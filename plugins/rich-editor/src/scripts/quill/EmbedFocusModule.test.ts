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
    before(() => {
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

    describe("handleTab", () => {
        it("handles a tab when quill is focused", () => {
            quill.focus();
            const wasHandled = embedFocusModule.handleTab();
            expect(wasHandled).eq(true);
        });
    });
});
