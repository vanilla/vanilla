/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onContent } from "@library/utility/appUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

export function initCodeHighlighting() {
    void highlightCodeBlocks();
    onContent(() => void highlightCodeBlocks());
}

let wasRequested = false;
let hljs: any;
async function highlightCodeBlocks() {
    if (!wasRequested) {
        wasRequested = true;
        // Lazily initialize this because it can be rather heavy.
        hljs = await import("highlight.js" /* webpackChunkName: "highlightJs" */);

        // Start fetching the styles.
        const vars = globalVariables();
        const useLight = vars.mainColors.bg.lightness() >= 0.5;
        if (useLight) {
            await import("./_codeLight.scss" /* webpackChunkName: "highlightJs-light" */ as any); // Sorry typescript.
        } else {
            await import("./_codeDark.scss" /* webpackChunkName: "highlightJs-dark" */ as any); // Sorry typescript.
        }
    }

    doHighlighting();
}

function doHighlighting() {
    if (hljs) {
        const blocks = document.querySelectorAll(".code.codeBlock");
        blocks.forEach(hljs.highlightBlock);
    }
}
