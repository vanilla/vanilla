/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Quill from "quill/core";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import { CodeToken } from "quill/modules/syntax";
import Module from "quill/core/module";
import throttle from "lodash/throttle";

/**
 * Module that triggers syntax highlighting in code blocks.
 * The actual code block highlighting logic lives in CodeBlockBlot.
 *
 * We use this instead of the built-in quill 1.x syntax module for the following reasons:
 *
 * - Only trigger on text change instead of constantly in a loop.
 * - Throttle highlights for performance reasons.
 * - Ensure we don't jump the cursor position around while highlighting.
 *
 * Maybe some of this won't be necessary once we are on the quill 2.x branch.
 */
export default class SyntaxModule extends Module {
    /** The throttle duration for the highlighting. */
    public static THROTTLE_DURATION = 500; // milliseconds

    /**
     * Register our code blots with quill.
     */
    public static register() {
        Quill.register(CodeToken, true);
        Quill.register(CodeBlockBlot, true);
    }

    /**
     * @inheritdoc
     */
    public constructor(public quill: Quill, options) {
        super(quill, options);
        this.setupEventHandler();
    }

    /**
     * Setup the text event handler that triggers changes.
     */
    private setupEventHandler() {
        this.quill.on("text-change", () => {
            requestAnimationFrame(() => {
                this.highlight();
            });
        });
    }

    /**
     * Apply syntax highlighting to code blots.
     *
     * This method is throttled to only occur on the trailing edge of THROTTLE_DURATION
     * The reasoning for only the trailing edge is that ideally we don't want to cause
     * any initial lag spike while the user is editing a code block.
     *
     * - Update quill (ensure all operations are persisted).
     * - Saves the existing selection.
     * - Bails out if there are no code blocks.
     * - Gets all the code blots.
     * - Writes out all the code blocks w/ highlighting.
     * - Update quill again (silently this time).
     * - Silently put the cursor back because the highlighting might have moved it.
     */
    public highlight = throttle(
        () => {
            if ((this.quill as any).selection.composing) {
                return;
            }
            this.quill.update(Quill.sources.USER);
            const selection = this.quill.getSelection();
            const codeBlocks = (this.quill.scroll.descendants(
                blot => blot instanceof CodeBlockBlot,
                0,
                this.quill.scroll.length() - 1,
            ) as any) as CodeBlockBlot[];

            if (codeBlocks.length === 0) {
                return; // Nothing to do here.
            }

            codeBlocks.forEach(code => {
                code.highlight();
            });
            this.quill.update(Quill.sources.SILENT);
            const hasFocus = this.quill.hasFocus();
            if (selection && hasFocus) {
                this.quill.setSelection(selection, Quill.sources.SILENT);
            }
        },
        SyntaxModule.THROTTLE_DURATION,
        {
            leading: false,
            trailing: true,
        },
    );
}
