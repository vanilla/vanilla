/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "quill";
import Parchment from "parchment";
import KeyboardModule from "quill/modules/keyboard";
import Module from "quill/core/module";
import { RangeStatic } from "quill/core";

export default class EmbedFocusModule extends Module {

    private keyboard: KeyboardModule;

    constructor(private quill: Quill, options = {}) {
        super(quill as any, options);

        this.keyboard = quill.getModule("keyboard");
        this.setupQuillArrowKeyListeners();
    }

    private setupQuillArrowKeyListeners() {
        this.keyboard.addBinding(KeyboardModule.keys.UP, {}, this.setupFocusOnPreviousEmbed);
    }

    private setupFocusOnPreviousEmbed(range: RangeStatic) {
        return;
    }
}
