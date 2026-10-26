// Tiptap schema of the admin rich text fields (docs/admin/PLAN.md §3.12).
// Shared by the `wysiwyg` Stimulus controller and tools/wysiwyg-normalization-report.mjs, keep them in sync by importing only this.
import { Mark, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import TextAlign from '@tiptap/extension-text-align';

// Old CKEditor style "Menší text"
export const Small = Mark.create({
    name: 'small',

    parseHTML() {
        return [{ tag: 'span.small' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes, { class: 'small' }), 0];
    },

    addCommands() {
        return {
            toggleSmall: () => ({ commands }) => commands.toggleMark(this.name),
        };
    },
});

// Legacy content uses `align="center"` as well as `style="text-align: …"`
const LegacyTextAlign = TextAlign.extend({
    addGlobalAttributes() {
        return [{
            types: this.options.types,
            attributes: {
                textAlign: {
                    default: this.options.defaultAlignment,
                    parseHTML: (element) => {
                        const alignment = element.style.textAlign || element.getAttribute('align');

                        return this.options.alignments.includes(alignment) ? alignment : this.options.defaultAlignment;
                    },
                    renderHTML: (attributes) => {
                        if (!this.options.alignments.includes(attributes.textAlign)) {
                            return {};
                        }

                        return { style: `text-align: ${attributes.textAlign}` };
                    },
                },
            },
        }];
    },
});

export function wysiwygExtensions() {
    return [
        StarterKit.configure({
            heading: { levels: [1, 2, 3, 4] },
            link: {
                openOnClick: false,
                autolink: true,
                // keep href / target / rel exactly as in the content, no defaults added
                HTMLAttributes: { target: null, rel: null },
            },
        }),
        Image.configure({ inline: true, allowBase64: true }),
        LegacyTextAlign.configure({ types: ['heading', 'paragraph'] }),
        Small,
    ];
}
