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
    ensureColorHelper,
    flexHelper,
    fonts,
    IBackground,
    isLightColor,
    paddings,
    unit,
    modifyColorBasedOnLightness,
    IBorderStyles,
    EMPTY_BORDER,
    ISimpleBorderStyle,
    margins,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, ColorHelper, calc } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { clickableItemStates, EMPTY_STATE_COLORS } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IThemeVariables } from "@library/theming/themeReducer";
const defaultIcon = require("!file-loader!./widgetDefaultIcon.svg").default;

export enum HomeWidgetItemContentType {
    TITLE = "title",
    TITLE_DESCRIPTION = "title-description",
    TITLE_DESCRIPTION_IMAGE = "title-description-image",
    TITLE_DESCRIPTION_ICON = "title-description-icon",
    TITLE_BACKGROUND = "title-background",
}

export interface IHomeWidgetItemOptions {
    borderType?: BorderType;
    borderRadius?: string | number;
    background?: IBackground;
    contentType?: HomeWidgetItemContentType;
    fg?: string | ColorHelper;
    display?: {
        description?: boolean;
        counts?: boolean;
    };
    verticalAlignment?: "top" | "middle" | "bottom" | string;
    alignment?: "center" | "left";
    viewMore?: {
        labelCode?: string;
        buttonType?: ButtonTypes;
    };
    defaultIconUrl?: string;
    defaultImageUrl?: string;
    iconProps?: {
        placement?: "top" | "left";
        background?: IBackground;
        border?: ISimpleBorderStyle;
    };
}

export const homeWidgetItemVariables = useThemeCache(
    (optionOverrides?: IHomeWidgetItemOptions, itemVars?: IThemeVariables) => {
        const makeVars = variableFactory("homeWidgetItem", itemVars);
        const globalVars = globalVariables(itemVars);
        const layoutVars = layoutVariables(itemVars);
        let options = makeVars(
            "options",
            {
                display: {
                    description: true,
                    counts: true,
                },
                contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON,
                borderType: BorderType.NONE,
                borderRadius: globalVars.border.radius,
                background: {
                    ...EMPTY_BACKGROUND,
                },
                fg: globalVars.mainColors.fg,
                viewMore: {
                    buttonType: ButtonTypes.TRANSPARENT,
                    labelCode: "View More",
                },
                verticalAlignment: "middle",
                alignment: "left" as "left" | "center",
                defaultIconUrl: defaultIcon as string | undefined,
                defaultImageUrl: undefined as string | undefined,
                iconProps: {
                    placement: "top" as "top" | "left",
                    background: {
                        ...EMPTY_BACKGROUND,
                    },
                    border: {
                        ...EMPTY_BORDER,
                        width: 0,
                    },
                },
            },
            optionOverrides,
        );

        const hasImage = options.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE;
        const hasBackground = options.contentType === HomeWidgetItemContentType.TITLE_BACKGROUND;
        const hasIcon = options.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON;
        const iconPlacementLeft = options.iconProps.placement === "left";

        options = makeVars(
            "options",
            {
                ...options,
                fg:
                    !options.background.color || isLightColor(ensureColorHelper(options.background.color))
                        ? globalVars.mainColors.fg
                        : globalVars.mainColors.bg,
                borderType:
                    options.background?.color || hasImage || hasBackground || hasIcon
                        ? BorderType.SHADOW
                        : options.borderType,
                alignment: !iconPlacementLeft && (hasIcon || hasBackground) ? "center" : "left",
            },
            optionOverrides,
        );

        options = makeVars(
            "options",
            {
                ...options,
                background: {
                    alignment: hasIcon ? "center" : "left",
                    color: options.borderType !== BorderType.NONE ? globalVars.mainColors.bg : undefined,
                },
            },
            optionOverrides,
        );

        const sizing = makeVars("sizing", {
            minWidth: layoutVars.contentSizes.full / 4 - layoutVars.gutter.size * 5, // Min width allows 4 items to fit.
        });

        const hasBorder = [BorderType.BORDER, BorderType.SHADOW].includes(options.borderType);

        const spacing = makeVars("spacing", {
            contentPadding: {
                ...EMPTY_SPACING,
                vertical: hasBorder || hasImage ? 24 : 0,
                top:
                    iconPlacementLeft && (!options.iconProps.background.color || !options.display.description)
                        ? 8
                        : hasIcon
                        ? 0
                        : undefined,
                bottom: hasIcon || hasBorder ? 8 : 0,
                horizontal: iconPlacementLeft ? 8 : hasBorder ? (hasIcon || hasBackground ? 24 : 16) : 0,
            },
        });

        const iconContainer = makeVars("iconContainer", {
            padding: iconPlacementLeft ? 8 : 24,
            borderRadius: options.iconProps.background ? globalVars.border.radius : undefined,
        });

        const icon = makeVars("icon", {
            size: iconPlacementLeft ? 48 : 72,
        });

        let background = makeVars("background", {
            fg: {
                color: globalVars.elementaryColors.white,
            },
            bg: {
                ...EMPTY_BACKGROUND,
            },
            scrim: {
                ...EMPTY_BACKGROUND,
            },
        });
        const isForegroundLight = background.fg.color.lightness() >= 0.5;
        background = makeVars("background", {
            ...background,
            scrim: {
                ...background.scrim,
                color: isForegroundLight
                    ? globalVars.elementaryColors.black.fade(0.3)
                    : globalVars.elementaryColors.white.fade(0.3),
            },
        });

        const name = makeVars("name", {
            font: {
                ...EMPTY_FONTS,
                color: options.fg,
                size: hasBackground ? globalVars.fonts.size.title : undefined,
                weight: hasBackground ? globalVars.fonts.weights.semiBold : undefined,
            },
        });

        const image = makeVars("image", {
            ratio: {
                height: hasBackground ? 16 : 9,
                width: 16,
            },
            maxHeight: hasBackground ? 400 : 250,
        });

        return { options, sizing, name, spacing, image, icon, iconContainer, background };
    },
);

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
                    borderRadius: vars.options.borderRadius,
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
    const isIconLeft = vars.options.iconProps.placement === "left";

    const name = style("name", {
        color: colorOut(vars.options.fg),
        ...fonts(vars.name.font),
        // ...linkStyleFallbacks,
        marginBottom: isIconLeft && !vars.options.display.description ? 0 : unit(globalVars.gutter.half),
    });

    const nestedStyles = buttonStateStyles.$nest ?? undefined;

    const root = style(
        {
            height: percent(100),
            ...backgroundHelper(vars.options.background),
            color: colorOut(vars.options.fg),
            overflow: "hidden",
            minWidth: unit(vars.sizing.minWidth),
            display: "flex",
            flexDirection: "column",
            $nest: {
                [`&:hover .${name}`]: nestedStyles && nestedStyles["&&:hover"] ? nestedStyles["&&:hover"] : undefined,
                [`&:focus .${name}`]: nestedStyles && nestedStyles["&&:focus"] ? nestedStyles["&&:focus"] : undefined,
                [`&:focus-visible .${name}`]:
                    nestedStyles && nestedStyles["&&:focus-visible"] ? nestedStyles["&&:focus-visible"] : undefined,
                [`&:active .${name}`]:
                    nestedStyles && nestedStyles["&&:active"] ? nestedStyles["&&:active"] : undefined,
            },
        },
        borderStyling,
    );

    const backgroundContainer = style("backgroundContainer", {
        position: "relative",
        ...backgroundHelper(vars.background.bg),
        flex: 1,
        display: isIconLeft ? "flex" : undefined,
        alignItems: isIconLeft ? (vars.options.display.description ? "flex-start" : "center") : undefined,
        paddingTop: isIconLeft ? 8 : undefined,
        paddingLeft: isIconLeft ? 8 : undefined,
    });

    const backgroundScrim = style("backgroundScrim", {
        ...absolutePosition.fullSizeOfParent(),
        ...backgroundHelper(vars.background.scrim),
    });

    const content = style(
        "content",
        {
            textAlign: vars.options.alignment,
        },
        paddings(vars.spacing.contentPadding),
    );

    const absoluteContent = style("absoluteContent", {
        ...absolutePosition.fullSizeOfParent(),
        ...(vars.options.alignment === "left" ? flexHelper().middleLeft() : flexHelper().middle()),
        flexDirection: "column",
        ...paddings(vars.spacing.contentPadding),
        paddingTop: 16,
        paddingBottom: 16,
        justifyContent: (() => {
            switch (vars.options.verticalAlignment) {
                case "top":
                    return "flex-start";
                case "bottom":
                    return "flex-end";
                default:
                    return "center";
            }
        })(),
        textAlign: vars.options.alignment,
    });

    const absoluteName = style("absoluteName", {
        ...fonts(vars.name.font),
        color: colorOut(vars.background.fg.color),
        marginBottom: 16,
    });

    const imageAspectRatio = percent((vars.image.ratio.height / vars.image.ratio.width) * 100);

    const imageContainer = style("imageContainer", {
        background: colorOut(globalVars.mixPrimaryAndBg(0.08)),
        width: percent(100),
        paddingTop: imageAspectRatio,
        position: "relative",
    });

    const imageContainerWrapper = style("imageContainerWrapper", {
        maxHeight: unit(vars.image.maxHeight),
        overflow: "hidden",
    });

    const image = style("image", {
        ...absolutePosition.fullSizeOfParent(),
        objectFit: "cover",
        objectPosition: "center center",
    });

    const iconContainer = style("iconContainer", {
        ...(vars.options.alignment === "left" ? flexHelper().middleLeft() : flexHelper().middle()),
        padding: vars.iconContainer.padding,
        ...backgroundHelper(vars.options.iconProps.background),
        borderRadius: vars.iconContainer.borderRadius ?? undefined,
        $nest: {
            "&:hover, &:focus": {
                backgroundColor: vars.options.iconProps.background.color
                    ? colorOut(
                          modifyColorBasedOnLightness({ color: vars.options.iconProps.background.color, weight: 0.1 }),
                      )
                    : undefined,
            },
        },
    });

    const icon = style("icon", {
        height: vars.icon.size,
        width: vars.icon.size,
        ...borders(vars.options.iconProps.border),
    });

    const metas = style("metas", {
        $nest: {
            "&&": {
                position: "relative",
                textAlign: vars.options.alignment,
                ...paddings({
                    ...vars.spacing.contentPadding,
                    top: 10,
                    bottom: 10,
                    left: isIconLeft
                        ? calc(`${unit(vars.icon.size + vars.spacing.contentPadding.horizontal * 4)}`)
                        : undefined,
                }),
            },
        },
    });

    const description = style("description", {
        lineHeight: globalVars.lineHeights.base,
        display: vars.options.display.description ? undefined : "none",
    });

    return {
        root,
        name,
        absoluteName,
        metas,
        content,
        backgroundScrim,
        backgroundContainer,
        absoluteContent,
        imageContainer,
        imageContainerWrapper,
        image,
        description,
        icon,
        iconContainer,
    };
});
