/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import EmbedFocusModule from "./EmbedFocusModule";
import Quill from "quill/core";
import ExternalEmbedBlot from "./blots/embeds/ExternalEmbedBlot";
import { expect } from "chai";

describe("EmbedFocusModule", () => {
    let quill: Quill;
    let embedFocusModule: EmbedFocusModule;
    beforeEach(() => {
        document.body.innerHTML = `<div>
            <div class="FormWrapper"><div id="quillNoEditor"></div></div>
            <div class="richEditor"><div id="quillNoForm"></div></div>
            <div class="FormWrapper">
                <div class="richEditor">
                    <div id="quill"></div>
                    <button id="button1"></button>
                </div>
            </div>
        </div>`;

        Quill.register("formats/embed-external", ExternalEmbedBlot, true);
        quill = new Quill("#quill");
        embedFocusModule = new EmbedFocusModule(quill);
    });

    it("throws an error if it can't find its needed surrounding HTML", () => {
        const createNoEditor = () => {
            const badQuill = new Quill("#quillNoEditor");
            const badFocusModule = new EmbedFocusModule(badQuill);
        };

        const createNoForm = () => {
            const badQuill = new Quill("#quillNoForm");
            const badFocusModule = new EmbedFocusModule(badQuill);
        };
        expect(createNoEditor).to.throw();
        expect(createNoForm).to.throw();
    });

    it("adds a tabindex 0 on the root element", () => {
        expect(quill.root.getAttribute("tabindex")).eq("0");
    });

    describe("handleTab()", () => {
        it("handles a tab keypress when the quill root is focused", () => {
            quill.focus();
            const wasHandled = embedFocusModule.handleTab();
            expect(wasHandled).eq(true);
        });

        it("handles a tab keypress if the focused item is a FocusableEmbedBlot in the current quill instance");

        it("tabs to the next tabbable element if conditions were met");

        it(
            "does not handle a tab keypress if the focused item is not quill or a FocusableEmbedBlot in the quill instance",
        );

        it("will not tab to a focusable element inside of the editor that is not a FocusableEmbedBlot");
    });

    describe("handleTabShiftTab()", () => {
        it("will place focus on any element inside of the editor or the editor itself");

        it("will not handle the keypress if quill is focused");

        it("will handle the keypress if an element inside of the editor is focused");

        it("will place focus and selection on quill if the last element in the editor is text");

        it("will place focus on a FocusableEmbedBlot if it is the last element in the editor");
    });

    // SUPER TODO: -> Move these methods onto the EmbedFocusBlot itself.
    // TODO: rename to deleteEmbed
    describe("handleDeleteOnEmbed()", () => {
        it("will delete the currently active embed");

        it("triggers a quill update");

        it("places the selection at the same position if it will be text");

        it("places the focuses an embed blot if it will be in the original position");
    });

    // TODO: rename to insertNewlineAfterEmbed
    describe("handleEnterOnEmbed()", () => {
        it("inserts a newline after the selected element");
    });

    // TODO: move a large portion of this code onto the EmbedFocusBlot itself.
    describe("handleArrowKeyFromEmbed()", () => {});
});
