/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import BaseSyntaxModule from "quill/modules/syntax";
import Quill from "quill/core";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";

/**
 * Override the core syntax module to register our own code block.
 */
export default class SyntaxModule extends BaseSyntaxModule {
    public static register() {
        super.register();
        Quill.register(CodeBlockBlot, true);
    }

    /**
     * Overridden to ensure quill has focus before resetting the selection
     * because quill selection does not always get moved away from quill when focus moves.
     *
     * For example opening the paragraph menu retains selection, but the setSelection at the end here
     * would clear remove focus from the paragraph menu.
     *
     * Check if this needs to be removed with Quill 2.0.
     */
    public highlight() {
        if ((this.quill as any).selection.composing) {
            return;
        }
        this.quill.update(Quill.sources.USER);
        const range = this.quill.getSelection();
        const hasFocus = this.quill.hasFocus();
        (this.quill as any).scroll.descendants(CodeBlockBlot).forEach(code => {
            code.highlight(this.options.highlight);
        });
        this.quill.update(Quill.sources.SILENT);
        if (range != null && hasFocus) {
            this.quill.setSelection(range, Quill.sources.SILENT);
        }
    }
}
