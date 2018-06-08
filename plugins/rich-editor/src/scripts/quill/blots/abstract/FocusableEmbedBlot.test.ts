/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import FocusableEmbedBlot from "./FocusableEmbedBlot";
import Quill, { RangeStatic } from "quill/core";
import { expect } from "chai";
import sinon from "sinon";
import { getBlotAtIndex } from "../../utility";

describe("FocusableEmbedBlot", () => {
    let quill: Quill;

    before(() => {
        Quill.register("formats/embed-focusable", FocusableEmbedBlot, true);
    });

    beforeEach(() => {
        document.body.innerHTML = `<div><div id="quill"></div></div>`;

        quill = new Quill("#quill");
    });

    const embedDelta = { insert: { [FocusableEmbedBlot.blotName]: true } };
    const newLineDelta = { insert: "\n" };

    describe("remove()", () => {
        it("will delete the currently active embed", () => {
            const embed = new FocusableEmbedBlot(FocusableEmbedBlot.create());
            quill.scroll.insertBefore(embed);

            const expectedContent = [newLineDelta];

            embed.remove();
            expect(quill.getContents().ops).deep.equals(expectedContent);
        });

        it("places the selection at the same position if it will be text", () => {
            const initialContent = [
                { insert: "\n" },
                {
                    insert: {
                        [FocusableEmbedBlot.blotName]: {},
                    },
                },
                { insert: "test\n" },
            ];

            const expectedContent = [
                {
                    insert: "\ntest\n",
                },
            ];

            quill.setContents(initialContent);

            const embed = getBlotAtIndex(quill, 1, FocusableEmbedBlot) as FocusableEmbedBlot;
            embed.remove();
            expect(quill.getContents().ops).deep.equals(expectedContent);
            expect(quill.getSelection().index).eq(1);
        });

        it("places the focuses an embed blot if it will be in the original position", () => {
            const initialContent = [newLineDelta, embedDelta, embedDelta];
            const expectedContent = [newLineDelta, embedDelta, newLineDelta];

            quill.setContents(initialContent);

            const embed = getBlotAtIndex(quill, 1, FocusableEmbedBlot) as FocusableEmbedBlot;
            const embed2 = getBlotAtIndex(quill, 2, FocusableEmbedBlot) as FocusableEmbedBlot;
            embed.remove();
            expect(quill.getContents().ops).deep.equals(expectedContent);
            expect(quill.getSelection().index).eq(1);
            expect(embed2.domNode).eq(document.activeElement);
        });
    });

    describe("insertNewlineAfter()", () => {
        it("inserts a newline after the selected element", () => {
            const initialContent = [newLineDelta, embedDelta, newLineDelta];
            const expectedContent = [newLineDelta, embedDelta, { insert: "\n\n" }];

            quill.setContents(initialContent);

            const embed = getBlotAtIndex(quill, 1, FocusableEmbedBlot) as FocusableEmbedBlot;
            embed.insertNewlineAfter();
            expect(quill.getContents().ops).deep.equals(expectedContent);
            expect(quill.getSelection().index).eq(2);
        });
    });

    describe("focus", () => {
        it("fires a selection event for it's position", () => {
            const spy = sinon.spy();
            quill.on("selection-change", (range: RangeStatic) => {
                spy(range);
            });
        });
    });
});
