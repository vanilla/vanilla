/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createVanillaEditor } from "@library/vanilla-editor/VanillaEditor.loadable";
import { MyEditor, MyNode } from "@library/vanilla-editor/typescript";
import { ELEMENT_CODE_BLOCK, ELEMENT_CODE_LINE } from "@udecode/plate-code-block";

describe("VanillaEditor Code Block", () => {
    function pasteCode(editor: MyEditor, data: any) {
        const dataTransfer = new DataTransfer();
        if (data.text) {
            dataTransfer.setData("text/plain", data.text);
        }
        if (data.html) {
            dataTransfer.setData("text/html", data.html);
        }

        editor.insertNodes({
            type: ELEMENT_CODE_BLOCK,
            children: [{ type: ELEMENT_CODE_LINE, children: [{ text: "" }] }],
        } as MyNode);

        editor.setSelection({
            anchor: {
                path: [0, 0, 0],
                offset: 0,
            },
        });

        editor.insertData(dataTransfer);
    }

    const expected = [
        {
            type: "code_block",
            children: [
                {
                    type: "code_line",
                    children: [{ text: "const myVar = {" }],
                },
                {
                    type: "code_line",
                    children: [{ text: "    prop: 'string'," }],
                },
                {
                    type: "code_line",
                    children: [{ text: "    val: 0," }],
                },
                {
                    type: "code_line",
                    children: [{ text: "};" }],
                },
            ],
        },
        { type: "p", children: [{ text: "" }] },
    ];

    it("Pastes code into a code block from plain text editor", () => {
        const editor = createVanillaEditor();
        pasteCode(editor, {
            text: "const myVar = {\n    prop: 'string',\n    val: 0,\n};",
        });
        expect(editor.children).toStrictEqual(expected);
    });

    it("Pastes code into a code block from VS Code", () => {
        const editor = createVanillaEditor();
        pasteCode(editor, {
            html: `<meta charset='utf-8'><div style="color: #cccccc;background-color: #1f1f1f;font-family: Consolas, monospace, Menlo, Monaco, 'Courier New', monospace;font-weight: normal;font-size: 14px;line-height: 21px;white-space: pre;"><div><span style="color: #569cd6;">const</span><span style="color: #cccccc;"> </span><span style="color: #4fc1ff;">myVar</span><span style="color: #cccccc;"> </span><span style="color: #d4d4d4;">=</span><span style="color: #cccccc;"> {</span></div><div><span style="color: #cccccc;">    </span><span style="color: #9cdcfe;">prop</span><span style="color: #9cdcfe;">:</span><span style="color: #cccccc;"> </span><span style="color: #ce9178;">'string'</span><span style="color: #cccccc;">,</span></div><div><span style="color: #cccccc;">    </span><span style="color: #9cdcfe;">val</span><span style="color: #9cdcfe;">:</span><span style="color: #cccccc;"> </span><span style="color: #b5cea8;">0</span><span style="color: #cccccc;">,</span></div><div><span style="color: #cccccc;">};</span></div></div>`,
            text: "const myVar = {\n    prop: 'string',\n    val: 0,\n};",
        });
        expect(editor.children).toStrictEqual(expected);
    });
});
