/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { CSSObject } from "@emotion/css";
import { TLength } from "@library/styles/cssUtilsTypes";
import { styleUnit } from "@library/styles/styleUnit";
import { srOnlyMixin } from "@vanilla/ui";
import { Property } from "csstype";
import { important, percent, px } from "csx";

export const internalAbsoluteMixins = {
    topRight: (top: string | number = "0", right: Property.Right<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as Property.Position,
            top: styleUnit(top),
            right: styleUnit(right),
        };
    },
    topLeft: (top: string | number = "0", left: Property.Left<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as Property.Position,
            top: styleUnit(top),
            left: styleUnit(left),
        };
    },
    bottomRight: (bottom: Property.Bottom<TLength> = px(0), right: Property.Right<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as Property.Position,
            bottom: styleUnit(bottom),
            right: styleUnit(right),
        };
    },
    bottomLeft: (bottom: Property.Bottom<TLength> = px(0), left: Property.Left<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as Property.Position,
            bottom: styleUnit(bottom),
            left: styleUnit(left),
        };
    },
    middleOfParent: (shrink: boolean = false): CSSObject => {
        if (shrink) {
            return {
                position: "absolute" as Property.Position,
                display: "inline-block",
                top: percent(50),
                left: percent(50),
                right: "initial",
                bottom: "initial",
                transform: "translate(-50%, -50%)",
            };
        } else {
            return {
                position: "absolute" as Property.Position,
                display: "block",
                top: 0,
                left: 0,
                right: 0,
                bottom: 0,
                maxHeight: percent(100),
                maxWidth: percent(100),
                margin: "auto",
            };
        }
    },
    middleLeftOfParent: (left: Property.Left<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as Property.Position,
            display: "block",
            top: 0,
            left,
            bottom: 0,
            maxHeight: percent(100),
            maxWidth: percent(100),
            margin: "auto 0",
        };
    },
    middleRightOfParent: (right: Property.Right<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as Property.Position,
            display: "block",
            top: 0,
            right,
            bottom: 0,
            maxHeight: percent(100),
            maxWidth: percent(100),
            margin: "auto 0",
        };
    },
    fullSizeOfParent: (): CSSObject => {
        return {
            display: "block",
            position: "absolute" as Property.Position,
            top: px(0),
            left: px(0),
            width: percent(100),
            height: percent(100),
        };
    },
    srOnly: (): CSSObject => {
        return srOnlyMixin();
    },
};
