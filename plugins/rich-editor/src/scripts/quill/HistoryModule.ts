/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { FOCUS_CLASS } from "@library/embeddedContent/embedService";
import { DeltaOperation, DeltaStatic } from "quill/core";
import CodeBlock from "quill/formats/code";
import BaseHistoryModule from "quill/modules/history";
import ExternalEmbedBlot from "./blots/embeds/ExternalEmbedBlot";

const SHORTKEY = /Mac/i.test(navigator.platform) ? "metaKey" : "ctrlKey";

/**
 * A custom history module to allow redo/undo to work while an Embed is focused
 * and hack around the fact that Quill doesn't have first class support for asynchronusly rendering things.
 * @link https://quilljs.com/docs/modules/history
 */
export default class HistoryModule extends BaseHistoryModule {
    private readonly Z_KEYCODE = 90;

    /**
     * Add an undo handler for when an embed blot has focus.
     */
    constructor(quill, options) {
        super(quill, {
            ...options,
            // usersOnly: true,
        });
        document.addEventListener("keydown", this.undoKeyboardListener, true);
    }

    private actionIsEmbedCompletion(undoDelta: DeltaStatic, redoDelta: DeltaStatic) {
        const hasDeleteRecordOfLoadingEmbed = this.operationsContain(redoDelta.ops, this.isEmbedLoadedInsert);
        const hasAddRecordOfLoadingEmbed = this.operationsContain(undoDelta.ops, this.isEmbedLoadedInsert);
        return hasDeleteRecordOfLoadingEmbed || hasAddRecordOfLoadingEmbed;
    }

    /**
     * Does the operation contain a code-block insert?
     * @param operation Content operation.
     */
    private isCodeBlock = (operation: DeltaOperation | undefined): boolean => {
        return (
            typeof operation === "object" &&
            "attributes" in operation &&
            typeof operation.attributes === "object" &&
            CodeBlock.blotName in operation.attributes &&
            operation.attributes[CodeBlock.blotName]
        );
    };

    /**
     * Does the operation include an external embed insert?
     * @param operation Content operation.
     */
    private isEmbedInsert = (operation: DeltaOperation | undefined): boolean => {
        return this.isInsertOperation(operation) && ExternalEmbedBlot.blotName in operation!.insert;
    };

    /**
     * Does the operation include a fully-loaded external embed insert?
     * @param operation Content operation.
     */
    private isEmbedLoadedInsert = (operation: DeltaOperation | undefined): boolean => {
        return this.isEmbedInsert(operation) && "data" in operation!.insert[ExternalEmbedBlot.blotName];
    };

    /**
     * Does the operation contain any inserts?
     * @param operation Content operation.
     */
    private isInsertOperation = (operation: DeltaOperation | undefined): boolean => {
        return typeof operation === "object" && "insert" in operation && typeof operation.insert === "object";
    };

    /**
     * Iterate through a list of operations and, using a callback, determine if any contain a match.
     * @param ops List of change operations.
     * @param callback Function to check for the presence of a specific type of operation.
     */
    private operationsContain(ops: DeltaOperation[] | undefined, callback: (DeltaOperation) => boolean) {
        if (!ops) {
            return false;
        }

        for (const op of ops) {
            if (callback(op)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Record changes made to the content.
     * @param changeDelta New operations on the content.
     * @param oldDelta Previous content state.
     */
    public record(changeDelta: DeltaStatic, oldDelta: DeltaStatic) {
        if (this.operationsContain(changeDelta.ops, this.isEmbedInsert)) {
            // Avoid merging several changes as single undo/redo if an embed was added.
            this.cutoff();
        }

        if (this.operationsContain(changeDelta.ops, this.isCodeBlock)) {
            // Avoid merging preceding changes as single undo/redo when a code block is added.
            this.cutoff();
        }

        const undoDelta = this.quill.getContents().diff(oldDelta);
        if (this.actionIsEmbedCompletion(undoDelta, changeDelta)) {
            return;
        }

        super.record(changeDelta, oldDelta);
    }

    /**
     * Given a keyboard event, determine if an undo or re-do operation should be performed on the content.
     * @param event Keyboard input event.
     */
    private undoKeyboardListener = (event: KeyboardEvent) => {
        // Quill's Keyboard.match() FAILS to match a shortkey + z for some reason. Just check it ourself.
        if (event.keyCode === this.Z_KEYCODE && event[SHORTKEY]) {
            if (document.activeElement && document.activeElement.classList.contains(FOCUS_CLASS)) {
                if (event.shiftKey) {
                    this.redo();
                } else {
                    this.undo();
                }
            }
        }
    };
}
