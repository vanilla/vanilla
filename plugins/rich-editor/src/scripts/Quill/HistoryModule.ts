/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import BaseHistoryModule from "quill/modules/history";
import Parchment from "parchment";
import { delegateEvent } from "@core/dom";
import KeyboardModule from "quill/modules/keyboard";

const SHORTKEY = /Mac/i.test(navigator.platform) ? 'metaKey' : 'ctrlKey';

/**
 * A custom history module to allow redo/undo to work while an Embed is focused.
 */
export default class HistoryModule extends BaseHistoryModule {

    constructor(quill, options) {
        super(quill, options);
        delegateEvent("keydown", ".embed", (event: KeyboardEvent, clickedElement) => {
            const zKeyCode = "Z".charCodeAt(0);
            if (event && event[SHORTKEY] && event.keyCode === zKeyCode) {
                if (event.shiftKey) {
                    this.redo();
                } else {
                    this.undo();
                }
            }
        }, this.quill.container);
    }
}
