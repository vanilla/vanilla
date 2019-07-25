/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import registerQuill from "@rich-editor/quill/registerQuill";
import Quill, { DeltaOperation } from "quill/core";
import { mountReact } from "@vanilla/react-utils";
import { Editor } from "@rich-editor/editor/Editor";
import EditorContent from "@rich-editor/editor/EditorContent";

/**
 * Add quill setup test utility.
 *
 * @param withTheme Whether or not the editor should be created with the full Vanilla UI.
 */
export function setupTestQuill(htmlBody?: string): Quill {
    registerQuill();
    document.body.innerHTML = htmlBody || `<form class="FormWrapper"><div id='quill' class="richEditor"></div></form>`;
    const mountPoint = document.getElementById("quill")!;
    const options = {
        theme: "vanilla",
    };
    const quill = new Quill(mountPoint, options);
    window.quill = quill;
    return quill;
}

/**
 * Setup a legacy textarea quill instance and pass return the instances.
 *
 * @param initialValue The initial text area value to use.
 */
export function setupLegacyEditor(
    initialValue: DeltaOperation[],
): Promise<{
    quill: Quill;
    textarea: HTMLTextAreaElement;
}> {
    document.body.innerHTML = `<form class="FormWrapper"><div class="richEditor" id="mount" /><textarea id="textarea"/></form>`;
    const mountLocation = document.getElementById("mount")!;
    const textarea = document.getElementById("textarea") as HTMLTextAreaElement;
    textarea.value = JSON.stringify(initialValue);

    return new Promise(resolve => {
        mountReact(
            <Editor isPrimaryEditor={true} isLoading={false} legacyMode={true} allowUpload={true}>
                <EditorContent legacyTextArea={textarea} />
            </Editor>,
            mountLocation,
            () => {
                setTimeout(() => {
                    resolve({ quill: window.quill as Quill, textarea });
                }, 500);
            },
        );
    });
}
