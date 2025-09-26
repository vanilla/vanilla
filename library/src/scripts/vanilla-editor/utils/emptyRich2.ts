/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MyValue, MyRootBlock } from "@library/vanilla-editor/typescript";
import isEqual from "lodash-es/isEqual";

export const EMPTY_RICH2_PARAGRAPH: MyRootBlock = {
    type: "p",
    children: [{ text: "" }],
};

export const EMPTY_RICH2_BODY: MyValue = [EMPTY_RICH2_PARAGRAPH];

export function isEmptyRich2(body: MyValue): boolean {
    return (
        isEqual(body, EMPTY_RICH2_BODY) ||
        (Array.isArray(body) &&
            body.length === 1 &&
            body[0].type === "p" &&
            body[0].children.length === 1 &&
            body[0].children[0].text === "")
    );
}
