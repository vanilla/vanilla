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
import { isEditorWalledEvent } from "@rich-editor/editor/pieces/EditorEventWall";

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
        const themeOptions = {
            ...options,
            placeholder: "Create a new post...",
            scrollingContainer: "body",
        };

        super(quill, themeOptions);
        this.applyLastSelectionHack();
        this.applyFocusFixHack();

        this.quill.root.classList.add("richEditor-text");
        this.quill.root.classList.add("userContent");
        this.quill.root.setAttribute("tabindex", 0);
        this.quill.container.addEventListener("click", (e) => {
            e.preventDefault();
        });

        // Add keyboard bindings to options.
        this.addModule("embed/insertion");
        this.addModule("embed/selection");
        this.applyEmitterHack();

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
     * Apply our editor event wall checking to quills own event handling.
     * @see {<EditorEventWall />}
     */
    private applyEmitterHack() {
        const realHandleDOM = this.quill.emitter.handleDOM;

        this.quill.emitter.handleDOM = (event: Event, ...args) => {
            if (!isEditorWalledEvent(event)) {
                realHandleDOM.call(this.quill.emitter, event, ...args);
            }
        };
    }

    /**
     * Quill has a bad habit of scrolling the document on focus.
     * We are monkey patching over it to pass some extra arguments to the build in focus method.
     */
    private applyFocusFixHack() {
        const { root } = this.quill.selection;
        const initialFocus: typeof HTMLElement.prototype.focus = root.focus;

        root.focus = function (options = {}) {
            initialFocus.call(root, {
                ...options,
                preventScroll: true,
            });
        };
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
