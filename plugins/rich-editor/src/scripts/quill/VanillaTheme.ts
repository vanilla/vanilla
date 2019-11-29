/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// Quill
import Quill, { QuillOptionsStatic, RangeStatic } from "quill/core";
import ThemeBase from "quill/core/theme";
import KeyboardBindings from "@rich-editor/quill/KeyboardBindings";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import MarkdownModule from "@rich-editor/quill/MarkdownModule";
import NewLineClickInsertionModule from "./NewLineClickInsertionModule";
import KeyboardModule from "quill/modules/keyboard";

export default class VanillaTheme extends ThemeBase {
    /** The previous selection */
    private lastGoodSelection: RangeStatic;

    /**
     * Constructor.
     *
     * @param quill - The quill instance the theme is applying to.
     * @param options - The current options for the instance.
     */
    constructor(quill: Quill, options: QuillOptionsStatic) {
        const classesRichEditor = richEditorClasses(false);
        const themeOptions = {
            ...options,
            placeholder: "Create a new post...",
            scrollingContainer: "body",
        };

        super(quill, themeOptions);
        this.applyLastSelectionHack();

        this.quill.root.classList.add(classesRichEditor.text);
        this.quill.root.classList.add("richEditor-text");
        this.quill.root.classList.add("userContent");

        // Add keyboard bindings to options.
        this.addModule("embed/insertion");
        this.addModule("embed/focus");
        const keyboardBindings = new KeyboardBindings(this.quill);
        this.options.modules.keyboard.bindings = {
            ...this.options.modules.keyboard.bindings,
            ...keyboardBindings.bindings,
        };

        // Attaches the markdown keyboard listener.
        const markdownModule = new MarkdownModule(this.quill);
        markdownModule.registerHandler();

        // Create the newline insertion module.
        void new NewLineClickInsertionModule(this.quill);
    }

    /**
     * Override to ensure we get a public update event after the enter key is pressed.
     * Prevents a "lagging" paragraph menu that doesn't listen to silent events.
     */
    public init() {
        super.init();

        const keyboard = this.quill.getModule("keyboard") as KeyboardModule;
        keyboard.addBinding(
            {
                key: KeyboardModule.keys.ENTER,
            },
            {},
            () => {
                const selection = this.quill.getSelection();
                selection.index += 1;
                this.quill.setSelection(selection, Quill.sources.USER);
                return true;
            },
        );
    }

    /**
     * Apply a hacky method of tracking the last good selection in quill.
     *
     * This should be handled properly after forking.
     */
    private applyLastSelectionHack() {
        this.lastGoodSelection = {
            index: 0,
            length: 0,
        };

        // Track user selection events.
        this.quill.on(Quill.events.EDITOR_CHANGE, (type, value, oldValue, source) => {
            const selection = this.quill.getSelection();
            if (selection && source !== Quill.sources.SILENT) {
                this.lastGoodSelection = selection;
            }
        });

        this.quill.getLastGoodSelection = () => {
            return this.lastGoodSelection;
        };
    }
}
