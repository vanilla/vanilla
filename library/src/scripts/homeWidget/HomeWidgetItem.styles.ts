/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import {
    absolutePosition,
    backgroundHelper,
    borders,
    BorderType,
    colorOut,
    EMPTY_BACKGROUND,
    EMPTY_FONTS,
    EMPTY_SPACING,
    fonts,
    IBackground,
    paddings,
    clickableItemStates,
    unit,
    linkStyleFallbacks,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export enum HomeWidgetItemContentType {
    TITLE = "title",
    TITLE_DESCRIPTION = "title-description",
    TITLE_DESCRIPTION_IMAGE = "title-description-image",
}

export interface IHomeWidgetItemOptions {
    borderType?: BorderType;
    background?: IBackground;
    contentType?: HomeWidgetItemContentType;
    fg?: string;
}

export const homeWidgetItemVariables = useThemeCache((optionOverrides?: IHomeWidgetItemOptions) => {
    const makeVars = variableFactory("homeWidgetItem");
    const globalVars = globalVariables();
    const layoutVars = layoutVariables();

    let options = makeVars(
        "options",
        {
            contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION,
            borderType: BorderType.NONE,
            background: {
                ...EMPTY_BACKGROUND,
            },
            fg: globalVars.mainColors.fg,
        },
        optionOverrides,
    );

    const hasImage = options.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE;

    options = makeVars(
        "options",
        {
            ...options,
            borderType: hasImage ? BorderType.SHADOW : options.borderType,
        },
        optionOverrides,
    );

    options = makeVars(
        "options",
        {
            ...options,
            background: {
                color: options.borderType !== BorderType.NONE ? globalVars.mainColors.bg : undefined,
            },
        },
        optionOverrides,
    );

    const sizing = makeVars("sizing", {
        minWidth: layoutVars.contentSizes.full / 4 - layoutVars.gutter.size * 5, // Min width allows 4 items to fit.
    });

    const name = makeVars("name", {
        font: {
            ...EMPTY_FONTS,
        },
    });

    const hasBorder = [BorderType.BORDER, BorderType.SHADOW].includes(options.borderType);

    const spacing = makeVars("spacing", {
        contentPadding: {
            ...EMPTY_SPACING,
            vertical: hasBorder || hasImage ? 24 : 0,
            horizontal: hasBorder ? 16 : 0,
        },
    });

    const image = makeVars("image", {
        ratio: {
            height: 10,
            width: 16,
        },
        maxHeight: 250,
    });

    return { options, sizing, name, spacing, image };
});

export const homeWidgetItemClasses = useThemeCache((optionOverrides?: IHomeWidgetItemOptions) => {
    const vars = homeWidgetItemVariables(optionOverrides);
    const globalVars = globalVariables();
    const style = styleFactory("homeWidgetItem");

    const borderStyling: NestedCSSProperties = (() => {
        switch (vars.options.borderType) {
            case BorderType.SHADOW_AS_BORDER:
            case BorderType.NONE:
                return {};
            case BorderType.BORDER:
                return {
                    ...borders(),
                    $nest: {
                        "&:hover, &:focus": {
                            ...borders({
                                color: globalVars.border.colorHover,
                            }),
                        },
                    },
                };
            case BorderType.SHADOW:
                return {
                    borderRadius: globalVars.border.radius,
                    ...shadowHelper().embed(),
                    $nest: {
                        "&:hover, &:focus": {
                            ...shadowHelper().embedHover(),
                        },
                    },
                };
        }
    })();

    const buttonStateStyles = clickableItemStates();

    const name = style("name", {
        color: colorOut(vars.options.fg),
        ...fonts(vars.name.font),
        // ...linkStyleFallbacks,
        marginBottom: unit(globalVars.gutter.half),
    });

    const root = style(
        {
            height: percent(100),
            display: "block",
            ...backgroundHelper(vars.options.background),
            color: colorOut(vars.options.fg),
            overflow: "hidden",
            minWidth: unit(vars.sizing.minWidth),
            $nest: {
                [`&:hover .${name}`]: buttonStateStyles.$nest["&&:hover"],
                [`&:focus .${name}`]: buttonStateStyles.$nest["&&:focus"],
                [`&:focus-visible .${name}`]: buttonStateStyles.$nest["&&:focus-visible"],
                [`&:active .${name}`]: buttonStateStyles.$nest["&&:active"],
            },
        },
        borderStyling,
    );

    const content = style("content", {}, paddings(vars.spacing.contentPadding));

    const imageAspectRatio = percent((vars.image.ratio.height / vars.image.ratio.width) * 100);

    const imageContainer = style("imageContainer", {
        background: colorOut(globalVars.mixPrimaryAndBg(0.08)),
        width: percent(100),
        paddingTop: imageAspectRatio,
        position: "relative",
        maxHeight: unit(vars.image.maxHeight),
    });

    const image = style("image", {
        ...absolutePosition.fullSizeOfParent(),
        objectFit: "cover",
        objectPosition: "center center",
    });

    const description = style("description", {
        lineHeight: globalVars.lineHeights.base,
    });

    return { root, name, content, imageContainer, image, description };
});
