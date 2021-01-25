/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import {
    buttonStates,
    userSelect,
    IStateSelectors,
    absolutePosition,
    negativeUnit,
    pointerEvents,
} from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { CSSObject } from "@emotion/css";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { important, percent, rgba } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { buttonResetMixin } from "@library/forms/buttonMixins";

export const notUserContent = "u-notUserContent";

export const dropDownVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("dropDown");

    const sizing = makeThemeVars("sizing", {
        widths: {
            default: 250,
            medium: 350,
        },
        minHeight: 600,
    });

    const spacer = makeThemeVars("spacer", {
        margin: {
            vertical: 8,
        },
    });

    const metas = makeThemeVars("metas", {
        font: Variables.font({
            size: globalVars.meta.text.size,
            color: globalVars.meta.text.color,
        }),
        padding: {
            vertical: 6,
            horizontal: 14,
        },
    });

    const item = makeThemeVars("item", {
        colors: {
            fg: globalVars.mainColors.fg,
        },
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

    const contents = makeThemeVars("contents", {
        bg: globalVars.mainColors.bg,
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
    const globalVars = globalVariables();
    const style = styleFactory("dropDown");
    const shadows = shadowHelper();
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style({
        position: "relative",
        listStyle: "none",
    });

    const contents = style(
        "contents",
        {
            position: "absolute",
            minWidth: styleUnit(vars.sizing.widths.default),
            backgroundColor: ColorsUtils.colorOut(vars.contents.bg),
            color: ColorsUtils.colorOut(vars.contents.fg),
            overflow: "auto",
            ...Mixins.border(vars.contents.border),
            ...shadowOrBorderBasedOnLightness(
                vars.contents.bg,
                Mixins.border(vars.contents.border),
                shadows.dropDown(),
            ),
            ...{
                "&&": {
                    zIndex: 3,
                    ...Mixins.border(vars.contents.border),
                },
                "&.isMedium": {
                    width: styleUnit(vars.sizing.widths.medium),
                },
                "&.isParentWidth": {
                    minWidth: "initial",
                    left: 0,
                    right: 0,
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
        },
        mediaQueries.oneColumnDown({
            ...{
                "&.isOwnWidth": {
                    width: percent(100),
                },
            },
        }),
    );

    const asModal = style("asModal", {
        ...{
            "&.hasVerticalPadding": Mixins.padding({
                vertical: 12,
            }),
        },
    });

    const likeDropDownContent = style("likeDropDownContent", {
        ...shadows.dropDown(),
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
        ...Mixins.border(vars.contents.border),
    });

    const items = style(
        "items",
        {
            padding: 0,
            fontSize: styleUnit(globalVars.fonts.size.medium),
        },
        mediaQueries.oneColumnDown({
            ...Mixins.padding({
                vertical: 9,
            }),
        }),
    );

    const metaItems = style("metaItems", {
        ...{
            "&&": {
                display: "block",
            },
        },
        ...Mixins.padding(vars.metas.padding),
    });

    const metaItem = style("metaItem", {
        ...{
            "& + &": {
                paddingTop: styleUnit(vars.item.padding.top),
            },
        },
        ...Mixins.font(vars.metas.font),
    });

    // wrapping element
    const item = style("item", {
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

    const section = style("section", {
        display: "block",
    });

    const toggleButtonIcon = style("toggleButtonIcon", {
        ...{
            ...buttonStates({
                allStates: {
                    color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                },
            }),
        },
    });

    const action = style("action", {
        ...{
            "&&": actionMixin(),
        },
    });

    const actionActive = style("actionActive", {
        ...{
            "&&": {
                color: important(ColorsUtils.colorOut(globalVars.links.colors.active)!),
                fontWeight: important(globalVars.fonts.weights.bold) as any,
            },
        },
    });

    const text = style("text", {
        display: "block",
        flex: 1,
    });

    const separator = style("separator", {
        listStyle: "none",
        height: styleUnit(globalVars.separator.size),
        backgroundColor: ColorsUtils.colorOut(globalVars.separator.color),
        ...Mixins.margin(vars.spacer.margin),
        border: "none",
        ...{
            "&:first-child": {
                height: 0,
                ...Mixins.margin({ all: 0, top: vars.spacer.margin.vertical * 1.5 }),
            },
        },
    });

    const panelNavItems = style("panelNavItems", {
        display: "flex",
        alignItems: "flex-start",
    });

    const panel = style("panel", {
        backgroundColor: ColorsUtils.colorOut(vars.contents.bg),
        ...absolutePosition.fullSizeOfParent(),
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
    const panelFirst = style("panelFirst", panelFirstStyle);

    const panelMobileOnly = style("panelMobileOnly", panelFirstStyle);

    const panelLast = style("panelLast", {
        ...{
            "&&": {},
        },
    });

    const panelContent = style("panelContent", {
        flex: 1,
        ...{
            "&.isNested": {},
        },
    });
    const sectionContents = style("sectionContents", {
        display: "block",
        position: "relative",
    });

    const sectionHeading = style("sectionHeading", {
        ...{
            "&&": {
                color: ColorsUtils.colorOut(globalVars.meta.text.color),
                fontSize: styleUnit(globalVars.fonts.size.small),
                textTransform: "uppercase",
                textAlign: "center",
                fontWeight: globalVars.fonts.weights.semiBold,
                ...Mixins.padding(vars.sectionTitle.padding),
            },
            [`& + .${sectionContents} li:first-child`]: { paddingTop: styleUnit(vars.spacer.margin.vertical) },
        },
    });

    const headingContentContainer = style("headingContentContainer", {
        display: "flex",
        alignItems: "center",
        height: styleUnit(44),
    });

    const headingTitleContainer = style("headingTitleContainer", {
        flex: "auto",
    });

    const arrow = style("arrow", {
        ...{
            "&&": {
                padding: styleUnit(globalVars.gutter.quarter),
            },
        },
    });

    const actionIcon = style("actionIcon", {
        marginRight: globalVars.gutter.half,
    });

    const backButton = style(
        "backButton",
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
                },
            },
        }),
    );

    const count = style("count", {
        fontSize: styleUnit(globalVars.fonts.size.small),
        paddingLeft: "1em",
        marginLeft: "auto",
    });

    const verticalPadding = style(
        "verticalPadding",
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

    const noVerticalPadding = style("noVerticalPadding", {
        ...Mixins.padding({ vertical: 0 }),
    });

    const title = style("title", {
        ...Mixins.font({
            weight: globalVars.fonts.weights.semiBold,
            size: globalVars.fonts.size.medium,
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

    const paddedFrame = style("paddedFrame", {
        ...Mixins.padding(vars.contents.padding),
    });

    const check = style("check", {
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),

        /// Check to fix icon alignment.
        transform: `translateX(4px)`,
    });

    const flyoutOffset = vars.item.padding.horizontal + globalVars.border.width;

    const contentOffsetCenter = style("contentOffsetCenter", {
        transform: `translateX(-50%)`,
    });

    const contentOffsetLeft = style("contentOffsetLeft", {
        transform: `translateX(${styleUnit(flyoutOffset)})`,
    });

    const contentOffsetRight = style("contentOffsetRight", {
        transform: `translateX(${negativeUnit(flyoutOffset)})`,
    });

    // Used to figure out the position of the flyout,
    // without it being visible to the user until the calculation is complete
    const positioning = style("positioning", {
        ...pointerEvents(),
        ...Mixins.absolute.srOnly(),
    });

    const closeButton = style("closeButtonOffsetRight", {
        display: "inline-block",
        marginRight: styleUnit(12),
    });

    const itemButton = style("itemButton", {
        paddingLeft: globalVars.gutter.size,
    });

    return {
        root,
        contents,
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
        panelMobileOnly,
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
    };
});

// Contents (button or link)
// Replaces: .dropDownItem-button, .dropDownItem-link
export const actionMixin = (classBasedStates?: IStateSelectors): CSSObject => {
    const vars = dropDownVariables();
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();

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
        color: ColorsUtils.colorOut(vars.item.colors.fg),
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
            fontWeight: globalVars.fonts.weights.semiBold,
            minHeight: styleUnit(vars.item.mobile.minHeight),
        }),
    };
};
