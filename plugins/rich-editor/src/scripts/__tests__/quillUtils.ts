/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import registerQuill from "@rich-editor/quill/registerQuill";
import Quill from "quill/core";

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
