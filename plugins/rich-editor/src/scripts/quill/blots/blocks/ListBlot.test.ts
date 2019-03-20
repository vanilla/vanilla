/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Parchment from "parchment";
import Quill, { Blot } from "quill/core";
import Delta from "quill-delta";
import registerQuill from "@rich-editor/quill/registerQuill";
import {
    OrderedListGroup,
    UnorderedListGroup,
    ListValue,
    ListTag,
    ListType,
    ListItem,
    ListGroup,
} from "@rich-editor/quill/blots/blocks/ListBlot";
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

    const insertListBlot = (listValue: ListValue, text: string = "list item"): ListItem => {
        let delta = new Delta();
        if (quill.scroll.length() === 1) {
            delta = delta.delete(1);
        } else {
            delta = delta.retain(quill.scroll.length());
        }

        delta = delta.insert(text + "\n", { list: listValue });
        quill.updateContents(delta, Quill.sources.USER);
        const lastUL = quill.scroll.children.tail as UnorderedListGroup;
        return lastUL.children.tail as any;
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
            depth: 0,
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
            depth: 0,
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
                        depth: 0,
                    },
                },
                insert: "\n",
            },
            { insert: "list item" },
            {
                attributes: {
                    list: {
                        type: ListType.BULLETED,
                        depth: 0,
                    },
                },
                insert: "\n",
            },
        ];

        expect(quill.getContents().ops).deep.equals(expected);
    });

    it("can be update the depth through quill's formatLine API", () => {
        insertListBlot({ type: ListType.NUMBERED, depth: 0 });
        expect(quill.getContents().ops).deep.equals([
            { insert: "list item" },
            {
                attributes: {
                    list: {
                        type: ListType.NUMBERED,
                        depth: 0,
                    },
                },
                insert: "\n",
            },
        ]);
        quill.formatLine(0, 1, ListItem.blotName, {
            type: ListType.NUMBERED,
            depth: 1,
        });
        expect(quill.getContents().ops).deep.equals([
            { insert: "list item" },
            {
                attributes: {
                    list: {
                        type: ListType.NUMBERED,
                        depth: 1,
                    },
                },
                insert: "\n",
            },
        ]);
    });
    it("list blots of the same type & level are joined together", () => {
        const testAutoJoining = (depth: number, type: ListType.BULLETED) => {
            insertListBlot({ type, depth });
            insertListBlot({ type, depth });
            insertListBlot({ type, depth });
            const listGroup = quill.scroll.children.tail as ListGroup;
            expect(quill.scroll.children.tail).eq(quill.scroll.children.head);
            expect(listGroup).instanceOf(ListGroup);
            expect(listGroup.children).has.length(3);
            resetQuill();
        };

        const depths = [1, 2, 3, 4];
        const types = Object.values(ListType);
        for (const depth of depths) {
            for (const type of types) {
                testAutoJoining(depth, type);
            }
        }
    });

    describe("list blots of different types & levels are separeted or nested properly", () => {
        it("different types", () => {
            insertListBlot({ type: ListType.CHECKBOX, depth: 0 });
            insertListBlot({ type: ListType.NUMBERED, depth: 0 });

            expect(quill.scroll.children).has.length(2);
            quill.scroll.children.forEach((blot: ListGroup) => {
                expect(blot).instanceOf(ListGroup);
                expect(blot.children).has.length(1);
                expect(blot.children.head).instanceOf(ListItem);
            });
        });

        it.only("different levels", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 });
            insertListBlot({ type: ListType.BULLETED, depth: 0 });
            insertListBlot({ type: ListType.BULLETED, depth: 1 });
            insertListBlot({ type: ListType.BULLETED, depth: 1 });
            insertListBlot({ type: ListType.BULLETED, depth: 0 });

            // The inner items should be
            // UL > LI
            //    > LI
            //    > LI > UL > LI
            //              > LI
            //    > LI

            expect(quill.scroll.children).has.length(1);
            const outerUL = quill.scroll.children.head as ListGroup;

            expect(outerUL.children).has.length(4);
        });
    });
    it("lists can only gain depth if there are other list items before them");
    it("properly reports if nesting is possible");
    it("different lists of increasingly nested depths are joined together");
    it("different lists of the same depth do NOT get joined together");
});
