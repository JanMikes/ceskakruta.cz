/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { Editor } from '@tiptap/core';
import { wysiwygExtensions } from '../wysiwyg/extensions';
import '../styles/admin/wysiwyg.scss';

// Tiptap editor on top of the form's <textarea> (docs/admin/PLAN.md §3.12).
// The textarea stays the submitted field: it is only rewritten after an edit in the editor,
// so an untouched field (or one edited in the "HTML" mode) is saved exactly as it was.
const BUTTONS = [
    [
        { label: 'B', title: 'Tučně', className: 'fw-bold', run: (chain) => chain.toggleBold(), active: (editor) => editor.isActive('bold') },
        { label: 'I', title: 'Kurzíva', className: 'fst-italic', run: (chain) => chain.toggleItalic(), active: (editor) => editor.isActive('italic') },
        { label: 'Menší', title: 'Menší text', run: (chain) => chain.toggleSmall(), active: (editor) => editor.isActive('small') },
    ],
    [
        { label: 'Odstavec', title: 'Odstavec', run: (chain) => chain.setParagraph(), active: (editor) => editor.isActive('paragraph') },
        { label: 'H2', title: 'Nadpis', run: (chain) => chain.toggleHeading({ level: 2 }), active: (editor) => editor.isActive('heading', { level: 2 }) },
        { label: 'H3', title: 'Podnadpis', run: (chain) => chain.toggleHeading({ level: 3 }), active: (editor) => editor.isActive('heading', { level: 3 }) },
    ],
    [
        { label: '• Seznam', title: 'Odrážky', run: (chain) => chain.toggleBulletList(), active: (editor) => editor.isActive('bulletList') },
        { label: '1. Seznam', title: 'Číslovaný seznam', run: (chain) => chain.toggleOrderedList(), active: (editor) => editor.isActive('orderedList') },
    ],
    [
        { label: 'Vlevo', title: 'Zarovnat vlevo', run: (chain) => chain.unsetTextAlign(), active: () => false },
        { label: 'Na střed', title: 'Zarovnat na střed', run: (chain) => chain.setTextAlign('center'), active: (editor) => editor.isActive({ textAlign: 'center' }) },
        { label: 'Vpravo', title: 'Zarovnat vpravo', run: (chain) => chain.setTextAlign('right'), active: (editor) => editor.isActive({ textAlign: 'right' }) },
    ],
    [
        { label: 'Odkaz', title: 'Vložit / upravit odkaz', command: 'link', active: (editor) => editor.isActive('link') },
        { label: 'Zrušit odkaz', title: 'Odebrat odkaz', run: (chain) => chain.extendMarkRange('link').unsetLink(), active: () => false },
        { label: 'Obrázek', title: 'Vložit obrázek z URL', command: 'image', active: () => false },
        { label: '―', title: 'Vodorovná čára', run: (chain) => chain.setHorizontalRule(), active: () => false },
    ],
    [
        { label: '↶', title: 'Zpět', run: (chain) => chain.undo(), active: () => false },
        { label: '↷', title: 'Znovu', run: (chain) => chain.redo(), active: () => false },
    ],
];

export default class extends Controller {
    connect() {
        this.textarea = this.element;
        this.rawMode = false;

        this.wrapper = document.createElement('div');
        this.wrapper.className = 'wysiwyg border rounded';
        this.textarea.parentNode.insertBefore(this.wrapper, this.textarea);

        this.toolbar = this.buildToolbar();
        this.content = document.createElement('div');
        this.content.className = 'wysiwyg-content';

        this.wrapper.append(this.toolbar, this.content);
        this.wrapper.appendChild(this.textarea);
        this.textarea.classList.add('wysiwyg-raw', 'border-0', 'rounded-0');
        this.textarea.hidden = true;

        this.editor = new Editor({
            element: this.content,
            extensions: wysiwygExtensions(),
            content: this.textarea.value,
            onUpdate: ({ editor }) => {
                this.textarea.value = editor.isEmpty ? '' : editor.getHTML();
            },
            onTransaction: () => this.refreshToolbar(),
            onFocus: () => this.wrapper.classList.add('wysiwyg-focused'),
            onBlur: () => this.wrapper.classList.remove('wysiwyg-focused'),
        });

        this.refreshToolbar();
    }

    disconnect() {
        this.editor?.destroy();

        if (this.wrapper) {
            this.wrapper.parentNode.insertBefore(this.textarea, this.wrapper);
            this.wrapper.remove();
            this.textarea.hidden = false;
            this.textarea.classList.remove('wysiwyg-raw', 'border-0', 'rounded-0');
        }
    }

    buildToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'wysiwyg-toolbar d-flex flex-wrap gap-1 p-1 border-bottom bg-light';
        this.buttons = [];

        for (const group of BUTTONS) {
            const groupElement = document.createElement('div');
            groupElement.className = 'btn-group btn-group-sm';

            for (const definition of group) {
                const button = this.createButton(definition.label, definition.title, definition.className);
                button.addEventListener('click', () => this.execute(definition));
                groupElement.appendChild(button);
                this.buttons.push({ button, definition });
            }

            toolbar.appendChild(groupElement);
        }

        this.rawButton = this.createButton('HTML', 'Přepnout na úpravu HTML kódu');
        this.rawButton.classList.add('ms-auto');
        this.rawButton.addEventListener('click', () => this.toggleRaw());
        toolbar.appendChild(this.rawButton);

        return toolbar;
    }

    createButton(label, title, className = '') {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn btn-sm btn-outline-secondary ${className}`.trim();
        button.textContent = label;
        button.title = title;
        button.setAttribute('aria-label', title);

        return button;
    }

    execute(definition) {
        if (definition.command === 'link') {
            this.editLink();
        } else if (definition.command === 'image') {
            this.insertImage();
        } else {
            definition.run(this.editor.chain().focus()).run();
        }
    }

    editLink() {
        const previous = this.editor.getAttributes('link');
        const href = window.prompt('Adresa odkazu (prázdné = odebrat odkaz)', previous.href ?? 'https://');

        if (href === null) {
            return;
        }

        const chain = this.editor.chain().focus().extendMarkRange('link');

        if (href.trim() === '') {
            chain.unsetLink().run();

            return;
        }

        const external = /^https?:\/\//i.test(href) && !href.includes(window.location.host);
        chain.setLink({
            href: href.trim(),
            target: external ? '_blank' : null,
            rel: external ? 'noopener' : null,
        }).run();
    }

    insertImage() {
        const src = window.prompt('Adresa obrázku');

        if (src && src.trim() !== '') {
            this.editor.chain().focus().setImage({ src: src.trim() }).run();
        }
    }

    toggleRaw() {
        this.rawMode = !this.rawMode;

        if (!this.rawMode) {
            // back from raw HTML: the textarea keeps the raw value until the next edit in the editor
            this.editor.commands.setContent(this.textarea.value, { emitUpdate: false });
        }

        this.textarea.hidden = !this.rawMode;
        this.content.hidden = this.rawMode;
        this.rawButton.classList.toggle('active', this.rawMode);
        this.rawButton.setAttribute('aria-pressed', String(this.rawMode));
        this.buttons.forEach(({ button }) => { button.disabled = this.rawMode; });

        (this.rawMode ? this.textarea : this.editor.view.dom).focus();
        this.refreshToolbar();
    }

    refreshToolbar() {
        if (!this.editor || this.rawMode) {
            return;
        }

        for (const { button, definition } of this.buttons) {
            const active = definition.active(this.editor);
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', String(active));
        }
    }
}
