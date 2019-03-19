/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Parchment from "parchment";
import Quill, { Blot } from "quill/core";
import registerQuill from "@rich-editor/quill/registerQuill";
import { ListGroup, ListValue, ListTag, ListType } from "@rich-editor/quill/blots/blocks/ListBlot";
import { expect } from "chai";

describe.only("ListBlot", () => {
    before(() => {
        registerQuill();
    });

    let quill: Quill;
    let quillNode: HTMLDivElement;

    const resetQuill = () => {
        document.body.innerHTML = `<div id='quill' />`;
        const mountPoint = document.getElementById("quill")!;
        quill = new Quill(mountPoint);
        quillNode = quill.scroll.domNode as HTMLDivElement;
    };

    const insertListBlot = (listValue: ListValue, text: string = "list item", index: number = 0): ListGroup => {
        let start = quill.scroll.length() - 1;
        if (start > 0) {
            quill.insertText(start, "\n", Quill.sources.USER);
            quill.update();
            start += 1;
        }
        quill.insertText(start, text, Quill.sources.USER);
        const length = text.length;
        quill.formatLine(start, length, ListGroup.blotName, listValue, Quill.sources.API);
        quill.update();
        return quill.getLine(index)[0];
    };

    beforeEach(() => {
        resetQuill();
    });

    it("can be created with a bullet list item with all possible value types", () => {
        const testCreateUl = (value: ListValue) => {
            const itemBlot = insertListBlot(value);

            expect(itemBlot.domNode.tagName).eq(ListTag.LI);
            const parent = itemBlot.domNode.parentElement!;
            expect(parent.tagName).eq(ListTag.UL);
            expect(parent.parentElement).eq(quillNode);
        };
        testCreateUl("bullet");
        resetQuill();
        testCreateUl({
            type: ListType.BULLETED,
        });
    });

    it("can be created with a simple ordered list item", () => {
        const testCreateOl = (value: ListValue) => {
            const itemBlot = insertListBlot("ordered");

            expect(itemBlot.domNode.tagName).eq(ListTag.LI);
            const parent = itemBlot.domNode.parentElement!;
            expect(parent.tagName).eq(ListTag.OL);
            expect(parent.parentElement).eq(quillNode);
        };

        testCreateOl("ordered");
        resetQuill();
        testCreateOl({
            type: ListType.NUMBERED,
        });
    });

    it("always reports it's value in the new object style", () => {
        insertListBlot("ordered");
        insertListBlot("bullet");

        const expected = [
            { insert: "list item" },
            {
                attributes: {
                    list: {
                        type: ListType.NUMBERED,
                    },
                },
                insert: "\n",
            },
            { insert: "list item" },
            {
                attributes: {
                    list: {
                        type: ListType.BULLETED,
                    },
                },
                insert: "\n",
            },
        ];

        expect(quill.getContents().ops).deep.equals(expected);
    });

    it("can be updated with a new list type");
    it("can be updated with a new depth value");
    it("can be updated from the old simple value to the new value type");
    it("can have its list formatting removed");
    it("nested list blots are joined together");
    it("lists can only gain depth if there are other list items before them");
    it("properly reports if nesting is possible");
    it("different lists of increasingly nested depths are joined together");
    it("different lists of the same depth do NOT get joined together");
});
