/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { style } from "typestyle";
import { px, quote, viewWidth, viewHeight } from "csx";
import { BackgroundImageProperty } from "csstype";

export default function splashStyles() {
    const root = style({});
    return { root };
}

// To be moved
export function fakeBackgroundFixed() {
    return style({
        content: quote(""),
        display: "block",
        position: "fixed",
        top: px(0),
        left: px(0),
        width: viewWidth(100),
        height: viewHeight(100),
    });
}

// To be moved
const centeredBackground = () => {
    return {
        backgroundPosition: `50% 50%`,
        backgroundRepeat: "no-repeat",
    };
};

// To be moved
export function centreBackground() {
    return style(centeredBackground());
}

// To be moved
export function backgroundCover(backgroundImage: BackgroundImageProperty) {
    return style({
        ...centeredBackground(),
        backgroundSize: "cover",
        backgroundImage: backgroundImage.toString(),
    });
}
