/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { HTMLAttributes } from "react";

/**
 * Deconstructs all attributes from HTML element to recreate it in React
 * This helps to avoid using dangerouslySetInnerHTML
 *
 * @param element - The element to deconstruct
 * @param options - Additionnal options. By default, "class" is replaced with "className".
 * @returns object - the attributes of that element
 */
export function deconstructAttributesFromElement(
    element: Element,
    options?: {
        classAsClassName?: boolean;
        hrefAsTo?: boolean; // for SmartLinks/Link components
    },
): HTMLAttributes<any> {
    const { classAsClassName = true, hrefAsTo = false } = options || {};
    const attrs = {};
    Object.values(element.attributes).forEach(attr => {
        attrs[attr.name] = attr.value;
    });

    // If this is to be used in React, we want to replace "class" with "className"
    if (classAsClassName && attrs.hasOwnProperty("class")) {
        attrs["className"] = attrs["class"];
        delete attrs["class"];
    }

    // If this is to be used with <SmartLink/> OR <Link>. Do not use for regular <a/> tags.
    if (hrefAsTo && attrs.hasOwnProperty("href")) {
        attrs["to"] = attrs["href"];
        delete attrs["href"];
    }

    return attrs;
}
