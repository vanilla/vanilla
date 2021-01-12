/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { absolutePosition, BorderType, flexHelper } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { ISimpleBorderStyle, IFont, IBackground } from "@library/styles/cssUtilsTypes";
import { Variables } from "@library/styles/Variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { CSSObject } from "@emotion/css";
import { percent, ColorHelper, calc, color, rgba } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IButtonStates } from "@library/styles/styleHelpersButtons";
import { IThemeVariables } from "@library/theming/themeReducer";

const defaultIcon = require("!file-loader!./widgetDefaultIcon.svg").default;

export enum HomeWidgetItemContentType {
    TITLE = "title",
    TITLE_DESCRIPTION = "title-description",
    TITLE_DESCRIPTION_IMAGE = "title-description-image",
    TITLE_DESCRIPTION_ICON = "title-description-icon",
    TITLE_BACKGROUND = "title-background",
    TITLE_CHAT_BUBBLE = "title-chat-bubble",
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
    name?: {
        hidden?: boolean;
        font?: IFont;
        states?: any; //FIXME
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
        size?: number;
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
                name: {
                    hidden: false,
                    font: Variables.font({}),
                    states: Variables.clickable({}),
                },
                contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON,
                borderType: BorderType.NONE,
                borderRadius: globalVars.border.radius,
                background: Variables.background({}),
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
                    background: Variables.background({}),
                    border: Variables.border({
                        width: 0,
                    }),
                    size: undefined as number | undefined,
                },
            },
            optionOverrides,
        );

        const hasImage = options.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE;
        const hasBackground = options.contentType === HomeWidgetItemContentType.TITLE_BACKGROUND;
        const hasIcon = options.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON;
        const iconPlacementLeft = options.iconProps.placement === "left";
        const hasChatBubble = options.contentType === HomeWidgetItemContentType.TITLE_CHAT_BUBBLE;

        options = makeVars(
            "options",
            {
                ...options,
                overflow: hasChatBubble ? "visible" : "hidden",
                fg:
                    !options.background.color || ColorsUtils.isLightColor(options.background.color)
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
            contentPadding: Variables.spacing({
                vertical: hasBorder || hasImage ? 24 : 0,
                top:
                    iconPlacementLeft && (!options.iconProps.background.color || !options.display.description)
                        ? 8
                        : hasIcon
                        ? 0
                        : undefined,
                bottom: hasIcon || hasBorder ? 8 : 0,
                horizontal: iconPlacementLeft ? 8 : hasBorder ? (hasIcon || hasBackground ? 24 : 16) : 0,
            }),
        });

        const iconContainer = makeVars("iconContainer", {
            padding: iconPlacementLeft ? 8 : 24,
            borderRadius: options.iconProps.background ? globalVars.border.radius : undefined,
            hoverBackgroundColor: options.iconProps.background.color
                ? ColorsUtils.modifyColorBasedOnLightness({
                      color: options.iconProps.background.color,
                      weight: 0.1,
                  })
                : undefined,
        });

        const icon = makeVars("icon", {
            size: options.iconProps.size ? options.iconProps.size : iconPlacementLeft ? 48 : 72,
        });

        let background = makeVars("background", {
            fg: {
                color: globalVars.elementaryColors.white,
            },
            bg: Variables.background({}),
            scrim: Variables.background({}),
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
            font: Variables.font({
                color: options.name.font.color ?? options.fg,
                size: (() => {
                    if (options.name.font.size) {
                        return options.name.font.size;
                    }
                    // else if (hasBackground) {
                    //     return globalVars.fonts.size.title;
                    // }
                    return undefined;
                })(),
                weight: (() => {
                    if (options.name.font.weight) {
                        return options.name.font.weight;
                    } else if (hasBackground) {
                        return globalVars.fonts.weights.semiBold;
                    }
                    return undefined;
                })(),
            }),
            background: Variables.background({
                color: color("#fff"),
            }),
            afterContent: hasChatBubble ? "triangle" : "none",
            spacing: hasChatBubble
                ? Variables.spacing({
                      vertical: 50,
                      horizontal: 38,
                  })
                : Variables.spacing({}),
            states: Variables.clickable({
                hover: options.name.states.hover ?? undefined,
                focus: options.name.states.focus ?? undefined,
            }),
        });

        const callToAction = makeVars("callToAction", {
            padding: {
                ...name.spacing,
                top: 20,
            },
            font: Variables.font({
                transform: "uppercase",
                color: options.fg,
                size: 13,
                weight: globalVars.fonts.weights.semiBold,
            }),
        });

        const description = makeVars("description", {
            padding: {
                ...name.spacing,
                top: 0,
                bottom: 0,
            },
        });

        const image = makeVars("image", {
            ratio: {
                height: hasBackground ? 16 : 9,
                width: 16,
            },
            maxHeight: hasBackground ? 400 : 250,
        });

        return { options, sizing, name, callToAction, description, spacing, image, icon, iconContainer, background };
    },
);

export const homeWidgetItemClasses = useThemeCache((optionOverrides?: IHomeWidgetItemOptions) => {
    const vars = homeWidgetItemVariables(optionOverrides);
    const globalVars = globalVariables();
    const style = styleFactory("homeWidgetItem");

    const borderStyling: CSSObject = (() => {
        switch (vars.options.borderType) {
            case BorderType.SHADOW_AS_BORDER:
            case BorderType.NONE:
                return {};
            case BorderType.BORDER:
                return {
                    ...Mixins.border(),
                    ...{
                        "&:hover, &:focus": {
                            ...Mixins.border({
                                color: globalVars.border.colorHover,
                            }),
                        },
                    },
                };
            case BorderType.SHADOW:
                return {
                    borderRadius: vars.options.borderRadius,
                    ...shadowHelper().embed(),
                    ...{
                        "&:hover, &:focus": {
                            ...shadowHelper().embedHover(),
                        },
                    },
                };
        }
    })();

    const buttonStateStyles = vars.name.states.hover
        ? Mixins.clickable.itemState(vars.name.states)
        : Mixins.clickable.itemState();
    const isIconLeft = vars.options.iconProps.placement === "left";
    const isIconLeftAndDescriptionHidden = isIconLeft && !vars.options.display.description;
    const hasChatBubble = vars.options.contentType === HomeWidgetItemContentType.TITLE_CHAT_BUBBLE;
    const hasBubbleShadow = hasChatBubble && vars.options.borderType === BorderType.SHADOW;

    const bubbleTriangle: CSSObject =
        vars.name.afterContent === "triangle"
            ? {
                  ...(hasChatBubble ? Mixins.background(vars.name.background) : {}),
                  boxShadow: hasChatBubble ? "4px 4px 7px rgba(0,0,0, .05)" : undefined,
                  content: "''",
                  width: 20,
                  height: 20,
                  position: "absolute",
                  top: `calc(100% - ${10}px)`,
                  transform: `rotate(45deg)`,
                  left: 30,
              }
            : {};

    const linkState = Mixins.clickable.itemState({
        default: vars.name.font.color,
        allStates: ColorsUtils.offsetLightness(vars.name.font.color!, 0.05),
    });

    const name = style(
        "name",
        {
            ...Mixins.padding(vars.name.spacing),
            ...Mixins.font(vars.name.font),
            ...Mixins.background(vars.name.background),
            marginBottom: isIconLeftAndDescriptionHidden
                ? 0
                : hasChatBubble
                ? styleUnit(30)
                : styleUnit(globalVars.gutter.half),
            boxShadow: hasChatBubble ? "0 0px 15px rgba(0,0,0, .15)" : "none",
            position: "relative",
            ...{
                "&:after": bubbleTriangle,
            },
        },
        linkState,
    );

    const nestedStyles = buttonStateStyles;

    const root = style(
        {
            height: percent(100),
            ...Mixins.background(vars.options.background),
            color: ColorsUtils.colorOut(vars.options.fg),
            overflow: hasChatBubble ? "visible" : "hidden",
            minWidth: styleUnit(vars.sizing.minWidth),
            display: "flex",
            flexDirection: "column",
            ...{
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
        ...Mixins.background(vars.background.bg),
        flex: 1,
        display: isIconLeft ? "flex" : undefined,
        alignItems: isIconLeft ? (vars.options.display.description ? "flex-start" : "center") : undefined,
        paddingTop: isIconLeft ? 8 : undefined,
        paddingLeft: isIconLeft ? 8 : undefined,
    });

    const backgroundScrim = style("backgroundScrim", {
        ...absolutePosition.fullSizeOfParent(),
        ...Mixins.background(vars.background.scrim),
    });

    const content = style(
        "content",
        {
            textAlign: vars.options.alignment,
        },
        Mixins.padding(vars.spacing.contentPadding),
    );

    const absoluteContent = style("absoluteContent", {
        ...absolutePosition.fullSizeOfParent(),
        ...(vars.options.alignment === "left" ? flexHelper().middleLeft() : flexHelper().middle()),
        flexDirection: "column",
        ...Mixins.padding(vars.spacing.contentPadding),
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
        ...Mixins.font(vars.name.font),
        color: ColorsUtils.colorOut(vars.background.fg.color),
        marginBottom: 16,
    });

    const imageAspectRatio = percent((vars.image.ratio.height / vars.image.ratio.width) * 100);

    const imageContainer = style("imageContainer", {
        background: ColorsUtils.colorOut(globalVars.mixPrimaryAndBg(0.08)),
        width: percent(100),
        paddingTop: imageAspectRatio,
        position: "relative",
    });

    const imageContainerWrapper = style("imageContainerWrapper", {
        maxHeight: styleUnit(vars.image.maxHeight),
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
        ...Mixins.background(vars.options.iconProps.background),
        borderRadius: vars.iconContainer.borderRadius ?? undefined,
        ...{
            "&:hover, &:focus": {
                backgroundColor: vars.options.iconProps.background.color
                    ? ColorsUtils.colorOut(vars.iconContainer.hoverBackgroundColor)
                    : undefined,
            },
        },
    });

    const icon = style("icon", {
        height: vars.icon.size,
        // Width not set so that the aspect ratio of wider icons is preserved.
        ...Mixins.border(vars.options.iconProps.border),
    });

    const iconLeftPadding = calc(
        `${styleUnit(vars.icon.size + (vars.spacing.contentPadding.horizontal as number) * 4)}`,
    );

    const metas = style("metas", {
        ...Mixins.padding({
            ...vars.name.spacing,
        }),
        ...{
            "&&": {
                position: "relative",
                textAlign: vars.options.alignment,
                ...Mixins.padding({
                    ...vars.spacing.contentPadding,
                    top: 10,
                    bottom: 10,
                    left: isIconLeft ? iconLeftPadding : hasChatBubble ? "none" : undefined,
                }),
            },
        },
    });

    const callToAction = style("callToAction", {
        ...Mixins.padding({
            ...vars.callToAction.padding,
        }),
        ...Mixins.font(vars.callToAction.font),
        ...{
            svg: {
                marginLeft: 10,
            },
        },
    });

    const description = style("description", {
        lineHeight: globalVars.lineHeights.base,
        display: vars.options.display.description ? undefined : "none",
        paddingLeft: vars.name.spacing?.horizontal,
        paddingRight: vars.name.spacing?.horizontal,
    });

    return {
        root,
        name,
        callToAction,
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
