/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Quill from "quill/core";
import { expect } from "chai";
import {
    getIDForQuill,
    insertBlockBlotAt,
    convertRangeToBoundary,
    convertBoundaryToRange,
    getMentionRange,
    expandRange,
} from "@rich-editor/quill/utility";
import FocusableEmbedBlot from "@rich-editor/quill/blots/abstract/FocusableEmbedBlot";
import OpUtils from "@rich-editor/__tests__/OpUtils";
import { _executeReady } from "@library/utility/appUtils";
import { setupTestQuill } from "@rich-editor/__tests__/quillUtils";

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

        expect(convertRangeToBoundary(input)).deep.equals(output);
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

        expect(convertBoundaryToRange(input)).deep.equals(output);
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

        expect(expandRange(initialRange, startRange)).deep.equals(expectedRange);
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
    let quill: Quill;
    let button: HTMLButtonElement;
    beforeEach(() => {
        quill = setupTestQuill(`
            <form class="FormWrapper">
                <div id='quill' class="richEditor"></div>
                <button id="button"></button>
            </form
        `);
        button = document.getElementById("button") as HTMLButtonElement;
        quill.focus();
    });

    describe("gets a mention range in the first position", () => {
        const description = "@Somebody - Index: ";
        const validIndexes = [2, 3, 4, 5, 6, 7, 8];

        beforeEach(() => {
            quill.setContents([
                {
                    insert: "@Somebody",
                },
            ]);
        });

        validIndexes.forEach(index => {
            it(prettyNewline(description) + index, () => {
                expect(getMentionRange(quill, { index, length: 0 })).deep.equals({ index: 0, length: index });
            });
        });
    });

    describe("gets a mention range after an existing mention", () => {
        const description = "@some - Index: ";
        const validIndexes = [4, 5, 6, 7];

        beforeEach(() => {
            quill.setContents([
                mentionInsert,
                {
                    insert: " @Some",
                },
            ]);
        });

        validIndexes.forEach(index => {
            it(prettyNewline(description) + index, () => {
                expect(getMentionRange(quill, { index, length: 0 })).deep.equals({
                    index: 2,
                    length: index - 2,
                });
            });
        });
    });

    describe("gets a mention range on the third line", () => {
        const description = "@adam\n@adam\n\n @Some\nOther inserts - Index: ";
        const validIndexes = [8, 9, 10];

        beforeEach(() => {
            quill.setContents([
                mentionInsert,
                newline,
                mentionInsert,
                newline,
                {
                    insert: "\n @Some\nOther inserts",
                },
            ]);
        });

        validIndexes.forEach(index => {
            it(prettyNewline(description) + index, () => {
                expect(getMentionRange(quill, { index, length: 0 })).deep.equals({
                    index: 6,
                    length: index - 6,
                });
            });
        });
    });

    it("Returns even when quill is not focused.", () => {
        // Sanity check that this would otherwise be a valid mention.
        quill.setContents([{ insert: "@Somebody" }]);
        const selection = { index: 3, length: 0 };
        quill.setSelection(selection);
        expect(getMentionRange(quill, selection)).not.to.eq(null);

        // Actual check.
        button.focus();
        expect(getMentionRange(quill, selection)).not.to.eq(null);
    });

    it("Returns null when the selection length is greater than 0", () => {
        // Sanity check that this would otherwise be a valid mention.
        quill.setContents([{ insert: "@Somebody" }]);
        let selection = { index: 3, length: 0 };
        quill.setSelection(selection);
        expect(getMentionRange(quill, selection)).not.to.eq(null);

        // Actual check.
        button.focus();
        selection = { index: 3, length: 1 };
        quill.setSelection(selection);
        expect(getMentionRange(quill, selection)).to.eq(null);
    });

    it("Returns null if we are inside of a codeblock", () => {
        quill.setContents([{ insert: "@Somebody" }, OpUtils.codeBlock()]);
        const selection = { index: 3, length: 0 };
        quill.setSelection(selection);
        expect(getMentionRange(quill, selection)).to.eq(null);
    });
});

describe("getIDForQuill()", () => {
    it("can generate an ID", () => {
        const quill = setupTestQuill();
        expect(getIDForQuill(quill)).to.be.a("string");
    });

    it("generates uniqueIds", () => {
        const quill1 = setupTestQuill();
        const quill2 = setupTestQuill();
        const id1 = getIDForQuill(quill1);
        const id2 = getIDForQuill(quill2);
        expect(id1).not.to.equal(id2);
    });

    it("generates id's consistently", () => {
        const quill1 = setupTestQuill();
        const id1 = getIDForQuill(quill1);
        const id2 = getIDForQuill(quill1);
        expect(id1).to.equal(id2);
    });
});

describe("insertBlockBlotAt()", () => {
    it("can split a line in the middle", async () => {
        await _executeReady();
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
