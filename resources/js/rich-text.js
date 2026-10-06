/**
 * Editor de texto enriquecido (TipTap): negrita, cursiva, subrayado, tachado, color de texto,
 * color de fondo, alineación, listas, enlaces y tablas.
 *
 * Se usa con x-data="richEditor({ value, placeholder, onChange })". El servidor vuelve a limpiar
 * el HTML al guardar (App\Support\RichText), por lo que solo sobreviven etiquetas y estilos permitidos.
 */
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { BackgroundColor, Color, TextStyle } from '@tiptap/extension-text-style';
import TextAlign from '@tiptap/extension-text-align';
import { Table, TableCell, TableHeader, TableRow } from '@tiptap/extension-table';
import { Placeholder } from '@tiptap/extensions';

const COLORS = [
    ['#000000', 'Negro'],
    ['#4b5563', 'Gris oscuro'],
    ['#9ca3af', 'Gris'],
    ['#ffffff', 'Blanco'],
    ['#dc2626', 'Rojo'],
    ['#ea580c', 'Naranja'],
    ['#facc15', 'Amarillo'],
    ['#16a34a', 'Verde'],
    ['#0891b2', 'Turquesa'],
    ['#2563eb', 'Azul'],
    ['#7c3aed', 'Violeta'],
    ['#db2777', 'Rosado'],
    ['#fecaca', 'Rojo claro'],
    ['#fef08a', 'Amarillo claro'],
    ['#bbf7d0', 'Verde claro'],
    ['#bfdbfe', 'Azul claro'],
];

window.richEditor = ({ value = '', placeholder = '', onChange = () => {} }) => {
    // El editor se guarda fuera del estado de Alpine: envuelto en un proxy reactivo TipTap falla.
    let editor = null;

    return {
        colors: COLORS,
        // Panel abierto: 'color' | 'background' | 'link' | 'table' | null
        panel: null,
        linkUrl: '',
        // Se incrementa en cada cambio para que Alpine vuelva a evaluar los botones activos.
        tick: 0,

        init() {
            editor = new Editor({
                element: this.$refs.content,
                content: value,
                extensions: [
                    StarterKit.configure({
                        blockquote: false,
                        code: false,
                        codeBlock: false,
                        heading: false,
                        horizontalRule: false,
                        link: {
                            openOnClick: false,
                            autolink: true,
                            HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
                        },
                    }),
                    TextStyle,
                    Color,
                    BackgroundColor,
                    TextAlign.configure({ types: ['paragraph'], alignments: ['left', 'center', 'right', 'justify'] }),
                    Table.configure({ resizable: false }),
                    TableRow,
                    TableHeader,
                    TableCell,
                    Placeholder.configure({ placeholder }),
                ],
                editorProps: {
                    attributes: { class: 'rich-content' },
                },
                onUpdate: ({ editor: current }) => {
                    onChange(current.isEmpty ? '' : current.getHTML());
                },
                onTransaction: () => {
                    this.tick++;
                },
            });
        },

        destroy() {
            editor?.destroy();
            editor = null;
        },

        // Para los x-bind:class de la barra: leer `tick` hace que se actualicen al mover el cursor.
        is(name, attributes = {}) {
            void this.tick;

            return editor?.isActive(name, attributes) ?? false;
        },

        isAligned(alignment) {
            void this.tick;

            return editor?.isActive({ textAlign: alignment }) ?? false;
        },

        get inTable() {
            void this.tick;

            return editor?.isActive('table') ?? false;
        },

        get canUndo() {
            void this.tick;

            return editor?.can().undo() ?? false;
        },

        get canRedo() {
            void this.tick;

            return editor?.can().redo() ?? false;
        },

        run(command) {
            const chain = editor.chain().focus();

            switch (command) {
                case 'bold': chain.toggleBold().run(); break;
                case 'italic': chain.toggleItalic().run(); break;
                case 'underline': chain.toggleUnderline().run(); break;
                case 'strike': chain.toggleStrike().run(); break;
                case 'bulletList': chain.toggleBulletList().run(); break;
                case 'orderedList': chain.toggleOrderedList().run(); break;
                case 'undo': chain.undo().run(); break;
                case 'redo': chain.redo().run(); break;
                case 'addRowAfter': chain.addRowAfter().run(); break;
                case 'addColumnAfter': chain.addColumnAfter().run(); break;
                case 'deleteRow': chain.deleteRow().run(); break;
                case 'deleteColumn': chain.deleteColumn().run(); break;
                case 'toggleHeaderRow': chain.toggleHeaderRow().run(); break;
                case 'deleteTable': chain.deleteTable().run(); break;
            }
        },

        align(alignment) {
            editor.chain().focus().setTextAlign(alignment).run();
        },

        setColor(kind, color) {
            const chain = editor.chain().focus();

            if (kind === 'color') {
                color ? chain.setColor(color).run() : chain.unsetColor().run();
            } else {
                color ? chain.setBackgroundColor(color).run() : chain.unsetBackgroundColor().run();
            }

            this.panel = null;
        },

        togglePanel(name) {
            this.panel = this.panel === name ? null : name;

            if (name === 'link' && this.panel === 'link') {
                this.linkUrl = editor.getAttributes('link').href ?? '';
                this.$nextTick(() => this.$refs.linkInput?.focus());
            }
        },

        applyLink() {
            const url = this.linkUrl.trim();

            if (url === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
            } else {
                const href = /^(https?:\/\/|mailto:)/i.test(url) ? url : `https://${url}`;
                editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
            }

            this.panel = null;
        },

        removeLink() {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            this.panel = null;
        },

        insertTable(rows, cols) {
            editor.chain().focus().insertTable({ rows, cols, withHeaderRow: true }).run();
            this.panel = null;
        },
    };
};
