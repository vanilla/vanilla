/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import FocusModule from "@rich-editor/quill/FocusModule";
import Quill, { Blot } from "quill/core";
import Parchment from "parchment";
import { expect } from "chai";
import KeyboardModule from "quill/modules/keyboard";
import FocusableEmbedBlot from "@rich-editor/quill/blots/abstract/FocusableEmbedBlot";
import LoadingBlot from "@rich-editor/quill/blots/embeds/LoadingBlot";
import { IEmbedValue } from "@rich-editor/quill/blots/embeds/ExternalEmbedBlot";
import classNames from "classnames";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";

const stubEmbedData: IEmbedValue = {
    data: {
        embedType: "stub",
        url: "",
        attributes: [],
    },
    loaderData: {
        type: "link",
        link: "adsfasd",
    },
};

describe("FocusModule", () => {
    let quill: Quill;
    let embedFocusModule: FocusModule;

    before(() => {
        Quill.register("formats/embed-loading", LoadingBlot, true);
    });

    beforeEach(() => {
        const classesRichEditor = richEditorClasses(false);
        document.body.innerHTML = `<div>
            <div class="FormWrapper"><div id="quillNoEditor"></div></div>
            <div class=${classNames("richEditor", classesRichEditor.root)}><div id="quillNoForm"></div></div>
            <div class="FormWrapper">
                <button id="buttonBefore"></button>
                <div class=${classNames("richEditor", classesRichEditor.root)}>
                    <div id="quill"></div>
                    <button id="button1"></button>
                </div>
            </div>
        </div>`;

        quill = new Quill("#quill");
        embedFocusModule = new FocusModule(quill);
    });

    it("throws an error if it can't find its needed surrounding HTML", () => {
        const createNoEditor = () => {
            const badQuill = new Quill("#quillNoEditor");
            const badFocusModule = new FocusModule(badQuill);
        };

        const createNoForm = () => {
            const badQuill = new Quill("#quillNoForm");
            const badFocusModule = new FocusModule(badQuill);
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

        it("handles a tab keypress if the focused item is a FocusableEmbedBlot in the current quill instance", async () => {
            const blot = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(blot);
            blot.focus();

            const wasHandled = embedFocusModule.handleTab();
            expect(wasHandled).eq(true);
        });

        it("tabs to the next tabbable element outside the editor if conditions were met", async () => {
            // Inserting a couple of focusable items in the editor.
            const blot = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            const blot2 = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            const button1 = document.getElementById("button1")!;
            quill.scroll.insertBefore(blot);
            quill.scroll.insertBefore(blot2);
            blot.focus();

            embedFocusModule.handleTab();
            expect(document.activeElement === button1).eq(true);
        });

        it("does not handle a tab keypress if the focused item is not quill or a FocusableEmbedBlot in the quill instance", () => {
            const button1 = document.getElementById("button1")!;
            button1.focus();

            const wasHandled = embedFocusModule.handleTab();
            expect(wasHandled).eq(false);
        });
    });

    describe("handleTabShiftTab()", () => {
        // We want natural tabbing here.
        it("will handle the keypress if the editor root is focused", () => {
            quill.focus();
            const wasHandled = embedFocusModule.handleShiftTab();
            expect(wasHandled).eq(true);
        });

        // It could be tabbable element inside. We do __not__ want focus to move back to the editor. It already "is".
        it("will handle the keypress if an element inside of the editor is focused", async () => {
            const blot = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(blot);
            blot.focus();

            const wasHandled = embedFocusModule.handleShiftTab();
            expect(wasHandled).eq(true);
        });
    });

    describe("focusLastLine()", () => {
        it("will place focus and selection on quill if the last element in the editor is text", async () => {
            const embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);
            const test = quill.insertText(quill.scroll.length(), "test");

            embedFocusModule.focusLastLine();

            expect(quill.hasFocus()).eq(true);
            expect(quill.getSelection().index, "The quill selection was incorrect").eq(quill.scroll.length() - 1);
        });

        it("will place focus on a FocusableEmbedBlot if it is the last element in the editor", async () => {
            const embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);
            const test = quill.insertText(0, "test");

            embedFocusModule.focusLastLine();

            expect(embed.domNode.contains(document.activeElement) || document.activeElement === embed.domNode).eq(true);
            expect(quill.getSelection().index, "The quill selection was incorrect").eq(quill.scroll.length() - 1);
        });
    });
    describe("focusFirstLine", () => {
        it("will place focus and selection on quill if the first element in the editor is text", async () => {
            const test = quill.insertText(0, "test");
            const embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);

            embedFocusModule.focusFirstLine();

            expect(quill.hasFocus()).eq(true);
            expect(quill.getSelection().index, "The quill selection was incorrect").eq(0);
        });

        it("will place focus on a FocusableEmbedBlot if it is the first element in the editor", async () => {
            const embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed, quill.scroll.children.tail!);
            const test = quill.insertText(quill.scroll.length(), "test");

            embedFocusModule.focusFirstLine();

            expect(embed.domNode.contains(document.activeElement) || document.activeElement === embed.domNode).eq(true);
            expect(quill.getSelection().index, "The quill selection was incorrect").eq(0);
        });
    });

    describe("handleArrowKeyFromEmbed()", () => {
        let embed: FocusableEmbedBlot;

        beforeEach(async () => {
            embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);
            embed.focus();
        });
        [KeyboardModule.keys.UP, KeyboardModule.keys.LEFT].forEach(key => {
            it("can insert a newline at the beginning of the scroll", async () => {
                embedFocusModule.handleArrowKeyFromEmbed(key, embed);
                expect(quill.scroll.children.head!.domNode.textContent).eq("");
            });
        });

        [KeyboardModule.keys.RIGHT, KeyboardModule.keys.DOWN].forEach(key => {
            it("can insert a newline at the end of the scroll", async () => {
                embedFocusModule.handleArrowKeyFromEmbed(key, embed);
                expect(quill.scroll.children.tail!.domNode.textContent).eq("");
            });
        });
    });

    describe("arrowToBlot()", () => {
        it("focuses a FocusableEmbedBlot", async () => {
            const embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);

            embedFocusModule.arrowToBlot(embed);
        });

        it("places selection at the start of a line of text by default", async () => {
            const embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);
            quill.insertText(quill.scroll.length(), "test");
            embed.focus();

            const expectedPosition = embed.next.offset(quill.scroll);

            embedFocusModule.arrowToBlot(embed.next as Blot);
            expect(quill.getSelection().index).eq(expectedPosition);
        });
    });

    describe("handleDeleteOnQuill()", () => {
        it("handles the keypress only if quill is focused", () => {
            const button1 = document.getElementById("button1")!;
            button1.focus();

            const wasHandled = embedFocusModule.handleDeleteOnQuill();
            expect(wasHandled).eq(false);
        });

        it("handles delete only if the current line is empty and the previous blot is a FocusableEmbedBlot", async () => {
            const embed = Parchment.create("embed-loading", stubEmbedData) as LoadingBlot;
            quill.scroll.insertBefore(embed);
            const test = quill.insertText(quill.scroll.length(), "\n");
            quill.setSelection(quill.scroll.length() - 1, 0);
            let wasHandled = embedFocusModule.handleDeleteOnQuill();
            expect(wasHandled).eq(true);

            quill.insertText(quill.scroll.length(), "test");
            quill.setSelection(3, 0);
            wasHandled = embedFocusModule.handleDeleteOnQuill();
            expect(wasHandled).eq(false);

            quill.setContents([]);
            quill.insertText(quill.scroll.length(), "\n\n\n\n\n");
            quill.setSelection(3, 0);
            wasHandled = embedFocusModule.handleDeleteOnQuill();
            expect(wasHandled).eq(false);
        });
    });
});
