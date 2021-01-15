/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject } from "@emotion/css";
import { AppearanceProperty } from "csstype";
import { important } from "csx";

export const appearance = (value: AppearanceProperty = "none", isImportant: boolean = false): CSSObject => {
    const val = (isImportant ? important(value) : value) as any;
    return {
        WebkitAppearance: val,
        MozAppearance: val,
        appearance: val,
    };
};
