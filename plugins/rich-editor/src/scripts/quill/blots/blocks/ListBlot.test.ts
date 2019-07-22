/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    ListGroup,
    ListItem,
    ListItemWrapper,
    ListTag,
    ListType,
    ListValue,
    OrderedListGroup,
    UnorderedListGroup,
} from "@rich-editor/quill/blots/blocks/ListBlot";
import Formatter from "@rich-editor/quill/Formatter";
import registerQuill from "@rich-editor/quill/registerQuill";
import { expect } from "chai";
import Delta from "quill-delta";
import Quill from "quill/core";
import { setupTestQuill } from "@rich-editor/__tests__/quillUtils";
import OpUtils from "@rich-editor/__tests__/OpUtils";

describe("ListBlot", () => {
    before(() => {
        registerQuill();
    });

    let quill: Quill;
    let quillNode: HTMLDivElement;

    const resetQuill = () => {
        quill = setupTestQuill();
        const buttonCont = document.createElement("div");
        buttonCont.innerHTML = `
            <div>
                <button id="indent">Indent</button>
                <button id="outdent">Outdent</button>
            </div>`;
        document.body.appendChild(buttonCont);
        const indent = document.getElementById("indent")!;
        const outdent = document.getElementById("outdent")!;
        const formatter = new Formatter(quill, quill.getSelection());
        indent.addEventListener("click", e => {
            e.preventDefault();
            formatter.indentList();
        });
        outdent.addEventListener("click", e => {
            e.preventDefault();
            formatter.outdentList();
        });
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
        quill.history.clear();
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
            type: ListType.ORDERED,
            depth: 0,
        });
    });

    it("deeply nested list items can be created", () => {
        const nestedDelta = [
            OpUtils.op("Line 1"),
            OpUtils.list(ListType.BULLETED, 0),
            OpUtils.op("Line 1.1"),
            OpUtils.list(ListType.ORDERED, 1),
            OpUtils.op("Line 1.1.1"),
            OpUtils.list(ListType.BULLETED, 2),
            OpUtils.op("Line 1.1.1.1"),
            OpUtils.list(ListType.ORDERED, 3),
        ];

        quill.setContents(nestedDelta);

        expect(quill.getContents().ops).deep.eq(nestedDelta);
    });

    it("always reports it's value in the new object style", () => {
        insertListBlot("ordered");
        insertListBlot("bullet");

        const expected = [
            { insert: "list item" },
            {
                attributes: {
                    list: {
                        type: ListType.ORDERED,
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
        insertListBlot({ type: ListType.ORDERED, depth: 0 });
        expect(quill.getContents().ops).deep.equals([
            { insert: "list item" },
            {
                attributes: {
                    list: {
                        type: ListType.ORDERED,
                        depth: 0,
                    },
                },
                insert: "\n",
            },
        ]);
        quill.formatLine(0, 1, ListItem.blotName, {
            type: ListType.ORDERED,
            depth: 1,
        });
        expect(quill.getContents().ops).deep.equals([
            { insert: "list item" },
            {
                attributes: {
                    list: {
                        type: ListType.ORDERED,
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

    describe("nests items properly", () => {
        it("different types", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 });
            insertListBlot({ type: ListType.ORDERED, depth: 0 });

            expect(quill.scroll.children).has.length(2);
            quill.scroll.children.forEach((blot: ListGroup) => {
                expect(blot).instanceOf(ListGroup);
                expect(blot.children).has.length(1);
                expect(blot.children.head).instanceOf(ListItemWrapper);
            });
        });

        it("different levels", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 });
            insertListBlot({ type: ListType.BULLETED, depth: 0 });
            insertListBlot({ type: ListType.BULLETED, depth: 1 });
            insertListBlot({ type: ListType.BULLETED, depth: 1 });
            insertListBlot({ type: ListType.BULLETED, depth: 0 });

            // The inner items should be
            // - list item
            // - list item
            //   - list item
            //   - list item
            // - list item

            expect(quill.scroll.children).has.length(1);
            const outerUL = quill.scroll.children.head as ListGroup;
            expect(outerUL).instanceOf(ListGroup);
            expect(outerUL.children).has.length(3);
            const secondChild = outerUL.children.head!.next as ListItemWrapper;
            expect(secondChild.getListGroup()).instanceOf(ListGroup);
        });

        it("can nest different types multiple levels deep", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 });
            insertListBlot({ type: ListType.ORDERED, depth: 1 });
            insertListBlot({ type: ListType.ORDERED, depth: 1 });
            insertListBlot({ type: ListType.BULLETED, depth: 2 });
            insertListBlot({ type: ListType.BULLETED, depth: 3 });
            insertListBlot({ type: ListType.ORDERED, depth: 4 });
            insertListBlot({ type: ListType.BULLETED, depth: 2 });
            insertListBlot({ type: ListType.ORDERED, depth: 0 });

            // The inner items should be
            // - list item
            //   1. list item
            //   2. list item
            //      - list item
            //         - list item
            //           - list item
            //      - list item
            // 1. list item

            expect(quill.scroll.children).has.length(2);

            const depth0UL = quill.scroll.children.head as ListGroup;
            expect(depth0UL).instanceOf(UnorderedListGroup);
            expect(depth0UL.children).has.length(1);
            const depth0LI = depth0UL.children.head as ListItem;

            const depth1OL = depth0LI.children.tail as OrderedListGroup;
            expect(depth1OL).instanceOf(OrderedListGroup);
            expect(depth1OL.children).has.length(2);
            const depth1LI = depth1OL.children.head!.next as ListItem;
            expect(depth1LI).instanceOf(ListItemWrapper);

            const depth2UL = depth1LI.children.tail as ListGroup;
            expect(depth2UL).instanceOf(UnorderedListGroup);
            expect(depth2UL.children).has.length(2);
            const depth2LI = depth2UL.children.head as ListItem;

            const depth3UL = depth2LI.children.tail as ListGroup;
            expect(depth3UL).instanceOf(UnorderedListGroup);
            expect(depth3UL.children).has.length(1);
            const depth3LI = depth3UL.children.head as ListItem;

            const depth4UL = depth3LI.children.tail as ListGroup;
            expect(depth4UL).instanceOf(OrderedListGroup);
            expect(depth4UL.children).has.length(1);
        });

        it("item depth can only be > 0 if there are parent items immediately above them.", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 2 });

            expect(quill.scroll.children).has.length(1);
            const UL = quill.scroll.children.head as UnorderedListGroup;
            expect(UL).instanceOf(UnorderedListGroup);
            expect(UL.children).has.length(1);
        });
    });

    describe("indent", () => {
        it("does nothing if we don't have a previous list item to merge into", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 });

            const listGroup = quill.scroll.children.head as ListGroup;
            const listItem = listGroup.children.head as ListItemWrapper;
            const contentBefore = quill.getContents().ops;

            listItem.getListContent()!.indent();
            quill.update();

            expect(quill.getContents().ops).deep.equals(contentBefore);
        });

        it("can indent an item into the item before it.", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 });
            insertListBlot({ type: ListType.BULLETED, depth: 0 });

            const listGroup = quill.scroll.children.head as ListGroup;
            let listItem = listGroup.children.tail as ListItemWrapper;

            expect(listGroup.children, "List group should have 2 children to start").has.length(2);
            expect(listItem.children, "List item should have 1 child to start").has.length(1);

            listItem.getListContent()!.indent();
            quill.update();

            // Refetch required due to the optimizations that may have occured on listItem.
            listItem = listGroup.children.tail as ListItemWrapper;

            expect(listGroup.children, "List group should have 1 child after").has.length(1);
            expect(listItem.children, "List item should have 2 children after").has.length(2);

            const secondListItem = listItem.children.tail as ListGroup;
            expect(secondListItem).instanceOf(ListGroup);
            expect(secondListItem.getValue().depth, "The first list item should contain a list group").eq(1);
        });

        it("can indent an item into it's own nest list of the same type", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 }, "1");
            insertListBlot({ type: ListType.BULLETED, depth: 0 }, "1.1");
            insertListBlot({ type: ListType.BULLETED, depth: 1 }, "1.2");

            const listGroup = quill.scroll.children.head as ListGroup;
            let listItem = listGroup.children.tail as ListItemWrapper;

            listItem.getListContent()!.indent();
            quill.update();

            // Expected
            // - listItem
            //   - listItem
            //   - listItem

            expect(listGroup.children, "Only top level list item should remain").has.length(1);
            listItem = listGroup.children.head as ListItemWrapper;
            expect(listItem.children.tail, "The first list item should contain a list group").instanceOf(
                UnorderedListGroup,
            );
            const nestedListGroup = listItem.children.tail as UnorderedListGroup;
            expect(nestedListGroup.children, "There should be 2 nested list items").has.length(2);
        });
    });

    describe("replacement", () => {
        it("can have it's format replaced", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 }, "1");
            insertListBlot({ type: ListType.BULLETED, depth: 1 }, "1.1");
            insertListBlot({ type: ListType.BULLETED, depth: 1 }, "1.2");

            quill.formatLine(0, 1, ListItem.blotName, false, Quill.sources.USER);
            expect(quill.getContents().ops).deep.eq([
                OpUtils.op("1\n1.1"),
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("1.2"),
                OpUtils.list(ListType.BULLETED),
            ]);
        });

        it("propert outdents nested children when it's format is replaced", () => {
            // Before
            // - 1
            //   - 1.1
            //   - 1.2
            //     - 1.2.1
            //     - 1.2.2
            //   - 1.3
            insertListBlot({ type: ListType.BULLETED, depth: 0 }, "1");
            insertListBlot({ type: ListType.BULLETED, depth: 1 }, "1.1");
            insertListBlot({ type: ListType.BULLETED, depth: 1 }, "1.2");
            insertListBlot({ type: ListType.BULLETED, depth: 2 }, "1.2.1");
            insertListBlot({ type: ListType.BULLETED, depth: 2 }, "1.2.2");
            insertListBlot({ type: ListType.BULLETED, depth: 1 }, "1.3");

            quill.formatLine(6, 2, ListItem.blotName, false, Quill.sources.USER);
            // After
            // - 1
            //   - 1.1
            // 1.2
            // - 1.2.1
            // - 1.2.2
            // - 1.3

            expect(quill.getContents().ops).deep.eq([
                OpUtils.op("1"),
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("1.1"),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op("1.2\n1.2.1"),
                OpUtils.list(ListType.BULLETED, 0),
                OpUtils.op("1.2.2"),
                OpUtils.list(ListType.BULLETED, 0),
                OpUtils.op("1.3"),
                OpUtils.list(ListType.BULLETED, 0),
            ]);
        });
    });

    /* 
        Press enter once; position in the line: last;
    */

    describe("newline insertion", () => {
        it("can insert newlines midline", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 }, "1234");
            quill.insertText(2, "\n");
            /* 
                - 1234

                After:
                - 12
                - 34
            
            */
            expect(quill.getContents().ops).deep.eq([
                OpUtils.op("12"),
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("34"),
                OpUtils.list(ListType.BULLETED),
            ]);
        });

        it("can insert newlines, end of the line", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 }, "1234");
            quill.insertText(4, "\n");
            /* 
                - 1234
    
                After:
                - 1234
                - 
            */

            expect(quill.getContents().ops).deep.eq([OpUtils.op("1234"), OpUtils.list(ListType.BULLETED, 0, "\n\n")]);
        });
    });
});
