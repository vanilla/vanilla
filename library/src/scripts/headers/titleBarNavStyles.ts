/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px, calc, quote, rgba } from "csx";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { flexHelper, negative, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { LogoAlignment } from "@library/headers/LogoAlignment";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { LocalVariableMapping } from "@library/styles/VariableMapping";
import { css } from "@emotion/css";

export const titleBarNavigationVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory(
        "titleBarNavigation",
        undefined,
        new LocalVariableMapping({
            "navLinks.font.size": "navLinks.fontSize",
        }),
    );
    const globalVars = globalVariables();
    const varsFormElements = formElementsVariables();
    const titleBarVars = titleBarVariables();

    const border = makeThemeVars("border", {
        verticalWidth: 3,
    });

    const item = makeThemeVars("item", {
        size: varsFormElements.sizing.height,
    });

    const padding = makeThemeVars("padding", {
        horizontal: globalVars.gutter.half,
    });

    const linkActive = makeThemeVars("linkActive", {
        offset: 0,
        height: 3,
        bg: titleBarVars.colors.fg,
        bottomSpace: 1,
        maxWidth: 40,
    });

    /**
     * @varGroup titleBarNavigation.navLinks
     * @description Variables for styling titlebar navigation links
     */
    const navLinks = makeThemeVars("navLinks", {
        /**
         * @varGroup titleBarNavigation.navLinks.font
         * @expand font
         */
        font: Variables.font({
            size: 14,
            color: titleBarVars.colors.fg,
            textDecoration: "auto",
        }),
        /**
         * @varGroup titleBarNavigation.navLinks.padding
         * @expand spacing
         */
        padding: {
            left: 8,
            right: 8,
        },
    });

    const navPadding = makeThemeVars("navPadding", {
        padding: {
            bottom: 4,
        },
    });

    return {
        border,
        item,
        linkActive,
        padding,
        navLinks,
        navPadding,
    };
});

const titleBarNavClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const titleBarVars = titleBarVariables();
    const vars = titleBarNavigationVariables();

    const mediaQueries = titleBarVars.mediaQueries();
    const flex = flexHelper();

    const root = css(
        {
            ...flex.middleLeft(),
            position: "relative",
            height: styleUnit(titleBarVars.sizing.height),
        },
        mediaQueries.compact({
            height: styleUnit(titleBarVars.sizing.mobile.height),
        }),
    );

    const navigation = css(
        titleBarVars.logo.doubleLogoStrategy === "hidden" ||
            titleBarVars.logo.doubleLogoStrategy === "mobile-only" ||
            titleBarVars.logo.justifyContent === LogoAlignment.CENTER
            ? {
                  marginLeft: styleUnit(-(vars.padding.horizontal * 2 + vars.navLinks.padding.left)),
              }
            : {},
    );

    const navigationCentered = css({
        ...Mixins.absolute.middleOfParent(true),
        display: "inline-flex",
    });

    const items = css(
        {
            ...flex.middleLeft(),
            height: styleUnit(titleBarVars.sizing.height),
            ...Mixins.padding(vars.padding),
        },
        mediaQueries.compact({
            height: px(titleBarVars.sizing.mobile.height),
            justifyContent: "center",
            width: percent(100),
        }),
    );

    const link = css({
        ...userSelect(),
        whiteSpace: "nowrap",
        lineHeight: globalVars.lineHeights.condensed,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        minHeight: styleUnit(vars.item.size),
        alignSelf: "center",
        paddingLeft: styleUnit(vars.navLinks.padding.left),
        paddingRight: styleUnit(vars.navLinks.padding.right),
        ...Mixins.font(vars.navLinks.font),
        ...{
            "&.focus-visible": {
                color: ColorsUtils.colorOut(titleBarVars.colors.state.fg),
                backgroundColor: ColorsUtils.colorOut(titleBarVars.colors.state.bg),
            },
            "&:focus": {
                color: ColorsUtils.colorOut(titleBarVars.colors.state.fg),
                backgroundColor: ColorsUtils.colorOut(titleBarVars.colors.state.bg),
            },
            "&:hover": {
                color: ColorsUtils.colorOut(titleBarVars.colors.state.fg),
                backgroundColor: ColorsUtils.colorOut(titleBarVars.colors.state.bg),
            },
        },
    });

    const offsetWidth = vars.linkActive.offset * 2;

    const linkActive = css({
        ...{
            "&:after": {
                ...Mixins.absolute.topLeft(
                    `calc(50% - ${styleUnit(vars.linkActive.height + vars.linkActive.bottomSpace)})`,
                ),
                maxWidth: styleUnit(vars.linkActive.maxWidth),
                content: quote(""),
                height: styleUnit(vars.linkActive.height),
                left: percent(50),
                marginLeft: styleUnit(negative(vars.linkActive.offset)),
                width: offsetWidth === 0 ? percent(100) : calc(`100% + ${styleUnit(offsetWidth)}`),
                transform: `translate(-50%, ${styleUnit(titleBarVars.sizing.height / 2)})`,
                backgroundColor: ColorsUtils.colorOut(vars.linkActive.bg),
            },
        },
    });

    const firstItem = css({
        zIndex: 2,
    });

    const lastItem = css({
        zIndex: 2,
    });
    const navContiner = css({
        paddingBottom: styleUnit(vars.navPadding.padding.bottom),
    });

    const navLinks = css({});

    const navLinkAsButton = css({
        ...Mixins.font({ weight: 400 }),
    });

    return {
        root,
        navigation,
        navigationCentered,
        items,
        link,
        linkActive,
        lastItem,
        firstItem,
        navLinks,
        navContiner,
        navLinkAsButton,
    };
});

export default titleBarNavClasses;
