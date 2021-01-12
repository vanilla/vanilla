/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { BackgroundImageProperty } from "csstype";
import { important, percent, px, quote, url, viewHeight, viewWidth } from "csx";
import { CSSObject } from "@emotion/css";
import { assetUrl, themeAsset } from "@library/utility/appUtils";
import { styleFactory } from "@library/styles/styleUtils";

export const getBackgroundImage = (image?: BackgroundImageProperty) => {
    if (!image) {
        return undefined;
    }
    image = image.toString();
    if (image.charAt(0) === "~") {
        // Relative path to theme folder
        image = themeAsset(image.substr(1, image.length - 1));
        return `url(${image})`;
    }

    if (image.startsWith("data:image/")) {
        return `url(${image})`;
    }

    if (image.startsWith("linear-gradient(")) {
        return image;
    }

    // Fallback to a general asset URL.
    return `url(${assetUrl(image)})`;
};

export const objectFitWithFallback = (): CSSObject => {
    return {
        position: "absolute",
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
        margin: "auto",
        height: "auto",
        width: percent(100),
        ...{
            "@supports (object-fit: cover)": {
                position: important("relative"),
                objectFit: "cover",
                objectPosition: "center",
                height: important(percent(100).toString()),
            },
        },
    };
};
export function fakeBackgroundFixed() {
    return {
        content: quote(""),
        display: "block",
        position: "fixed",
        top: px(0),
        left: px(0),
        width: viewWidth(100),
        height: viewHeight(100),
    };
}

export function centeredBackgroundProps() {
    return {
        backgroundPosition: `50% 50%`,
        backgroundRepeat: "no-repeat",
    };
}

export function centeredBackground() {
    const style = styleFactory("centeredBackground");
    return style(centeredBackgroundProps());
}
