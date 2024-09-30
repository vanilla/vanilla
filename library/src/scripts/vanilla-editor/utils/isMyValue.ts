/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MyValue } from "@library/vanilla-editor/typescript";

/**
 * Body could be formatted for Quill.
 * This guard ensures we're dealing with Plates "MyValue" type instead.
 */
export const isMyValue = (body: any): body is MyValue => {
    return [body]
        .flat()
        .some((arrayEntry) => !Object.keys(arrayEntry).some((item) => /insert|delete|retain/g.test(item)));
};
