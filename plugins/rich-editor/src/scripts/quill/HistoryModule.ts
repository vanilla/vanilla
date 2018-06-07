/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import BaseHistoryModule from "quill/modules/history";
import { delegateEvent } from "@dashboard/dom";
import KeyboardModule from "quill/modules/keyboard";

/**
 * A custom history module to allow redo/undo to work while an Embed is focused.
 */
export default class HistoryModule extends BaseHistoryModule {
    /**
     * Add an undo handler for when an embed blot has focus.
     */
    constructor(quill, options) {
        super(quill, options);
        delegateEvent(
            "keydown",
            ".embed",
            (event: KeyboardEvent, clickedElement) => {
                if (
                    KeyboardModule.match(event, {
                        key: "z",
                        metaKey: true,
                    })
                ) {
                    if (event.shiftKey) {
                        this.redo();
                    } else {
                        this.undo();
                    }
                }
            },
            this.quill.container,
        );
    }

    /**
     * Occasionally perform a double undo.
     *
     * @see {needsDoubleUndo}
     */
    public change(source: "undo" | "redo", dest) {
        if (source === "undo" && this.needsDoubleUndo()) {
            this.ignoreChange = true;
            console.log("double undo");
            super.change(source, dest);
            super.change(source, dest);
            this.ignoreChange = false;
        } else if (source === "redo" && this.needsDoubleRedo()) {
            console.log("double redo");
            this.ignoreChange = true;
            super.change(source, dest);
            super.change(source, dest);
            this.ignoreChange = false;
        } else {
            super.change(source, dest);
        }
    }

    public record(changeDelta, oldDelta) {
        super.record(changeDelta, oldDelta);
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
        const lastUndo = this.stack.undo[this.stack.undo.length - 1];
        if (!lastUndo) {
            return false;
        }

        const lastUndoOps = lastUndo.undo.ops;

        let containsAPromiseInsert = false;
        lastUndoOps.forEach(op => {
            if (op.insert && op.insert["embed-external"] && op.insert["embed-external"] instanceof Promise) {
                containsAPromiseInsert = true;
            }
        });
        return containsAPromiseInsert;
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
    private needsDoubleRedo(): boolean {
        const lastRedo = this.stack.redo[this.stack.redo.length - 1];
        if (!lastRedo) {
            return false;
        }

        const lastRedoOps = lastRedo.undo.ops;

        let containsAPromiseInsert = false;
        lastRedoOps.forEach(op => {
            if (op.insert && op.insert["embed-external"] && op.insert["embed-external"] instanceof Promise) {
                containsAPromiseInsert = true;
            }
        });
        return containsAPromiseInsert;
    }
}
