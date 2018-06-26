/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import get from "lodash/get";
import isEqual from "lodash/isEqual";
import cloneDeep from "lodash/cloneDeep";
import BaseHistoryModule from "quill/modules/history";
import { delegateEvent } from "@dashboard/dom";
import KeyboardModule from "quill/modules/keyboard";
import { DeltaOperation, DeltaStatic } from "quill/core";
import { FOCUS_CLASS } from "@dashboard/embeds";

const SHORTKEY = /Mac/i.test(navigator.platform) ? "metaKey" : "ctrlKey";

/**
 * A custom history module to allow redo/undo to work while an Embed is focused
 * and hack around the fact that Quill doesn't have first class support for asynchronusly rendering things.
 */
export default class HistoryModule extends BaseHistoryModule {
    private readonly EMBED_KEY = "insert.embed-external";
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
        if (event.key === "z" && event[SHORTKEY]) {
            if (document.activeElement.classList.contains(FOCUS_CLASS)) {
                console.log("My own redo");
                if (event.shiftKey) {
                    this.redo();
                } else {
                    this.undo();
                }
            }
        }
    };

    /**
     * Occasionally perform a double undo/redo. This is to prevent the undo stack from getting trashed
     * by promises that don't resolve in an orderly fashion.
     */
    public change(source: "undo" | "redo", dest) {
        // console.log("Before undos", this.stack.undo);
        // console.log("Before redos", this.stack.redo);
        if (source === "undo" && this.needsDoubleUndo()) {
            super.change(source, dest);
            super.change(source, dest);
        } else if (source === "redo" && this.needsDoubleRedo()) {
            super.change(source, dest);
            super.change(source, dest);
        } else {
            super.change(source, dest);
        }

        // console.log("After undos", this.stack.undo);
        // console.log("After redos", this.stack.redo);
    }
    public record(changeDelta: DeltaStatic, oldDelta: DeltaStatic) {
        if (this.operationsContainKey(changeDelta.ops, this.EMBED_KEY)) {
            this.cutoff();
        }

        super.record(changeDelta, oldDelta);
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

    /**
     * This is SUPER hacky, but I couldn't find a better way to manage it.
     *
     * Certain operations (where we are async rendering a blot and it needs to return immediately anyways)
     * require 2 undos. These inserts have an insert of a Promise.
     *
     * If a double undo is not performed the blot will continually re-resolve, and re-render itself, making
     * undoing impossible.
     */
    private needsDoubleUndo(): boolean {
        const lastRedo = this.stack.undo[this.stack.undo.length - 1];
        // const secondToLastRedo = this.stack.undo[this.stack.undo.length - 2];

        return (
            // (lastRedo && this.operationsContainKey(lastRedo.redo.ops, this.EMBED_KEY)) ||
            lastRedo && this.operationsContainKey(lastRedo.undo.ops, this.EMBED_KEY)
        );
    }
    /**
     * This is SUPER hacky, but I couldn't find a better way to manage it.
     *
     * Certain operations (where we are async rendering a blot and it needs to return immediately anyways)
     * require 2 undos. These inserts have an insert of a Promise.
     *
     * If a double redo is not performed the blot will continually re-resolve, and re-render itself, making
     * undoing impossible.
     *
     * Unfornunately the redo stack is totally trashed after we redo one of the promise based items.
     */
    private needsDoubleRedo(): boolean {
        const lastRedo = this.stack.redo[this.stack.redo.length - 1];
        // const secondToLastRedo = this.stack.redo[this.stack.redo.length - 2];

        return (
            // (lastRedo && this.operationsContainKey(lastRedo.redo.ops, this.EMBED_KEY)) ||
            lastRedo && this.operationsContainKey(lastRedo.redo.ops, this.EMBED_KEY)
        );
    }
}
