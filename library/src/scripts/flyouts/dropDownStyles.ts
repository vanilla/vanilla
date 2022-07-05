/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { buttonStates, userSelect, IStateSelectors, negativeUnit, pointerEvents } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { css, CSSObject } from "@emotion/css";
import { getPixelNumber, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { important, percent, rgba } from "csx";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { buttonResetMixin } from "@library/forms/buttonMixins";
import { metasVariables } from "@library/metas/Metas.variables";

export const notUserContent = "u-notUserContent";

/**
 * @varGroup dropDown
 * @title DropDown
 * @description The DropDown is used to organise lists of links and buttons into a toggleable overlay.
 */
export const dropDownVariables = useThemeCache(() => {
    const metasVars = metasVariables();
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("dropDown");

    /**
     * @varGroup dropDown.sizing
     * @description Configure the size of the dropdowns.
     */
    const sizing = makeThemeVars("sizing", {
        /**
         * @varGroup dropDown.sizing.widths
         * @description Container width for different sizes.
         */
        widths: {
            /**
             * @var dropDown.sizing.widths.default
             * @description The default width is used on menus like discussion options.
             * @title Default
             * @type number
             */
            default: 250,
            /**
             * @var dropDown.sizing.widths.medium
             * @description The medium width is used on items like the MeBox.
             * @title Medium
             * @type number
             */
            medium: 300,
        },
    });

    const spacer = makeThemeVars("spacer", {
        margin: {
            vertical: 8,
        },
    });

    const metas = makeThemeVars("metas", {
        font: Variables.font({
            size: metasVars.font.size,
            color: metasVars.font.color,
        }),
        padding: {
            vertical: 6,
            horizontal: 14,
        },
    });

    const item = makeThemeVars("item", {
        minHeight: 30,
        mobile: {
            minHeight: 44,
            fontSize: 16,
        },
        padding: {
            top: 6,
            horizontal: 14,
        },
    });

    const sectionTitle = makeThemeVars("sectionTitle", {
        padding: {
            top: 0,
            bottom: 0,
        },
    });

    const title = makeThemeVars("title", {
        color: globalVars.mainColors.fg,
    });

    /**
     * @varGroup dropDown.contents
     * @description User Dropdown contents section
     */
    const contents = makeThemeVars("contents", {
        /**
         * @var dropDown.contents.bg
         * @description Background color for user menu
         * @title User Menu Background Color
         * @type string
         * @format hex-color
         */
        bg: globalVars.mainColors.bg,
        /**
         * @var dropDown.contents.fg
         * @description Foreground color for user menu
         * @title User Menu Foreground Color
         * @type string
         * @format hex-color
         */
        fg: globalVars.mainColors.fg,
        border: globalVars.borderType.dropDowns,
        padding: {
            vertical: 9,
            horizontal: 16,
        },
    });

    return {
        sizing,
        metas,
        item,
        sectionTitle,
        spacer,
        title,
        contents,
    };
});

export const dropDownClasses = useThemeCache(() => {
    const vars = dropDownVariables();
    const metasVars = metasVariables();
    const globalVars = globalVariables();
    const shadows = shadowHelper();
    const mediaQueries = oneColumnVariables().mediaQueries();

    const root = css({
        position: "relative",
        listStyle: "none",
    });

    const contentMixin: CSSObject = {
        minWidth: styleUnit(vars.sizing.widths.default),
        backgroundColor: ColorsUtils.colorOut(vars.contents.bg),
        color: ColorsUtils.colorOut(vars.contents.fg),
        overflow: "auto",
        ...Mixins.border(vars.contents.border),
        ...shadowOrBorderBasedOnLightness(vars.contents.bg, Mixins.border(vars.contents.border), shadows.dropDown()),
        "&&": {
            zIndex: 3,
            ...Mixins.border(vars.contents.border),
        },
        "&.isMedium": {
            width: styleUnit(vars.sizing.widths.medium),
        },
    };
    const contentsBox = css(contentMixin);

    const contents = css(
        {
            position: "absolute",
            ...contentMixin,
            "&.isParentWidth": {
                minWidth: "initial",
                left: vars.item.padding.horizontal * -1,
                right: `${getPixelNumber(vars.item.padding.horizontal) * -1}px!important`,
                width: `calc(100% + ${getPixelNumber(vars.item.padding.horizontal) * 2}px)`,
            },
            "&.isOwnWidth": {
                width: "initial",
            },
            "&.isRightAligned": {
                right: 0,
                top: 0,
            },
            ".frame": {
                boxShadow: "none",
            },
            "&.noMinWidth": {
                minWidth: 0,
            },
            "&.hasVerticalPadding": {
                ...Mixins.padding({
                    vertical: 12,
                    horizontal: important(0),
                }),
            },
        },
        mediaQueries.oneColumnDown({
            ...{
                "&.isOwnWidth": {
                    width: percent(100),
                },
            },
        }),
    );

    const asModal = css({
        ...{
            "&.hasVerticalPadding": Mixins.padding({
                vertical: 12,
            }),
        },
    });

    const likeDropDownContent = css({
        ...shadows.dropDown(),
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
        ...Mixins.border(vars.contents.border),
    });

    const items = css(
        {
            padding: 0,
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium"),
            }),
        },
        mediaQueries.oneColumnDown({
            ...Mixins.padding({
                vertical: 9,
            }),
        }),
    );

    const metaItems = css({
        ...{
            "&&": {
                display: "block",
            },
        },
        ...Mixins.padding(vars.metas.padding),
    });

    const metaItem = css({
        ...{
            "& + &": {
                paddingTop: styleUnit(vars.item.padding.top),
            },
        },
        ...Mixins.font(vars.metas.font),
    });

    const badge = css({
        marginLeft: globalVars.gutter.size,
        ...Mixins.padding({
            vertical: globalVars.gutter.quarter,
            horizontal: globalVars.gutter.half,
        }),
        backgroundColor: ColorsUtils.colorOut(vars.contents.fg.fade(0.4)),
        color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        borderRadius: globalVars.border.radius,
    });

    // wrapping element
    const item = css({
        ...userSelect("none"),
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        width: percent(100),
        margin: 0,
        color: "inherit",
        textAlign: "left",
        lineHeight: globalVars.lineHeights.condensed,
    });

    const section = css({
        display: "block",
    });

    const toggleButtonIcon = css({
        ...{
            ...buttonStates({
                allStates: {
                    color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                },
            }),
        },
    });

    const action = css({
        ...{
            "&&": actionMixin(),
        },
    });

    const actionActive = css({
        ...{
            "&&": {
                color: important(ColorsUtils.colorOut(globalVars.links.colors.active)!),
                fontWeight: important(globalVars.fonts.weights.bold) as any,
            },
        },
    });

    const text = css({
        display: "block",
        flex: 1,
    });

    const separator = css({
        listStyle: "none",
        height: styleUnit(globalVars.separator.size),
        backgroundColor: ColorsUtils.colorOut(globalVars.separator.color),
        ...Mixins.margin(vars.spacer.margin),
        border: "none",
        "&:first-child": {
            height: 0,
            ...Mixins.margin({ all: 0, top: vars.spacer.margin.vertical * 1.5 }),
        },
        "& + &, &:last-child, &:first-child": {
            display: "none",
        },
    });

    const panelNavItems = css({
        display: "flex",
        alignItems: "flex-start",
    });

    const panel = css({
        backgroundColor: ColorsUtils.colorOut(vars.contents.bg),
        ...Mixins.absolute.fullSizeOfParent(),
        zIndex: 2,
    });

    const panelFirstStyle: CSSObject = {
        // We want the initial view to have no left space
        "& li": {
            paddingLeft: important(styleUnit(0)),
        },
        "&&": {
            position: "relative",
            height: "initial",
            zIndex: 0,
        },
    };
    const panelFirst = css(panelFirstStyle);

    const panelLast = css({
        ...{
            "&&": {},
        },
    });

    const panelContent = css({
        flex: 1,
        ...{
            "&.isNested": {},
        },
    });
    const sectionContents = css({
        display: "block",
        position: "relative",
    });

    const sectionHeading = css({
        ...{
            "&&": {
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("small", "semiBold"),
                    color: ColorsUtils.colorOut(metasVars.font.color),
                    transform: "uppercase",
                    align: "center",
                }),
                ...Mixins.padding(vars.sectionTitle.padding),
            },
            [`& + .${sectionContents} li:first-child`]: { paddingTop: styleUnit(vars.spacer.margin.vertical) },
        },
    });

    const headingContentContainer = css({
        display: "flex",
        alignItems: "center",
        height: styleUnit(44),
    });

    const headingTitleContainer = css({
        flex: "auto",
    });

    const arrow = css({
        ...{
            "&&": {
                padding: styleUnit(globalVars.gutter.quarter),
            },
        },
    });

    const actionIcon = css({
        marginRight: globalVars.gutter.half,
    });

    const backButton = css(
        {
            ...{
                "&&": {
                    zIndex: 2,
                    minHeight: styleUnit(vars.item.minHeight),
                    transform: "translateX(12px)",
                },
            },
        },
        mediaQueries.oneColumnDown({
            ...{
                "&&": {
                    minHeight: styleUnit(vars.item.mobile.minHeight),
                    minWidth: styleUnit(vars.item.mobile.minHeight),
                    transform: "none",
                },
            },
        }),
    );

    const count = css({
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small"),
        }),
        paddingLeft: "1em",
        marginLeft: "auto",
    });

    const verticalPadding = css(
        {
            ...Mixins.padding({
                vertical: vars.contents.padding.vertical,
                horizontal: 0,
            }),
        },
        mediaQueries.oneColumnDown({
            ...Mixins.padding({
                vertical: 0,
            }),
        }),
    );

    const noVerticalPadding = css({
        ...Mixins.padding({ vertical: 0 }),
    });

    const title = css({
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium", "semiBold"),
            lineHeight: globalVars.lineHeights.condensed,
        }),

        ...Mixins.padding({
            all: 0,
        }),
        ...Mixins.margin({
            all: 0,
        }),
        textAlign: "left",
        flexGrow: 1,
        color: ColorsUtils.colorOut(vars.title.color),
    });

    const paddedFrame = css({
        ...Mixins.padding(vars.contents.padding),
    });

    const check = css({
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),

        /// Check to fix icon alignment.
        transform: `translateX(4px)`,
    });

    const flyoutOffset = vars.item.padding.horizontal + globalVars.border.width;

    const contentOffsetCenter = css({
        transform: `translateX(-50%)`,
    });

    const contentOffsetLeft = css({
        transform: `translateX(${styleUnit(flyoutOffset)})`,
    });

    const contentOffsetRight = css({
        transform: `translateX(${negativeUnit(flyoutOffset)})`,
    });

    // Used to figure out the position of the flyout,
    // without it being visible to the user until the calculation is complete
    const positioning = css({
        ...pointerEvents(),
        ...Mixins.absolute.srOnly(),
    });

    const closeButton = css({
        display: "inline-block",
        marginRight: styleUnit(12),
    });

    const itemButton = css({
        paddingLeft: globalVars.gutter.size,
    });

    const thumbnailItemLabel = css({
        display: "inline-block",
        marginTop: 8,
    });

    const thumbnailItemThumbnail = css({
        display: "inline-block",
        border: "1px solid #dddee0",
        borderRadius: 6,
    });

    const thumbnailItem = css({
        width: 220,
        height: 186,
        padding: 6,
        borderRadius: 6,
        display: "inline-block",
        cursor: "pointer",

        [`&:hover,&active,&:focus,&.focus-visible`]: {
            [`.${thumbnailItemThumbnail}`]: {
                borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
                background: ColorsUtils.colorOut(globalVars.mainColors.primary.fade(0.1)),
            },
        },
    });

    const thumbnailItemSmall = css({
        width: 154,
        height: 166,

        "& svg": {
            width: 140,
            height: 126,
        },
    });

    const gridItem = css({
        width: 680,
        padding: "0 10px",
    });

    const gridItemSmall = css({
        display: "flex",
        flexWrap: "wrap",
        justifyContent: "center",
        width: "100%",
    });

    return {
        root,
        contents,
        contentsBox,
        asModal,
        likeDropDownContent,
        items,
        metaItems,
        metaItem,
        item,
        section,
        toggleButtonIcon,
        action,
        actionIcon,
        actionActive,
        text,
        separator,
        sectionHeading,
        sectionContents,
        count,
        arrow,
        verticalPadding,
        title,
        noVerticalPadding,
        paddedFrame,
        panelFirst,
        panelLast,
        panelNavItems,
        panel,
        panelContent,
        backButton,
        check,
        contentOffsetCenter,
        contentOffsetLeft,
        contentOffsetRight,
        positioning,
        closeButton,
        itemButton,
        headingContentContainer,
        headingTitleContainer,
        thumbnailItem,
        thumbnailItemSmall,
        thumbnailItemLabel,
        thumbnailItemThumbnail,
        gridItem,
        gridItemSmall,
        badge,
    };
});

// Contents (button or link)
// Replaces: .dropDownItem-button, .dropDownItem-link
export const actionMixin = (classBasedStates?: IStateSelectors): CSSObject => {
    const vars = dropDownVariables();
    const globalVars = globalVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();

    return {
        ...buttonResetMixin(),
        cursor: "pointer",
        appearance: "none",
        display: "flex",
        alignItems: "center",
        width: percent(100),
        textAlign: "left",
        minHeight: styleUnit(vars.item.minHeight),
        lineHeight: globalVars.lineHeights.condensed,
        ...Mixins.padding({
            vertical: 4,
            horizontal: vars.item.padding.horizontal,
        }),
        ...Mixins.border({
            color: rgba(0, 0, 0, 0),
            radius: 0,
        }),
        // Override legacy style.scss with global variables by making it important.
        // ".MenuItems a, .MenuItems a:link, .MenuItems a:visited, .MenuItems a:active "
        color: ColorsUtils.colorOut(vars.contents.fg, { makeImportant: true }),
        ...userSelect("none"),
        ...buttonStates(
            {
                allStates: {
                    textShadow: "none",
                    outline: 0,
                },
                hover: {
                    backgroundColor: important(ColorsUtils.colorOut(globalVars.states.hover.highlight) as string),
                    color: globalVars.states.hover.contrast
                        ? ColorsUtils.colorOut(globalVars.states.hover.contrast)
                        : undefined,
                },
                focus: {
                    backgroundColor: important(ColorsUtils.colorOut(globalVars.states.focus.highlight) as string),
                    color: globalVars.states.hover.contrast
                        ? ColorsUtils.colorOut(globalVars.states.focus.contrast)
                        : undefined,
                },
                active: {
                    backgroundColor: important(ColorsUtils.colorOut(globalVars.states.active.highlight) as string),
                    color: globalVars.states.hover.contrast
                        ? ColorsUtils.colorOut(globalVars.states.active.contrast)
                        : undefined,
                },
                keyboardFocus: {
                    borderColor: ColorsUtils.colorOut(globalVars.states.focus.highlight),
                    color: globalVars.states.hover.contrast
                        ? ColorsUtils.colorOut(globalVars.states.focus.contrast)
                        : undefined,
                },
            },
            undefined,
            classBasedStates,
        ),
        ...mediaQueries.oneColumnDown({
            fontSize: styleUnit(vars.item.mobile.fontSize),
            minHeight: styleUnit(vars.item.mobile.minHeight),
        }),
    };
};
