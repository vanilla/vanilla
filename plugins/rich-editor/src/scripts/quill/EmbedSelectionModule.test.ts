/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import EmbedSelectionModule from "@rich-editor/quill/EmbedSelectionModule";
import Quill, { Blot } from "quill/core";
import Parchment from "parchment";
import { expect } from "chai";
import KeyboardModule from "quill/modules/keyboard";
import { SelectableEmbedBlot } from "@rich-editor/quill/blots/abstract/SelectableEmbedBlot";
import LoadingBlot from "@rich-editor/quill/blots/embeds/LoadingBlot";
import { IEmbedValue } from "@rich-editor/quill/blots/embeds/ExternalEmbedBlot";
import classNames from "classnames";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import { setupTestQuill } from "@rich-editor/__tests__/quillUtils";

const stubEmbedData: IEmbedValue = {
    data: {
        embedType: "stub",
        url: "",
    },
    loaderData: {
        type: "link",
        link: "adsfasd",
    },
};

describe("EmbedSelectionModule", () => {
    let quill: Quill;
    let embedEmbedSelectionModule: EmbedSelectionModule;

    before(() => {
        Quill.register("formats/embed-loading", LoadingBlot, true);
    });

    beforeEach(() => {
        quill = setupTestQuill();
        embedEmbedSelectionModule = new EmbedSelectionModule(quill);
    });

    describe("handleArrowKeyFromEmbed()", () => {
        let embed: SelectableEmbedBlot;

        beforeEach(async () => {
            embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);
            embed.select();
        });
        [KeyboardModule.keys.UP, KeyboardModule.keys.LEFT].forEach(key => {
            it("can insert a newline at the beginning of the scroll", async () => {
                embedEmbedSelectionModule.handleArrowKeyFromEmbed(key, embed);
                expect(quill.scroll.children.head!.domNode.textContent).eq("");
            });
        });

        [KeyboardModule.keys.RIGHT, KeyboardModule.keys.DOWN].forEach(key => {
            it("can insert a newline at the end of the scroll", async () => {
                embedEmbedSelectionModule.handleArrowKeyFromEmbed(key, embed);
                expect(quill.scroll.children.tail!.domNode.textContent).eq("");
            });
        });
    });

    describe("arrowToBlot()", () => {
        it("focuses a SelectableEmbedBlot", async () => {
            const embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);

            embedEmbedSelectionModule.arrowToBlot(embed);
        });

        it("places selection at the start of a line of text by default", async () => {
            const embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);
            quill.insertText(quill.scroll.length(), "test");
            embed.select();

            const expectedPosition = embed.next.offset(quill.scroll);

            embedEmbedSelectionModule.arrowToBlot(embed.next as Blot);
            expect(quill.getSelection().index).eq(expectedPosition);
        });
    });

    describe("handleDeleteOnQuill()", () => {
        it("handles the keypress only if quill is focused", () => {
            const button1 = document.getElementById("button1")!;
            button1.focus();

            const wasHandled = embedEmbedSelectionModule.handleDeleteOnQuill();
            expect(wasHandled).eq(false);
        });

        it("handles delete only if the current line is empty and the previous blot is a SelectableEmbedBlot", async () => {
            const embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);
            const test = quill.insertText(quill.scroll.length(), "\n");
            quill.setSelection(quill.scroll.length() - 1, 0);
            let wasHandled = embedEmbedSelectionModule.handleDeleteOnQuill();
            expect(wasHandled).eq(true);

            quill.insertText(quill.scroll.length(), "test");
            quill.setSelection(3, 0);
            wasHandled = embedEmbedSelectionModule.handleDeleteOnQuill();
            expect(wasHandled).eq(false);

            quill.setContents([]);
            quill.insertText(quill.scroll.length(), "\n\n\n\n\n");
            quill.setSelection(3, 0);
            wasHandled = embedEmbedSelectionModule.handleDeleteOnQuill();
            expect(wasHandled).eq(false);
        });
    });
});
