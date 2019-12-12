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
    PositionProperty,
} from "csstype";
import { percent, px, quote, url, viewHeight, viewWidth } from "csx";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { assetUrl, themeAsset } from "@library/utility/appUtils";
import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
import { styleFactory } from "@library/styles/styleUtils";

export interface IBackground {
    color: ColorValues;
    attachment?: BackgroundAttachmentProperty;
    position?: BackgroundPositionProperty<TLength>;
    repeat?: BackgroundRepeatProperty;
    size?: BackgroundSizeProperty<TLength>;
    image?: BackgroundImageProperty;
    fallbackImage?: BackgroundImageProperty;
}

export const getBackgroundImage = (image?: BackgroundImageProperty, fallbackImage?: BackgroundImageProperty) => {
    // Get either image or fallback
    const workingImage = image ? image : fallbackImage;
    if (!workingImage) {
        return;
    }

    if (workingImage.charAt(0) === "~") {
        // Relative path to theme folder
        return themeAsset(workingImage.substr(1, workingImage.length - 1));
    }

    if (workingImage.startsWith("data:image/")) {
        return workingImage;
    }

    // Fallback to a general asset URL.
    const assetImage = assetUrl(workingImage);
    return assetImage;
};

export const background = (props: IBackground) => {
    const image = getBackgroundImage(props.image, props.fallbackImage);
    return {
        backgroundColor: props.color ? colorOut(props.color) : undefined,
        backgroundAttachment: props.attachment || undefined,
        backgroundPosition: props.position || `50% 50%`,
        backgroundRepeat: props.repeat || "no-repeat",
        backgroundSize: props.size || "cover",
        backgroundImage: image ? url(image) : undefined,
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
