/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MyValue, MyRootBlock } from "@library/vanilla-editor/typescript";
import { ELEMENT_DEFAULT } from "@udecode/plate-common";
import isEqual from "lodash-es/isEqual";

export const EMPTY_RICH2_PARAGRAPH: MyRootBlock = {
    type: ELEMENT_DEFAULT,
    children: [{ text: "" }],
};

export const EMPTY_RICH2_BODY: MyValue = [EMPTY_RICH2_PARAGRAPH];

export function isEmptyRich2(body: MyValue): boolean {
    return (
        isEqual(body, EMPTY_RICH2_BODY) ||
        (Array.isArray(body) &&
            body.length === 1 &&
            body[0].type === ELEMENT_DEFAULT &&
            body[0].children.length === 1 &&
            body[0].children[0].text === "")
    );
}
