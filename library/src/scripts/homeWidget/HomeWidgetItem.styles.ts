/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { BorderType, flexHelper } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { ISimpleBorderStyle, IFont, IBackground, IBoxOptions, ISpacing } from "@library/styles/cssUtilsTypes";
import { Variables } from "@library/styles/Variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { activeSelector, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { css, CSSObject } from "@emotion/css";
import { percent, ColorHelper, calc, color, rgba } from "csx";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IThemeVariables } from "@library/theming/themeReducer";
import { LocalVariableMapping } from "@library/styles/VariableMapping";
import { DeepPartial } from "redux";

const defaultIcon = require("!file-loader!./widgetDefaultIcon.svg").default;

export enum HomeWidgetItemContentType {
    TITLE = "title",
    TITLE_DESCRIPTION = "title-description",
    TITLE_DESCRIPTION_IMAGE = "title-description-image",
    TITLE_DESCRIPTION_ICON = "title-description-icon",
    TITLE_BACKGROUND = "title-background",
    TITLE_BACKGROUND_DESCRIPTION = "title-background-description",
    TITLE_CHAT_BUBBLE = "title-chat-bubble",
}

export interface IHomeWidgetItemOptions {
    box: IBoxOptions;
    contentType: HomeWidgetItemContentType;
    fg: string | ColorHelper;
    display: {
        name: boolean;
        description: boolean;
        counts: boolean;
        cta: boolean;
    };
    verticalAlignment: "top" | "middle" | "bottom" | string;
    alignment: "center" | "left";
    viewMore: {
        labelCode: string;
        buttonType: ButtonTypes;
    };
    defaultIconUrl: string | undefined;
    defaultImageUrl: string | undefined;
    imagePlacement: "top" | "left";
    imagePlacementMobile: "top" | "left";
    callToActionText: string;
    /** @deprecated */
    iconProps?: any;
}

export const homeWidgetItemVariables = useThemeCache(
    (optionOverrides?: DeepPartial<IHomeWidgetItemOptions>, itemVars?: IThemeVariables) => {
        const makeVars = variableFactory("homeWidgetItem", itemVars, [
            new LocalVariableMapping({
                "icon.border": "options.iconProps.border",
                "icon.background": "options.iconProps.background",
                "icon.backgroundState.color": "iconContainer.hoverBackgroundColor",
                "icon.padding": "iconContainer.padding",
                "icon.border.radius": "iconContainer.borderRadius",
                "icon.size": "options.iconProps.size",
                "options.imagePlacement": "options.iconProps.placement",
                "options.box.background": "options.background",
                "options.box.borderType": "options.borderType",
                "options.box.border.radius": "options.borderRadius",
                "options.box.spacing": "spacing.contentPadding",
                "name.font": "options.name.font",
            }),
        ]);
        const globalVars = globalVariables(itemVars);
        const layoutVars = oneColumnVariables(itemVars);

        /**
         * @varGroup homeWidgetItem.options
         * @commonTitle Options
         * @description Within a HomeWidget, an item is an individual grid item. These items could represent a discussion, article, category etc.
         */
        let options: IHomeWidgetItemOptions = makeVars(
            "options",
            {
                /**
                 * @varGroup homeWidgetItem.options.box
                 * @expand box
                 */
                box: Variables.box({
                    borderType: BorderType.NONE,
                    border: globalVars.border,
                }),
                /**
                 * @var homeWidgetItem.options.contentType
                 * @description Choose the content and layout of the items.
                 * @type string
                 * @enum title-description | title-description-image | title-description-icon | title-background |title-background-description
                 */
                contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON,
                /**
                 * @varGroup homeWidgetItem.options.display
                 * @description Hide and show different parts of the widget.
                 */
                display: {
                    /**
                     * @var homeWidgetItem.options.display.name
                     * @type boolean
                     */
                    name: true,
                    /**
                     * @var homeWidgetItem.options.display.description
                     * @type boolean
                     */
                    description: true,
                    /**
                     * @var homeWidgetItem.options.display.counts
                     * @type boolean
                     */
                    counts: true,

                    /**
                     * @var homeWidgetItem.options.display.cta
                     * @type boolean
                     */
                    cta: true,
                },
                /**
                 * @var homeWidgetItem.options.fg
                 * @title Foreground Color
                 * @description Choose the color of the foreground content in the widget. Defaults to have contrast with the box background.
                 * @type string
                 * @format hex-color
                 */
                fg: globalVars.mainColors.fg,
                verticalAlignment: "middle",
                /**
                 * @var homeWidgetItem.options.alignment
                 * @description Configure the horizontal alignment of content in the widget
                 * @type string
                 * @enum left | center
                 */
                alignment: "left",
                viewMore: {
                    buttonType: ButtonTypes.TRANSPARENT,
                    labelCode: "View More",
                },
                /**
                 * @var homeWidgetItem.options.defaultIconUrl
                 * @type string
                 * @format url
                 */
                defaultIconUrl: defaultIcon,
                /**
                 * @var homeWidgetItem.options.defaultIconUrl
                 * @type string
                 * @format url
                 */
                defaultImageUrl: undefined,
                /**
                 * @var homeWidgetItem.options.imagePlacement
                 * @type string
                 * @description Choose the positioning of the icon or image in icon and image variants.
                 * @enum top | left
                 */
                imagePlacement: "top",
                /**
                 * @var homeWidgetItem.options.imagePlacement
                 * @type string
                 * @description Choose the positioning of the icon or image in icon and image variants
                 * on mobile device sizes.
                 * @enum top | left
                 */
                imagePlacementMobile: "top",
                /**
                 * @var homeWidgetItem.options.callToActionText
                 * @type string
                 * @description CTA text by default, used for chat bubble variant
                 */
                callToActionText: "Read More",

                /// Legacy Kludge. Because it's options, it can't be mapped.
                iconProps: {
                    placement: undefined as string | undefined,
                    background: Variables.background({}),
                    border: Variables.border({}),
                    size: undefined as number | undefined,
                },
            },
            optionOverrides,
        );

        const hasImage = options.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE;
        const hasBackground = [
            HomeWidgetItemContentType.TITLE_BACKGROUND,
            HomeWidgetItemContentType.TITLE_BACKGROUND_DESCRIPTION,
        ].includes(options.contentType);
        const hasIcon = options.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON;
        const hasChatBubble = options.contentType === HomeWidgetItemContentType.TITLE_CHAT_BUBBLE;

        options = makeVars(
            "options",
            {
                ...options,
                imagePlacement: options.iconProps?.placement ?? options.imagePlacement,
                imagePlacementMobile: options.iconProps?.placement ?? options.imagePlacementMobile,
                fg: globalVars.getFgForBg(options.box.background.color),
                box: {
                    ...options.box,
                    borderType:
                        options.box.background?.color || hasImage || hasBackground || hasIcon || hasChatBubble
                            ? BorderType.SHADOW
                            : options.box.borderType,
                },
                alignment: hasIcon || hasBackground ? "center" : "left",
            },
            optionOverrides,
        );
        const boxHasBorder = Variables.boxHasOutline(options.box);

        options = makeVars(
            "options",
            {
                ...options,
                box: {
                    ...options.box,
                    spacing: Variables.spacing({
                        all: boxHasBorder || hasChatBubble ? (hasChatBubble ? 24 : 16) : undefined,
                    }),
                },
            },
            optionOverrides,
        );

        const sizing = makeVars("sizing", {
            /**
             * @var homeWidgetItem.sizing.minWidth
             * @title Minimum Width
             * @type number
             * @description Apply a minimum width to HomeWidgetItems.
             */
            minWidth: 180,
        });

        const iconInit = makeVars("icon", {
            /**
             * @varGroup homeWidgetItem.icon.background
             * @description Background properties to apply to the icon of the widget item.
             * @expand background
             */
            background: Variables.background(options.iconProps.background ?? {}),

            /**
             * @var homeWidgetItem.icon.size
             * @title HomeWidgetItem Icon Size
             * @type number | string
             * @description Sets the size of the icon.
             */
            size: 72,
        });

        const icon = makeVars("icon", {
            ...iconInit,
            /**
             * @var homeWidgetItem.icon.padding
             * @title IconContainer Padding
             * @type number | string
             * @expand spacing
             */
            padding: 16,

            size: options.iconProps.size ?? (options.imagePlacement === "left" ? 48 : iconInit.size),
            sizeMobile: options.iconProps.size ?? (options.imagePlacementMobile === "left" ? 48 : iconInit.size),

            /**
             * @varGroup homeWidgetItem.icon.border
             * @description Apply a border around the icon.
             * @expand border
             */
            border: Variables.border({
                radius: options.iconProps.border.radius ?? globalVars.border.radius,
                width: options.iconProps.border.width ?? 0,
            }),
            /**
             * @varGroup homeWidgetItem.icon.background
             * @description Background properties to apply to the icon of the widget item when the item is hovered, focused or active.
             * @expand background
             */
            backgroundState: Variables.background({
                color: iconInit.background.color
                    ? ColorsUtils.modifyColorBasedOnLightness({
                          color: iconInit.background.color,
                          weight: 0.1,
                      })
                    : undefined,
            }),
        });

        let background = makeVars("background", {
            /**
             * @varGroup homeWidgetItem.background.fg
             * @title ForeGround Color
             */
            fg: {
                color: globalVars.elementaryColors.white,
            },

            /**
             * @varGroup homeWidgetItem.background.fg
             * @title Background Color
             * @expand background
             */
            bg: Variables.background({}),

            /**
             * @varGroup homeWidgetItem.background.scrim
             * @title Overlay Color
             * @expand background
             */
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
            /**
             * @varGroup homeWidgetItem.name.font
             * @expand font
             */
            font: Variables.font({
                ...globalVars.fontSizeAndWeightVars("large", "semiBold"),
                color: options.fg,
                textDecoration: "none",
            }),

            fontState: Variables.font({
                // Since we don't have a border to interact with, the name gets the interactive state.
                color: !boxHasBorder || hasChatBubble ? globalVars.mainColors.statePrimary : undefined,
            }),
            /**
             * @var homeWidgetItem.name.afterContent
             * @description Indicates whether there is content that appears after the HomeWidget Name
             * @type string
             * @title AfterContent
             */
            afterContent: hasChatBubble ? "triangle" : "none",

            /**
             * @varGroup homeWidgetItem.name.spacing
             * @commonTitle Name Spacing
             * @expand spacing
             */
            spacing: Variables.spacing({}),
        });

        const callToAction = makeVars("callToAction", {
            /**
             * @varGroup homeWidgetItem.callToAction.padding
             * @commonTitle Call To Action Padding
             * @expand spacing
             */
            padding: {
                ...options.box.spacing,
                top: 16,
            },

            /**
             * @varGroup homeWidgetItem.callToAction.font
             * @commonTitle Call To Action Font
             * @expand font
             */
            font: Variables.font({
                ...name.font,
                ...globalVars.fontSizeAndWeightVars("small", "semiBold"),
                transform: "uppercase",
            }),
            fontState: Variables.font({
                ...name.fontState,
                ...globalVars.fontSizeAndWeightVars("small", "semiBold"),
                transform: "uppercase",
            }),
        });

        const description = makeVars("description", {
            /**
             * @varGroup homeWidgetItem.description.spacing
             * @commonTitle Description Padding
             * @expand spacing
             */
            spacing: Variables.spacing({
                ...(hasChatBubble ? options.box.spacing : {}),
                top: 0,
                bottom: 0,
            }),

            /**
             * @varGroup homeWidgetItem.description.font
             * @commonTitle Description Font
             * @expand font
             */
            font: Variables.font({
                color: options.fg,
                lineHeight: globalVars.lineHeights.base,
            }),
        });

        const image = makeVars("image", {
            /**
             * @var homeWidgetItem.image.ratio
             * @description Aspect ratio of HomeWidgetItem image.
             * @title Aspect Ratio
             */
            ratio: {
                /**
                 * @var homeWidgetItem.image.ratio.height
                 * @title Image Height
                 * @description Height of HomeWidget Item Image
                 * @type number
                 */
                height: hasBackground ? 16 : 9,

                /**
                 * @var homeWidgetItem.image.ratio.width
                 * @title Image Width
                 * @description Widtht of HomeWidget Item Image
                 * @type number
                 */
                width: 16,
            },

            /**
             * @var homeWidgetItem.image.maxHeight
             * @description The maximum height of the HomeWidgetItem image.
             * @title Maximum Image Height
             * @type number
             */
            maxHeight: hasBackground ? 400 : 250,
        });

        return { options, sizing, name, callToAction, description, image, icon, background };
    },
);

export const homeWidgetItemClasses = useThemeCache((optionOverrides?: DeepPartial<IHomeWidgetItemOptions>) => {
    const vars = homeWidgetItemVariables(optionOverrides);
    const globalVars = globalVariables();
    const style = styleFactory("homeWidgetItem");
    const mobileQuery = oneColumnVariables().mediaQueries().oneColumnDown;
    const isImageLeft = vars.options.imagePlacement === "left";
    const isImageLeftMobile = vars.options.imagePlacementMobile === "left";
    const boxHasBorder = Variables.boxHasOutline(vars.options.box);
    const borderTypeIsSeparator = vars.options.box.borderType === BorderType.SEPARATOR;

    const hasChatBubble = vars.options.contentType === HomeWidgetItemContentType.TITLE_CHAT_BUBBLE;
    // const hasBubbleShadow = hasChatBubble && vars.options.box.borderType === BorderType.SHADOW;

    const bubbleTriangle: CSSObject =
        vars.name.afterContent === "triangle"
            ? {
                  ...(() => {
                      switch (vars.options.box.borderType) {
                          case BorderType.SHADOW:
                              return shadowHelper().embedTooltip();
                          case BorderType.BORDER:
                      }
                  })(),
                  ...Mixins.background({ color: vars.options.box.background.color ?? globalVars.mainColors.bg }),
                  //   ...Mixins.borderType(vars.options.box.borderType),
                  content: "''",
                  width: 20,
                  height: 20,
                  position: "absolute",
                  top: `calc(100% - ${10}px)`,
                  transform: `rotate(135deg)`,
                  left: 30,
                  display: "block !important",
              }
            : {};

    const root = style(
        {
            "--content-type": vars.options.contentType,
            height: percent(100),
            color: ColorsUtils.colorOut(vars.options.fg),
            overflow: hasChatBubble ? "visible" : "hidden",
            minWidth: styleUnit(vars.sizing.minWidth),
            display: "flex",
            flexDirection: "column",
        },
        !hasChatBubble && Mixins.box(vars.options.box, { noPaddings: true, interactiveOutline: true }),
        borderTypeIsSeparator && {
            "& + :before": {
                borderTop: "none",
            },
        },
    );

    const name = style(
        "name",
        {
            ...Mixins.padding(vars.name.spacing),
            ...Mixins.font(vars.name.font),
        },
        hasChatBubble
            ? {
                  ...Mixins.box(vars.options.box),
                  marginBottom: 30,
                  position: "relative",
                  "&:after": hasChatBubble ? bubbleTriangle : undefined,
              }
            : {
                  [activeSelector(`.${root}`, "&")]: Mixins.font(vars.name.fontState),
              },
    );

    const bgContainerLeftStyles: CSSObject = {
        alignItems: vars.options.display.description ? "flex-start" : "center",
        flexDirection: "row",
    };
    const backgroundContainer = style(
        "backgroundContainer",
        {
            position: "relative",
            ...Mixins.background(vars.background.bg),
            flex: "0 1 auto",
            display: "flex",
            flexDirection: "column",
            /**
             * TODO: Remove this kludge when IE 11 is deprecated
             * This minimum height stops this container from collapsing on itself
             */
            minHeight: "80px",
        },
        isImageLeft && bgContainerLeftStyles,
        isImageLeftMobile && mobileQuery(bgContainerLeftStyles),
    );

    const backgroundScrim = style("backgroundScrim", {
        ...Mixins.absolute.fullSizeOfParent(),
        ...Mixins.background(vars.background.scrim),
    });

    const textAlignment: CSSObject = {
        "&&": {
            textAlign: isImageLeft ? "left" : vars.options.alignment,
        },
    };

    const content = style(
        "content",
        { flex: 1, display: "flex", flexDirection: "column" },
        !hasChatBubble && Mixins.box(vars.options.box, { onlyPaddings: true }),
        textAlignment,
    );

    const absoluteContent = style(
        "absoluteContent",
        {
            ...Mixins.absolute.fullSizeOfParent(),
            ...(vars.options.alignment === "left" ? flexHelper().middleLeft() : flexHelper().middle()),
            flexDirection: "column",
            ...Mixins.box(vars.options.box, { onlyPaddings: true }),
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
        },
        textAlignment,
    );

    const absoluteName = style("absoluteName", {
        ...Mixins.font(vars.name.font),
        color: ColorsUtils.colorOut(vars.background.fg.color),
        marginBottom: 16,
        textAlign: vars.options.alignment,
    });

    const imageAspectRatio = percent((vars.image.ratio.height / vars.image.ratio.width) * 100);

    const imageContainerLeftStyles: CSSObject = {
        height: "100%",
    };
    const imageContainer = style(
        "imageContainer",
        {
            background: ColorsUtils.colorOut(globalVars.mixPrimaryAndBg(0.08)),
            width: percent(100),
            paddingTop: imageAspectRatio,
            position: "relative",
        },
        isImageLeft && imageContainerLeftStyles,
        isImageLeftMobile && mobileQuery(imageContainerLeftStyles),
    );

    const boxSpacing = vars.options.box.spacing;
    const imageContainerWrapperLeftStyles: CSSObject = {
        maxWidth: 200,
        height: 120,
        flexBasis: "33%",
        alignSelf: "stretch",
        minHeight: "100%",
        [`& + .${content}`]: {
            paddingLeft: boxSpacing.left ?? boxSpacing.horizontal ?? boxSpacing.all ?? 16,
            paddingTop: !boxHasBorder ? 0 : undefined,
        },
    };
    const imageContainerWrapper = style(
        "imageContainerWrapper",
        {
            maxHeight: styleUnit(vars.image.maxHeight),
            overflow: "hidden",
        },
        !boxHasBorder &&
            !isImageLeft && {
                [`& + .${content}`]: {
                    paddingTop: boxSpacing.top ?? boxSpacing.vertical ?? boxSpacing.all ?? 16,
                },
            },
        isImageLeft && imageContainerWrapperLeftStyles,
        isImageLeftMobile && mobileQuery(imageContainerWrapperLeftStyles),
    );

    const image = style("image", {
        ...Mixins.absolute.fullSizeOfParent(),
        objectFit: "cover",
        objectPosition: "center center",
    });

    const defaultImageSVG = css({
        ...Mixins.absolute.fullSizeOfParent(),
    });

    const iconContainer = style(
        "iconContainer",
        {
            display: "flex",
            justifyContent: vars.options.alignment === "left" ? "left" : "center",
            padding: vars.icon.padding,
            ...Mixins.padding(
                !boxHasBorder && isImageLeft
                    ? {
                          top: 0,
                          left: 0,
                          bottom: 0,
                      }
                    : {},
            ),
            [`& + .${content}`]: {
                // We provide the padding downwards.
                paddingTop: isImageLeft ? undefined : 0,
                paddingLeft: isImageLeft ? 0 : undefined,
            },
        },
        mobileQuery({
            [`& + .${content}`]: {
                // We provide the padding downwards.
                paddingTop: isImageLeftMobile ? undefined : 0,
                paddingLeft: isImageLeftMobile ? 0 : undefined,
            },
        }),
    );

    const iconHasBG = !!vars.icon.background.color;
    const iconHeight = vars.icon.size + (iconHasBG ? vars.icon.padding * 2 : 0);
    const iconWrap = style("iconWrap", {
        height: iconHeight,
        padding: iconHasBG ? vars.icon.padding : 0,
        ...Mixins.background(vars.icon.background),
        ...Mixins.border(vars.icon.border),
        [activeSelector(`.${root}`, "&")]: {
            ...Mixins.background(vars.icon.backgroundState),
        },
        overflow: "hidden",
    });

    const icon = style(
        "icon",
        {
            // Width not set so that the aspect ratio of wider icons is preserved.
            height: vars.icon.size,
            maxHeight: vars.icon.size,
        },
        mobileQuery({
            height: vars.icon.sizeMobile,
            maxHeight: vars.icon.sizeMobile,
        }),
    );

    const metas = css(
        {
            "&&": {
                position: "relative",
                paddingTop: 4,
            },
        },
        textAlignment,
        [HomeWidgetItemContentType.TITLE_BACKGROUND, HomeWidgetItemContentType.TITLE_BACKGROUND_DESCRIPTION].includes(
            vars.options.contentType,
        ) && {
            "&&": {
                // Get our horizontal paddings.
                ...Mixins.box(vars.options.box, { onlyPaddings: true }),
                paddingTop: 4,
                paddingBottom: 4,
                textAlign: "left",
                flex: 1,
                display: "flex",
                alignItems: "center",
            },
        },
    );

    const longMetaItem = css({
        maxHeight: "none",
    });

    const metaDescription = css({
        whiteSpace: "normal",
    });

    const callToAction = style("callToAction", {
        display: "flex",
        alignItems: "center",
        ...Mixins.padding({
            ...vars.callToAction.padding,
        }),
        ...Mixins.font(vars.callToAction.font),
        "& svg": {
            marginLeft: 10,
        },
        [activeSelector(`.${root}`, "&")]: Mixins.font(vars.callToAction.fontState),
    });

    const description = style("description", {
        marginTop: globalVars.gutter.half,
        ...Mixins.padding(vars.description.spacing),
        ...Mixins.font(vars.description.font),
    });

    return {
        root,
        name,
        callToAction,
        absoluteName,
        metas,
        longMetaItem,
        metaDescription,
        content,
        backgroundScrim,
        backgroundContainer,
        absoluteContent,
        imageContainer,
        imageContainerWrapper,
        image,
        defaultImageSVG,
        description,
        icon,
        iconWrap,
        iconContainer,
    };
});
