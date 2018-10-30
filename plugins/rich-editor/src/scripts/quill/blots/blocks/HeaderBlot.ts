/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Header from "quill/formats/header";
import { hashString } from "@library/utility";

interface IValue {
    level: 2;
    ref: string;
}

export default class HeaderBlot extends Header {
    public static create(value: IValue | number) {
        console.log(value);
        let level;
        if (typeof value === "number") {
            level = value;
        } else {
            level = value.level;
        }

        const element = super.create(level) as Element;
        if (typeof value === "object" && value.ref) {
            element.setAttribute("data-id", value.ref);
        }
        return element;
    }

    private static calcUniqueID(val: string): string {
        val = val.replace(" ", "-");
        if (val.length > 50) {
            val = val.substring(0, 50) + "-" + hashString(val);
        }
        return encodeURIComponent(val);
    }

    public static formats(domNode: Element): IValue {
        return {
            level: super.formats(domNode),
            ref: this.calcUniqueID(domNode.textContent || ""),
        };
    }
}
