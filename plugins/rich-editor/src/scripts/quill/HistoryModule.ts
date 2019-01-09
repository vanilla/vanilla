/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import get from "lodash/get";
import BaseHistoryModule from "quill/modules/history";
import { DeltaOperation, DeltaStatic } from "quill/core";
import { FOCUS_CLASS } from "@library/embeds";

const SHORTKEY = /Mac/i.test(navigator.platform) ? "metaKey" : "ctrlKey";

/**
 * A custom history module to allow redo/undo to work while an Embed is focused
 * and hack around the fact that Quill doesn't have first class support for asynchronusly rendering things.
 */
export default class HistoryModule extends BaseHistoryModule {
    private readonly EMBED_KEY = "insert.embed-external";
    private readonly EMBED_CONFIRM_KEY = "insert.embed-external.loaderData.loaded";
    private readonly CODE_KEY = "attributes.codeBlock";
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

    public undoKeyboardListener = (event: KeyboardEvent) => {
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

    public record(changeDelta: DeltaStatic, oldDelta: DeltaStatic) {
        if (this.operationsContainKey(changeDelta.ops, this.EMBED_KEY)) {
            this.cutoff();
        }

        if (this.operationsContainKey(changeDelta.ops, this.CODE_KEY)) {
            this.cutoff();
        }

        const undoDelta = this.quill.getContents().diff(oldDelta);
        if (this.actionIsEmbedCompletion(undoDelta, changeDelta)) {
            return;
        }
        super.record(changeDelta, oldDelta);
    }

    public actionIsEmbedCompletion(undoDelta: DeltaStatic, redoDelta: DeltaStatic) {
        const hasDeleteRecordOfLoadingEmbed = this.operationsContainKey(redoDelta.ops, this.EMBED_CONFIRM_KEY);
        const hasAddRecordOfLoadingEmbed = this.operationsContainKey(undoDelta.ops, this.EMBED_CONFIRM_KEY);
        return hasDeleteRecordOfLoadingEmbed || hasAddRecordOfLoadingEmbed;
    }

    private operationsContainKey(ops: DeltaOperation[] | undefined, key: string) {
        if (!ops) {
            return false;
        }

        for (const op of ops) {
            if (get(op, key, false)) {
                return true;
            }
        }

        return false;
    }
}
