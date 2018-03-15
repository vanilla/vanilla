/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import Quill from "../Quill";
import Delta from "quill-delta";
import Keyboard from "quill/modules/keyboard";

describe("backspaceHandlers", () => {

    /** @var Quill */
    let quill;

    beforeAll(() => {
        document.body.innerHTML = `<form><div class="js-richText"></div></form>`;

        const container = document.querySelector(".js-richText");

        quill = new Quill(container);
    });

    // Sanity check.
    it("Can handle a backspace", () => {
        const delta = new Delta().insert("1");
        quill.setContents(delta);
        // quill.setSelection(1, 0);
        expect(quill.getText()).toEqual("1\n");
        console.log(quill.getSelection());

        const event = new KeyboardEvent(
            'keydown',
            {
                keyCode: Keyboard.keys.BACKSPACE,
            }
        );
        quill.root.dispatchEvent(event);
        expect(quill.getText()).toEqual("\n");
    });
});
