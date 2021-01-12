/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    ListGroupBlot,
    OrderedListGroupBlot,
    UnorderedListGroupBlot,
} from "@rich-editor/quill/blots/lists/ListGroupBlot";
import { ListItemWrapperBlot } from "@rich-editor/quill/blots/lists/ListItemWrapperBlot";
import { ListLineBlot } from "@rich-editor/quill/blots/lists/ListLineBlot";
import { ListTag, ListType, ListValue } from "@rich-editor/quill/blots/lists/ListUtils";
import Formatter from "@rich-editor/quill/Formatter";
import registerQuill from "@rich-editor/quill/registerQuill";
import OpUtils from "@rich-editor/__tests__/OpUtils";
import { setupTestQuill } from "@rich-editor/__tests__/quillUtils";
import { expect } from "chai";
import Delta from "quill-delta";
import Quill from "quill/core";

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
        indent.addEventListener("click", (e) => {
            e.preventDefault();
            const formatter = new Formatter(quill, quill.getSelection());
            formatter.indentList();
        });
        outdent.addEventListener("click", (e) => {
            e.preventDefault();
            const formatter = new Formatter(quill, quill.getSelection());
            formatter.outdentList();
        });
        quillNode = quill.scroll.domNode as HTMLDivElement;
    };

    const insertListBlot = (listValue: ListValue, text: string = "list item"): ListLineBlot => {
        let delta = new Delta();
        if (quill.scroll.length() === 1) {
            delta = delta.delete(1);
        } else {
            delta = delta.retain(quill.scroll.length());
        }

        delta = delta.insert(text + "\n", { list: listValue });
        quill.updateContents(delta, Quill.sources.USER);
        quill.history.clear();
        const lastUL = quill.scroll.children.tail as UnorderedListGroupBlot;
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
        quill.formatLine(0, 1, ListLineBlot.blotName, {
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
        const testAutoJoining = (depth: number, type: ListType) => {
            insertListBlot({ type, depth });
            insertListBlot({ type, depth });
            insertListBlot({ type, depth });
            const listGroup = quill.scroll.children.tail as ListGroupBlot;
            expect(quill.scroll.children.tail).eq(quill.scroll.children.head);
            expect(listGroup).instanceOf(ListGroupBlot);
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
            quill.scroll.children.forEach((blot: ListGroupBlot) => {
                expect(blot).instanceOf(ListGroupBlot);
                expect(blot.children).has.length(1);
                expect(blot.children.head).instanceOf(ListItemWrapperBlot);
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
            const outerUL = quill.scroll.children.head as ListGroupBlot;
            expect(outerUL).instanceOf(ListGroupBlot);
            expect(outerUL.children).has.length(3);
            const secondChild = outerUL.children.head!.next as ListItemWrapperBlot;
            expect(secondChild.getListGroup()).instanceOf(ListGroupBlot);
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

            const depth0UL = quill.scroll.children.head as ListGroupBlot;
            expect(depth0UL).instanceOf(UnorderedListGroupBlot);
            expect(depth0UL.children).has.length(1);
            const depth0LI = depth0UL.children.head as ListLineBlot;

            const depth1OL = depth0LI.children.tail as OrderedListGroupBlot;
            expect(depth1OL).instanceOf(OrderedListGroupBlot);
            expect(depth1OL.children).has.length(2);
            const depth1LI = depth1OL.children.head!.next as ListLineBlot;
            expect(depth1LI).instanceOf(ListItemWrapperBlot);

            const depth2UL = depth1LI.children.tail as ListGroupBlot;
            expect(depth2UL).instanceOf(UnorderedListGroupBlot);
            expect(depth2UL.children).has.length(2);
            const depth2LI = depth2UL.children.head as ListLineBlot;

            const depth3UL = depth2LI.children.tail as ListGroupBlot;
            expect(depth3UL).instanceOf(UnorderedListGroupBlot);
            expect(depth3UL.children).has.length(1);
            const depth3LI = depth3UL.children.head as ListLineBlot;

            const depth4UL = depth3LI.children.tail as ListGroupBlot;
            expect(depth4UL).instanceOf(OrderedListGroupBlot);
            expect(depth4UL.children).has.length(1);
        });

        it("item depth can only be > 0 if there are parent items immediately above them.", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 2 });

            expect(quill.scroll.children).has.length(1);
            const UL = quill.scroll.children.head as UnorderedListGroupBlot;
            expect(UL).instanceOf(UnorderedListGroupBlot);
            expect(UL.children).has.length(1);
        });
    });

    describe("indent", () => {
        it("does nothing if we don't have a previous list item to merge into", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 });

            const listGroup = quill.scroll.children.head as ListGroupBlot;
            const listItem = listGroup.children.head as ListItemWrapperBlot;
            const contentBefore = quill.getContents().ops;

            listItem.getListContent()!.indent();
            quill.update();

            expect(quill.getContents().ops).deep.equals(contentBefore);
        });

        it("can indent an item into the item before it.", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 });
            insertListBlot({ type: ListType.BULLETED, depth: 0 });

            const listGroup = quill.scroll.children.head as ListGroupBlot;
            let listItem = listGroup.children.tail as ListItemWrapperBlot;

            expect(listGroup.children, "List group should have 2 children to start").has.length(2);
            expect(listItem.children, "List item should have 1 child to start").has.length(1);

            listItem.getListContent()!.indent();
            quill.update();

            // Refetch required due to the optimizations that may have occured on listItem.
            listItem = listGroup.children.tail as ListItemWrapperBlot;

            expect(listGroup.children, "List group should have 1 child after").has.length(1);
            expect(listItem.children, "List item should have 2 children after").has.length(2);

            const secondListItem = listItem.children.tail as ListGroupBlot;
            expect(secondListItem).instanceOf(ListGroupBlot);
            expect(secondListItem.getValue().depth, "The first list item should contain a list group").eq(1);
        });

        it("can indent an item into it's own nest list of the same type", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 }, "1");
            insertListBlot({ type: ListType.BULLETED, depth: 0 }, "1.1");
            insertListBlot({ type: ListType.BULLETED, depth: 1 }, "1.1.1");

            const listGroup = quill.scroll.children.head as ListGroupBlot;
            let listItem = listGroup.children.tail as ListItemWrapperBlot;

            listItem.getListContent()!.indent();
            quill.update();

            // Expected
            // - listItem
            //   - listItem
            //     - listItem
            const expected = [
                OpUtils.op("1"),
                OpUtils.list(ListType.BULLETED, 0),
                OpUtils.op("1.1"),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op("1.1.1"),
                OpUtils.list(ListType.BULLETED, 2),
            ];
            expect(quill.getContents().ops).deep.equals(expected);
        });
    });

    describe("replacement", () => {
        it("can change the type of a whole list row", () => {
            const input = [
                OpUtils.op("1"),
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("1.1"),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op("1.2"),
                OpUtils.list(ListType.BULLETED, 1),
            ];
            quill.setContents(input);

            const formatter = new Formatter(quill, { index: 3, length: 0 });
            formatter.orderedList();

            const expected = [
                OpUtils.op("1"),
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("1.1"),
                OpUtils.list(ListType.ORDERED, 1),
                OpUtils.op("1.2"),
                OpUtils.list(ListType.ORDERED, 1),
            ];

            expect(quill.getContents().ops).deep.eq(expected);
        });

        it("preserves indentation and changes siblings when replacing it's format.", () => {
            // Before
            // - 1
            //   - 1.1
            //   - 1.2
            //     - 1.2.1
            //     - 1.2.2
            //   - 1.3
            const input = [
                OpUtils.op("1"),
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("1.1"),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op("1.2"),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op("1.2.1"),
                OpUtils.list(ListType.BULLETED, 2),
                OpUtils.op("1.2.2"),
                OpUtils.list(ListType.BULLETED, 2),
                OpUtils.op("1.3"),
                OpUtils.list(ListType.BULLETED, 1),
            ];

            quill.setContents(input);

            // Format 1.2.
            let formatter = new Formatter(quill, { index: 6, length: 2 });
            formatter.orderedList();

            // After
            // - 1
            //   1. 1.1
            //   2. 1.2
            //     - 1.2.1
            //     - 1.2.2
            //   3. 1.3
            const expectedTypeChange = [
                OpUtils.op("1"),
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("1.1"),
                OpUtils.list(ListType.ORDERED, 1),
                OpUtils.op("1.2"),
                OpUtils.list(ListType.ORDERED, 1),
                OpUtils.op("1.2.1"),
                OpUtils.list(ListType.BULLETED, 2),
                OpUtils.op("1.2.2"),
                OpUtils.list(ListType.BULLETED, 2),
                OpUtils.op("1.3"),
                OpUtils.list(ListType.ORDERED, 1),
            ];

            expect(quill.getContents().ops).deep.eq(expectedTypeChange, "Didn't handle changing list type");

            // Swap for another element
            // Replace 1.1 with a blockquote.
            formatter = new Formatter(quill, { index: 5, length: 1 });
            formatter.blockquote();

            // After
            // - 1
            // > 1.1
            // - (empty)
            //   1. 1.2
            //     - 1.2.1
            //     - 1.2.2
            //   2. 1.3
            const expectedFormatChanged = [
                OpUtils.op("1"),
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("1.1"),
                OpUtils.quoteLine(),
                OpUtils.list(ListType.BULLETED), // Extra bullet is created to preserve depth.
                OpUtils.op("1.2"),
                OpUtils.list(ListType.ORDERED, 1),
                OpUtils.op("1.2.1"),
                OpUtils.list(ListType.BULLETED, 2),
                OpUtils.op("1.2.2"),
                OpUtils.list(ListType.BULLETED, 2),
                OpUtils.op("1.3"),
                OpUtils.list(ListType.ORDERED, 1),
            ];

            expect(quill.getContents().ops).deep.eq(expectedFormatChanged, "Didn't handle changing list formats");
        });

        it("can outdent in a nested list", () => {
            const nestedDelta = [
                OpUtils.op("Line 1"),
                OpUtils.list(ListType.BULLETED, 0),
                OpUtils.op("Line 1.1"),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op("Line 1.1.1"),
                OpUtils.list(ListType.BULLETED, 2),
                OpUtils.op("Line 1.1.2"),
                OpUtils.list(ListType.BULLETED, 2),
                OpUtils.op("Line 2"),
                OpUtils.list(ListType.BULLETED, 0),
            ];

            const expected = [
                OpUtils.op("Line 1"),
                OpUtils.list(ListType.BULLETED, 0),
                OpUtils.op("Line 1.1"),
                OpUtils.list(ListType.BULLETED, 0),
                OpUtils.op("Line 1.1.1"),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op("Line 1.1.2"),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op("Line 2"),
                OpUtils.list(ListType.BULLETED, 0),
            ];

            quill.setContents(nestedDelta);
            const listItem = quill.scroll.descendant((blot) => blot instanceof ListLineBlot, 8)[0] as ListLineBlot;
            expect(listItem?.domNode?.textContent === "Line 1.1");

            // Outdent 1.1. Expected that all children are outdented.
            const formatter = new Formatter(quill, { index: 8, length: 0 });
            formatter.outdentList();
            expect(quill.getContents().ops).deep.equals(expected);
        });

        it("can outdent list edge case", () => {
            const input = [
                OpUtils.op("Line 1"),
                OpUtils.list(ListType.BULLETED, 0),
                OpUtils.op("Line 1.1"),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op("Line 1.2"),
                OpUtils.list(ListType.BULLETED, 1),
            ];

            const expected = [
                OpUtils.op("Line 1"),
                OpUtils.list(ListType.BULLETED, 0),
                OpUtils.op("Line 1.1"),
                OpUtils.list(ListType.BULLETED, 0),
                OpUtils.op("Line 1.2"),
                OpUtils.list(ListType.BULLETED, 1),
            ];

            quill.setContents(input);

            // Outdent 1.1.
            const formatter = new Formatter(quill, { index: 8, length: 0 });
            formatter.outdentList();
            expect(quill.getContents().ops).deep.equals(expected, "Line 1.2 did not stay in the correct spot.");
        });
    });

    /*
        Press enter once; position in the line: last;
    */

    describe("newline insertion", () => {
        it("can insert newlines at the start of a line", () => {
            insertListBlot({ type: ListType.BULLETED, depth: 0 }, "1234");
            quill.insertText(0, "\n");
            /*
                - 1234

                After:
                -
                - 1234

            */
            expect(quill.getContents().ops).deep.eq([
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("1234"),
                OpUtils.list(ListType.BULLETED),
            ]);
        });

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

    it("can start at an arbitrary depth and get deleted properly", () => {
        // Insert a list item at depth 1.
        insertListBlot({ type: ListType.BULLETED, depth: 1 }, "Test");
        expect(quill.getContents().ops).deep.eq([OpUtils.op("Test"), OpUtils.list(ListType.BULLETED, 1)]);
        // Try to remove the list item.
        quill.setContents([OpUtils.op("\n")]);
        expect(quill.getContents().ops).deep.eq([OpUtils.op("\n")]);
    });
});
