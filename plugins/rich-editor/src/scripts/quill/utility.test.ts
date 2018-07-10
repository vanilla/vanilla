/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as utility from "./utility";
import Quill from "@rich-editor/quill";
import { expect } from "chai";
import { getIDForQuill, insertBlockBlotAt } from "./utility";
import Parchment from "parchment";
import FocusableEmbedBlot from "@rich-editor/quill/blots/abstract/FocusableEmbedBlot";

const prettyNewline = (contents: string) => contents.replace(/\n/g, "↵ ");

describe("Range/Boundary conversions", () => {
    it("converts range to boundary", () => {
        const input = {
            index: 4,
            length: 10,
        };
        const output = {
            start: 4,
            end: 13,
        };

        expect(utility.convertRangeToBoundary(input)).deep.equals(output);
    });

    it("converts boundary to range", () => {
        const input = {
            start: 4,
            end: 13,
        };
        const output = {
            index: 4,
            length: 10,
        };

        expect(utility.convertBoundaryToRange(input)).deep.equals(output);
    });
});

describe("expandRange", () => {
    it("expands backwards", () => {
        const initialRange = {
            index: 4,
            length: 8,
        };

        const startRange = {
            index: 1,
            length: 5,
        };

        const expectedRange = {
            index: 1,
            length: 11,
        };

        expect(utility.expandRange(initialRange, startRange)).deep.equals(expectedRange);
    });
});

describe("getMentionRange", () => {
    const mentionInsert = {
        insert: {
            mention: {
                name: "adam",
                userID: 0,
            },
        },
    };

    const newline = { insert: "\n" };

    describe("gets a mention range in the first position", () => {
        const quill = new Quill(document.body);
        quill.setContents([
            {
                insert: "@Somebody",
            },
        ]);

        const description = quill.getText() + "- Index: ";
        const validIndexes = [2, 3, 4, 5, 6, 7, 8];

        const expected = {
            index: 0,
            length: 5,
        };

        validIndexes.forEach(index => {
            it(prettyNewline(description) + index, () => {
                expect(utility.getMentionRange(quill, index)).deep.equals({ index: 0, length: index });
            });
        });
    });

    describe("gets a mention range after an existing mention", () => {
        const quill = new Quill(document.body);
        quill.setContents([
            mentionInsert,
            {
                insert: " @Some",
            },
        ]);
        const description = quill.getText() + "- Index: ";

        const validIndexes = [4, 5, 6, 7];

        validIndexes.forEach(index => {
            it(prettyNewline(description) + index, () => {
                expect(utility.getMentionRange(quill, index)).deep.equals({ index: 2, length: index - 2 });
            });
        });
    });

    describe("gets a mention range on the third line", () => {
        const quill = new Quill(document.body);
        quill.setContents([
            mentionInsert,
            newline,
            mentionInsert,
            newline,
            {
                insert: "\n @Some\nOther inserts",
            },
        ]);

        const description = quill.getText() + "- Index: ";

        const validIndexes = [8, 9, 10];

        validIndexes.forEach(index => {
            it(prettyNewline(description) + index, () => {
                expect(utility.getMentionRange(quill, index)).deep.equals({ index: 6, length: index - 6 });
            });
        });
    });
});

describe("getIDForQuill()", () => {
    it("can generate an ID", () => {
        const quill = new Quill(document.createElement("div"));
        expect(getIDForQuill(quill)).to.be.a("string");
    });

    it("generates uniqueIds", () => {
        const quill1 = new Quill(document.createElement("div"));
        const quill2 = new Quill(document.createElement("div"));
        const id1 = getIDForQuill(quill1);
        const id2 = getIDForQuill(quill2);
        expect(id1).not.to.equal(id2);
    });

    it("generates id's consistently", () => {
        const quill1 = new Quill(document.createElement("div"));
        const id1 = getIDForQuill(quill1);
        const id2 = getIDForQuill(quill1);
        expect(id1).to.equal(id2);
    });
});

describe("insertBlockBlotAt()", () => {
    it("can split a line in the middle", () => {
        const content = [{ insert: "\n\n\n1234567890\n" }];
        const expected = [
            {
                insert: "\n\n\n12345\n",
            },
            {
                insert: {
                    "embed-focusable": true,
                },
            },
            {
                insert: "67890\n",
            },
        ];

        const newBlot = new FocusableEmbedBlot(FocusableEmbedBlot.create());
        const quill = new Quill(document.body);
        quill.setContents(content);

        insertBlockBlotAt(quill, 8, newBlot);
        quill.update();

        expect(quill.getContents().ops).deep.equals(expected);
    });
});
