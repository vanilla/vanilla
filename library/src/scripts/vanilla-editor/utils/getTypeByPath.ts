/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EElementOrText, ELEMENT_DEFAULT, getBlockAbove, getFragment, Value } from "@udecode/plate-headless";

/**
 * Get the fragment type as string given an editor
 */
export function getTypeByPath(editor): string {
    const [, path] = getBlockAbove(editor) ?? [];
    if (path) {
        const fragment: Array<EElementOrText<Value>> = getFragment(editor, path);
        if (fragment?.[0]?.type) {
            return `${fragment[0].type}`;
        }
    }
    return ELEMENT_DEFAULT;
}
