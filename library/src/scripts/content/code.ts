/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onContent } from "@library/utility/appUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

type HLJS = typeof import("highlight.js");

export function initCodeHighlighting() {
    void highlightCodeBlocks();
    onContent(() => void highlightCodeBlocks());
}

export async function highlightCodeBlocks(domNode: HTMLElement = document.body) {
    const hljs = await importHLJS();
    const blocks = domNode.querySelectorAll(".code.codeBlock");
    blocks.forEach(hljs.highlightBlock);
}

/**
 * Highlight some text and return HTML for it.
 *
 * @param text Some text to highlight.
 */
export async function highlightText(text: string): Promise<string> {
    const hljs = await importHLJS();
    return hljs.highlightAuto(text).value;
}

let requestPromise: Promise<HLJS> | null = null;

/**
 * Ensure we only import this once.
 */
function importHLJS(): Promise<HLJS> {
    if (requestPromise !== null) {
        return requestPromise;
    }

    const innerImport = async () => {
        // Lazily initialize this because it can be rather heavy.
        const hljs = await import("highlight.js" /* webpackChunkName: "highlightJs" */);

        // Start fetching the styles.
        const vars = globalVariables();
        const useLight = vars.mainColors.bg.lightness() >= 0.5;
        if (useLight) {
            await import("./_codeLight.scss" /* webpackChunkName: "highlightJs-light" */ as any); // Sorry typescript.
        } else {
            await import("./_codeDark.scss" /* webpackChunkName: "highlightJs-dark" */ as any); // Sorry typescript.
        }

        return hljs;
    };

    requestPromise = innerImport();
    return requestPromise;
}
