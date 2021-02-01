/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    AlignItemsProperty,
    BottomProperty,
    DisplayProperty,
    FlexWrapProperty,
    JustifyContentProperty,
    LeftProperty,
    PositionProperty,
    RightProperty,
} from "csstype";
import { TLength } from "@library/styles/styleShim";
import { CSSObject } from "@emotion/css";
import { percent, px } from "csx";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const absolutePosition = {
    topRight: (top: string | number = "0", right: RightProperty<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as PositionProperty,
            top: styleUnit(top),
            right: styleUnit(right),
        };
    },
    topLeft: (top: string | number = "0", left: LeftProperty<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as PositionProperty,
            top: styleUnit(top),
            left: styleUnit(left),
        };
    },
    bottomRight: (bottom: BottomProperty<TLength> = px(0), right: RightProperty<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as PositionProperty,
            bottom: styleUnit(bottom),
            right: styleUnit(right),
        };
    },
    bottomLeft: (bottom: BottomProperty<TLength> = px(0), left: LeftProperty<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as PositionProperty,
            bottom: styleUnit(bottom),
            left: styleUnit(left),
        };
    },
    middleOfParent: (shrink: boolean = false): CSSObject => {
        if (shrink) {
            return {
                position: "absolute" as PositionProperty,
                display: "inline-block",
                top: percent(50),
                left: percent(50),
                right: "initial",
                bottom: "initial",
                transform: "translate(-50%, -50%)",
            };
        } else {
            return {
                position: "absolute" as PositionProperty,
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
    middleLeftOfParent: (left: LeftProperty<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as PositionProperty,
            display: "block",
            top: 0,
            left,
            bottom: 0,
            maxHeight: percent(100),
            maxWidth: percent(100),
            margin: "auto 0",
        };
    },
    middleRightOfParent: (right: RightProperty<TLength> = px(0)): CSSObject => {
        return {
            position: "absolute" as PositionProperty,
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
            position: "absolute" as PositionProperty,
            top: px(0),
            left: px(0),
            width: percent(100),
            height: percent(100),
        };
    },
};

export function sticky(): CSSObject {
    return {
        position: ["-webkit-sticky", "sticky"],
    };
}

export function flexHelper() {
    const middle = (wrap = false): CSSObject => {
        return {
            display: "flex" as DisplayProperty,
            alignItems: "center" as AlignItemsProperty,
            justifyContent: "center" as JustifyContentProperty,
            flexWrap: (wrap ? "wrap" : "nowrap") as FlexWrapProperty,
        };
    };

    const middleLeft = (wrap = false): CSSObject => {
        return {
            display: "flex" as DisplayProperty,
            alignItems: "center" as AlignItemsProperty,
            justifyContent: "flex-start" as JustifyContentProperty,
            flexWrap: wrap ? "wrap" : ("nowrap" as FlexWrapProperty),
        };
    };

    return { middle, middleLeft };
}

export function fullSizeOfParent(): CSSObject {
    return {
        position: "absolute",
        display: "block",
        top: px(0),
        left: px(0),
        width: percent(100),
        height: percent(100),
    };
}

export const inheritHeightClass = useThemeCache(() => {
    const style = styleFactory("inheritHeight");
    return style({
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
        position: "relative",
    });
});
