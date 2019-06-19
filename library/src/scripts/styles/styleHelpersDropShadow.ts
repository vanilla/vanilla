/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface IDropShadowShorthand {
    nonColorProps: string;
    color: string;
}

export interface IDropShadow {
    x: string;
    y: string;
    blur: string;
    spread: string;
    inset: boolean;
    color: string;
}

export const dropShadow = (vals: IDropShadowShorthand | IDropShadow | "none" | "initial" | "inherit") => {
    if (typeof vals !== "object") {
        return { dropShadow: vals };
    } else if ("nonColorProps" in vals) {
        return { dropShadow: `${vals.nonColorProps} ${vals.color.toString()}` };
    } else {
        const { x, y, blur, spread, inset, color } = vals;
        return {
            dropShadow: `${x ? x + " " : ""}${y ? y + " " : ""}${blur ? blur + " " : ""}${
                spread ? spread + " " : ""
            }${color}${inset ? " inset" : ""}`,
        };
    }
};
