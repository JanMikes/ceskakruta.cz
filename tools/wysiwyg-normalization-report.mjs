// Dry-run normalization report of the admin WYSIWYG (docs/admin/PLAN.md §3.12 "Guard").
// Runs every rich-text row through Tiptap generateJSON → generateHTML with the editor's extensions and lists rows
// that would lose images, links, headings, lists or text on their next save. Read-only, prints only counts and ids
// (never row contents — they can contain personal data).
//
// Rows come as NDJSON on stdin ({"t": table, "c": column, "id": id, "h": html}), the query is printed by `--sql`:
//   docker compose exec -T web node tools/wysiwyg-normalization-report.mjs --sql > var/wysiwyg-rows.sql
//   docker compose exec -T mariadb sh -c 'mariadb -uroot -proot ceskakruta -N -r -B' < var/wysiwyg-rows.sql \
//     | docker compose exec -T web node tools/wysiwyg-normalization-report.mjs
import { createInterface } from 'node:readline';
import { generateHTML, generateJSON } from '@tiptap/html';
import { Window } from 'happy-dom';
import { wysiwygExtensions } from '../assets/wysiwyg/extensions.js';

// Every field with CKEditor in the old admin
const COLUMNS = [
    ['actuality', 'text'],
    ['actuality', 'text_full'],
    ['content', 'text'],
    ['place', 'description'],
    ['product', 'text'],
    ['product', 'text_additional'],
    ['recipe', 'text'],
    ['sortiment', 'text'],
];

if (process.argv.includes('--sql')) {
    const selects = COLUMNS.map(([table, column]) => `SELECT CONVERT(JSON_OBJECT('t', '${table}', 'c', '${column}', 'id', id, 'h', \`${column}\`) USING utf8mb4) FROM \`${table}\` WHERE del_flag = 0 AND \`${column}\` IS NOT NULL AND \`${column}\` <> ''`);
    process.stdout.write(selects.join('\nUNION ALL\n') + ';\n');
    process.exit(0);
}

const extensions = wysiwygExtensions();
const window = new Window();

function measure(html) {
    const document = window.document;
    const root = document.createElement('div');
    root.innerHTML = html;
    const attributes = (selector, attribute) => [...root.querySelectorAll(selector)].map((element) => element.getAttribute(attribute) ?? '').sort().join('\n');

    return {
        images: attributes('img', 'src'),
        links: attributes('a[href]', 'href') + '|' + attributes('a[target]', 'target'),
        headings: [...root.querySelectorAll('h1, h2, h3, h4, h5, h6')].map((element) => element.tagName).join(','),
        lists: ['ul', 'ol', 'li'].map((tag) => root.querySelectorAll(tag).length).join(','),
        tables: String(root.querySelectorAll('table').length),
        text: (root.textContent ?? '').replace(/\s+/gu, ''),
    };
}

const CHECKS = ['images', 'links', 'headings', 'lists', 'tables', 'text'];
const perColumn = new Map();
const flagged = [];
let rows = 0;
let unparsable = 0;

for await (const line of createInterface({ input: process.stdin, crlfDelay: Infinity })) {
    if (line.trim() === '') {
        continue;
    }

    const row = JSON.parse(line);
    const key = `${row.t}.${row.c}`;
    const stats = perColumn.get(key) ?? { rows: 0, changed: 0, flagged: 0 };
    perColumn.set(key, stats);
    stats.rows++;
    rows++;

    let normalized;
    try {
        normalized = generateHTML(generateJSON(row.h, extensions), extensions);
    } catch {
        unparsable++;
        stats.flagged++;
        flagged.push({ key, id: row.id, losses: ['unparsable'] });
        continue;
    }

    if (normalized !== row.h) {
        stats.changed++;
    }

    const before = measure(row.h);
    const after = measure(normalized);
    const losses = CHECKS.filter((check) => before[check] !== after[check]);

    if (losses.length > 0) {
        stats.flagged++;
        flagged.push({ key, id: row.id, losses });
    }
}

await window.happyDOM.close();

const lossTotals = Object.fromEntries(CHECKS.map((check) => [check, flagged.filter((row) => row.losses.includes(check)).length]));

console.log(`Rows checked: ${rows}, HTML changed by normalization: ${[...perColumn.values()].reduce((sum, stats) => sum + stats.changed, 0)}, flagged: ${flagged.length}, unparsable: ${unparsable}`);
console.log('Flagged rows per check:', lossTotals);
console.log('\nPer column (rows / changed / flagged):');
for (const [key, stats] of [...perColumn.entries()].sort()) {
    console.log(`  ${key.padEnd(26)} ${String(stats.rows).padStart(5)} ${String(stats.changed).padStart(5)} ${String(stats.flagged).padStart(5)}`);
}
console.log('\nFlagged rows (table.column #id: differing checks):');
for (const row of flagged) {
    console.log(`  ${row.key} #${row.id}: ${row.losses.join(', ')}`);
}
