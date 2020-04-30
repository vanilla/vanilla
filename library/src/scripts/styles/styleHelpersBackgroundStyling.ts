/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    BackgroundAttachmentProperty,
    BackgroundImageProperty,
    BackgroundPositionProperty,
    BackgroundRepeatProperty,
    BackgroundSizeProperty,
    ObjectFitProperty,
    OpacityProperty,
    PositionProperty,
} from "csstype";
import { percent, px, quote, url, viewHeight, viewWidth } from "csx";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { assetUrl, themeAsset } from "@library/utility/appUtils";
import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
import { styleFactory } from "@library/styles/styleUtils";

export interface IBackground {
    color?: ColorValues;
    attachment?: BackgroundAttachmentProperty;
    position?: BackgroundPositionProperty<TLength>;
    repeat?: BackgroundRepeatProperty;
    size?: BackgroundSizeProperty<TLength>;
    image?: BackgroundImageProperty;
    fallbackImage?: BackgroundImageProperty;
    opacity?: OpacityProperty;
    unsetBackground?: boolean; // do not apply background.
}

export const EMPTY_BACKGROUND: IBackground = {
    color: undefined,
    attachment: undefined,
    position: undefined,
    repeat: undefined,
    size: undefined,
    image: undefined,
    opacity: undefined,
    unsetBackground: false,
};

export const getBackgroundImage = (image?: BackgroundImageProperty) => {
    if (!image) {
        return undefined;
    }
    image = image.toString();
    if (image.charAt(0) === "~") {
        // Relative path to theme folder
        return themeAsset(image.substr(1, image.length - 1));
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

export const backgroundHelper = (props: IBackground) => {
    const image = getBackgroundImage(props.image);
    return {
        backgroundColor: props.color ? colorOut(props.color) : undefined,
        backgroundAttachment: props.attachment || undefined,
        backgroundPosition: props.position || `50% 50%`,
        backgroundRepeat: props.repeat || "no-repeat",
        backgroundSize: props.size || "cover",
        backgroundImage: image,
        opacity: props.opacity ?? undefined,
    };
};

export const objectFitWithFallback = () => {
    return {
        position: "absolute" as PositionProperty,
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
        margin: "auto",
        height: "auto",
        width: percent(100),
        $nest: {
            "@supports (object-fit: cover)": {
                objectFit: "cover" as ObjectFitProperty,
                objectPosition: "center",
                height: percent(100),
            },
        },
    } as NestedCSSProperties;
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
