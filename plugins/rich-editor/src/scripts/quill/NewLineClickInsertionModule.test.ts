/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import OpUtils from "@rich-editor/__tests__/OpUtils";
import { setupTestQuill } from "@rich-editor/__tests__/quillUtils";
import { expect } from "chai";
import Quill from "quill/core";
import NewLineClickInsertionModule from "./NewLineClickInsertionModule";

describe("NewLineClickInsertionModule", () => {
    let quill: Quill;
    let insertModule: NewLineClickInsertionModule;

    beforeEach(() => {
        quill = setupTestQuill();
        insertModule = new NewLineClickInsertionModule(quill);
    });

    it("can insert a newline at the end of the document", () => {
        const ops = [OpUtils.op("test\n"), OpUtils.image("http://test.com/image.png")];
        quill.setContents(ops);
        // Quill automatically puts a newline at the end. Trim it off.
        quill.deleteText(quill.scroll.length() - 1, 1);

        const event = ({
            y: 10000,
            target: quill.root,
        } as any) as MouseEvent;

        insertModule.handleClick(event);
        expect(quill.getContents().ops).deep.eq([...ops, OpUtils.newline()]);
    });
    it("does nothing if there is no embed blot at the end", () => {
        const ops = [OpUtils.op("test\n")];
        quill.setContents(ops);

        const event = ({
            y: 10000,
            target: quill.root,
        } as any) as MouseEvent;

        insertModule.handleClick(event);
        expect(quill.getContents().ops).deep.eq(ops);
    });
    it("it only handles direct mouse events", () => {
        const ops = [OpUtils.op("test\n"), OpUtils.image("http://test.com/image.png")];
        quill.setContents(ops);
        // Quill automatically puts a newline at the end. Trim it off.
        quill.deleteText(quill.scroll.length() - 1, 1);

        const setOps = quill.getContents();

        const event = ({
            y: 10000,
            target: document.createElement("div"),
        } as any) as MouseEvent;

        insertModule.handleClick(event);
        expect(quill.getContents().ops).deep.eq(ops);
    });
});
