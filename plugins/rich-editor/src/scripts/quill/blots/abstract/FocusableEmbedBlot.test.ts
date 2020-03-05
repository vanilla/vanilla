// /**
//  * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
//  * @copyright 2009-2019 Vanilla Forums Inc.
//  * @license GPL-2.0-only
//  */

// import Quill, { RangeStatic } from "quill/core";
// import { expect } from "chai";
// import sinon from "sinon";
// import FocusableEmbedBlot from "@rich-editor/quill/blots/abstract/SelectableEmbedBlot";
// import { getBlotAtIndex } from "@rich-editor/quill/utility";

// describe("FocusableEmbedBlot", () => {
//     let quill: Quill;
//     let button: HTMLElement;

//     before(() => {
//         Quill.register("formats/embed-focusable", FocusableEmbedBlot, true);
//     });

//     beforeEach(() => {
//         document.body.innerHTML = `<div><div id="quill"></div><button id="button"></button></div>`;
//         quill = new Quill("#quill");
//         button = document.getElementById("button")!;
//     });

//     const embedDelta = { insert: { [FocusableEmbedBlot.blotName]: true } };
//     const newLineDelta = { insert: "\n" };

//     describe("remove()", () => {
//         it("will delete the currently active embed", () => {
//             const embed = new FocusableEmbedBlot(FocusableEmbedBlot.create());
//             quill.scroll.insertBefore(embed);

//             const expectedContent = [newLineDelta];

//             embed.remove();
//             expect(quill.getContents().ops).deep.equals(expectedContent);
//         });

//         it("places the selection at the same position if it will be text if it was focused", () => {
//             const initialContent = [newLineDelta, embedDelta, { insert: "test\n" }];
//             const expectedContent = [{ insert: "\ntest\n" }];

//             quill.setContents(initialContent);

//             const embed = getBlotAtIndex(quill, 1, FocusableEmbedBlot) as FocusableEmbedBlot;
//             embed.focus();
//             embed.remove();
//             expect(quill.getContents().ops).deep.equals(expectedContent);
//             expect(quill.getSelection().index).eq(1);
//         });

//         it("places the focuses an embed blot if it will be in the original position if it was focused", () => {
//             const initialContent = [newLineDelta, embedDelta, embedDelta];
//             const expectedContent = [newLineDelta, embedDelta, newLineDelta];

//             quill.setContents(initialContent);

//             const embed = getBlotAtIndex(quill, 1, FocusableEmbedBlot) as FocusableEmbedBlot;
//             const embed2 = getBlotAtIndex(quill, 2, FocusableEmbedBlot) as FocusableEmbedBlot;
//             embed.focus();
//             embed.remove();
//             expect(quill.getContents().ops).deep.equals(expectedContent);
//             expect(quill.getSelection().index).eq(1);
//             expect(embed2.domNode).eq(document.activeElement);
//         });

//         it("doesn't change selection or refocus if it was not focused", () => {
//             const initialContent = [newLineDelta, embedDelta, { insert: "testas;ldkfjas;ldkfjas;\n" }];
//             quill.setContents(initialContent);
//             const embed = getBlotAtIndex(quill, 1, FocusableEmbedBlot) as FocusableEmbedBlot;
//             quill.setSelection(4, 0);
//             embed.remove();
//             expect(quill.getSelection().index).eq(3); // down by 1 because of the deletion.
//         });
//     });

//     describe("insertNewlineAfter()", () => {
//         it("inserts a newline after the selected element", () => {
//             const initialContent = [newLineDelta, embedDelta, newLineDelta];
//             const expectedContent = [newLineDelta, embedDelta, { insert: "\n\n" }];

//             quill.setContents(initialContent);

//             const embed = getBlotAtIndex(quill, 1, FocusableEmbedBlot) as FocusableEmbedBlot;
//             embed.insertNewlineAfter();
//             expect(quill.getContents().ops).deep.equals(expectedContent);
//             expect(quill.getSelection().index).eq(2);
//         });
//     });

//     describe("focus", () => {
//         it("fires a selection event for it's position", () => {
//             const spy = sinon.spy();
//             quill.on("selection-change", (range: RangeStatic) => {
//                 spy(range);
//             });
//         });
//     });
// });
