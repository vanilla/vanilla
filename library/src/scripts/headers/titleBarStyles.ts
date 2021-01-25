/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    absolutePosition,
    allButtonStates,
    BorderType,
    flexHelper,
    pointerEvents,
    singleBorder,
    sticky,
    userSelect,
    negativeUnit,
} from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import {
    calc,
    ColorHelper,
    linearGradient,
    percent,
    px,
    quote,
    rgba,
    translate,
    translateX,
    translateY,
    viewWidth,
} from "csx";
import backLinkClasses from "@library/routing/links/backLinkStyles";
import { CSSObject } from "@emotion/css";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { buttonResetMixin } from "@library/forms/buttonMixins";
import generateButtonClass from "@library/forms/styleHelperButtonGenerator";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { titleBarVariables } from "./TitleBar.variables";

export const titleBarClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = titleBarVariables();
    const formElementVars = formElementsVariables();
    const mediaQueries = vars.mediaQueries();
    const flex = flexHelper();
    const style = styleFactory("titleBar");

    const getBorderVars = (): CSSObject => {
        switch (vars.border.type) {
            case BorderType.BORDER:
                return {
                    borderBottom: singleBorder({
                        color: vars.border.color,
                        width: vars.border.width,
                    }),
                };
            case BorderType.SHADOW:
                return {
                    boxShadow: shadowHelper().embed(globalVars.elementaryColors.black).boxShadow,
                };
            case BorderType.SHADOW_AS_BORDER:
                // Note that this is empty because this option is set on the background elsewhere.
                return {};
            case BorderType.NONE:
                return {};
            default:
                return {};
        }
    };

    const root = style({
        maxWidth: percent(100),
        color: ColorsUtils.colorOut(vars.colors.fg),
        position: "relative",
        ...getBorderVars(),
        ...{
            ".searchBar__control": {
                color: vars.colors.fg.toString(),
                cursor: "pointer",
            },
            ".searchBar__placeholder": {
                textAlign: "left",
                color: vars.colors.fg.fade(0.8).toString(),
            },
            [`.${backLinkClasses().link}`]: {
                ...{
                    "&, &:hover, &:focus, &:active": {
                        color: ColorsUtils.colorOut(vars.colors.fg),
                    },
                },
            },
        },
        ...(vars.swoop.amount
            ? {
                  ...{
                      "& + *": {
                          // Offset the next element to account for the swoop. (next element should go under the swoop slightly).
                          marginTop: -vars.swoop.swoopOffset,
                      },
                  },
              }
            : {}),
    });

    const swoopStyles = {
        top: 0,
        left: 0,
        margin: `0 auto`,
        position: `absolute`,
        height: calc(`80% - ${styleUnit(vars.border.width + 1)}`),
        transform: translateX(`-10vw`),
        width: `120vw`,
        borderRadius: `0 0 100% 100%/0 0 ${percent(vars.swoop.amount)} ${percent(vars.swoop.amount)}`,
    };

    const swoop = style("swoop", {});

    const shadowAsBorder =
        vars.border.type === BorderType.SHADOW_AS_BORDER
            ? { boxShadow: `0 ${styleUnit(vars.border.width)} 0 ${ColorsUtils.colorOut(vars.border.color)}` }
            : {};

    const bg1 = style("bg1", {
        willChange: "opacity",
        ...absolutePosition.fullSizeOfParent(),
        backgroundColor: ColorsUtils.colorOut(vars.fullBleed.enabled ? vars.fullBleed.bgColor : vars.colors.bg),
        ...shadowAsBorder,
        overflow: "hidden",
        ...{
            [`&.${swoop}`]: swoopStyles,
        },
    });

    const bg2 = style("bg2", {
        willChange: "opacity",
        ...absolutePosition.fullSizeOfParent(),
        backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
        ...shadowAsBorder,
        overflow: "hidden",
        ...{
            [`&.${swoop}`]: swoopStyles,
        },
    });

    const container = style("container", {
        position: "relative",
        height: percent(100),
        width: percent(100),
        ...Mixins.padding(vars.spacing.padding),
    });

    const bgContainer = style("bgContainer", {
        ...absolutePosition.fullSizeOfParent(),
        height: percent(100),
        width: percent(100),
        ...Mixins.padding(vars.spacing.padding),
        boxSizing: "content-box",
        overflow: "hidden",
    });

    const bgImage = style("bgImage", {
        ...absolutePosition.fullSizeOfParent(),
        objectFit: "cover",
    });

    const bannerPadding = style(
        "bannerPadding",
        {
            paddingTop: px(vars.sizing.height / 2),
        },
        mediaQueries.compact({
            paddingTop: px(vars.sizing.mobile.height / 2 + 20),
        }),
    );

    const negativeSpacer = style(
        "negativeSpacer",
        {
            marginTop: px(-vars.sizing.height),
        },
        mediaQueries.compact({
            marginTop: px(-vars.sizing.mobile.height),
        }),
    );

    const spacer = style(
        "spacer",
        {
            height: px(vars.sizing.height),
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const bar = style(
        "bar",
        {
            display: "flex",
            justifyContent: "flex-start",
            flexWrap: "nowrap",
            alignItems: "center",
            height: px(vars.sizing.height),
            width: percent(100),
            ...{
                "&.isHome": {
                    justifyContent: "space-between",
                },
            },
        },
        mediaQueries.compact({ height: px(vars.sizing.mobile.height) }),
    );

    const logoOffsetDesktop = vars.logo.offsetVertical.amount
        ? {
              transform: translateY(`${styleUnit(vars.logo.offsetVertical.amount)}`),
          }
        : {};

    const logoOffsetMobile = vars.logo.offsetVertical.mobile.amount
        ? {
              transform: translateY(`${styleUnit(vars.logo.offsetVertical.mobile.amount)}`),
          }
        : {};

    const logoContainer = style(
        "logoContainer",
        {
            display: "inline-flex",
            alignSelf: "center",
            color: ColorsUtils.colorOut(vars.colors.fg),
            marginRight: styleUnit(vars.logo.offsetRight),
            justifyContent: vars.logo.justifyContent,
            ...logoOffsetDesktop,
            maxHeight: percent(100),
            ...{
                "&&": {
                    color: ColorsUtils.colorOut(vars.colors.fg),
                },
                "&.focus-visible": {
                    ...{
                        "&.headerLogo-logoFrame": {
                            outline: `5px solid ${vars.buttonContents.state.bg}`,
                            background: ColorsUtils.colorOut(vars.buttonContents.state.bg),
                            borderRadius: vars.button.borderRadius,
                        },
                    },
                },
            },
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
            marginRight: styleUnit(0),
            ...logoOffsetMobile,
        }),
    );

    const logoFlexBasis = style("logoFlexBasis", {
        flexBasis: vars.endElements.flexBasis,
    });

    const meBox = style("meBox", {
        justifyContent: "flex-end",
    });

    const nav = style(
        "nav",
        {
            display: "flex",
            flexWrap: "wrap",
            height: px(vars.sizing.height),
            color: "inherit",
            flexGrow: 1,
            justifyContent: vars.navAlignment.alignment === "left" ? "flex-start" : "center",
            ...{
                "&.titleBar-guestNav": {
                    flex: "initial",
                },
            },
        },
        mediaQueries.compact({ height: px(vars.sizing.mobile.height) }),
    );

    const locales = style(
        "locales",
        {
            height: px(vars.sizing.height),
            ...{
                "&.buttonAsText": {
                    ...{
                        "&:hover": {
                            color: "inherit",
                        },
                        "&:focus": {
                            color: "inherit",
                        },
                    },
                },
            },
        },
        mediaQueries.compact({ height: px(vars.sizing.mobile.height) }),
    );

    const messages = style("messages", {
        color: vars.colors.fg.toString(),
    });

    const notifications = style("notifications", {
        color: "inherit",
    });

    const compactSearch = style(
        "compactSearch",
        {
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            marginLeft: "auto",
            minWidth: styleUnit(formElementVars.sizing.height),
            flexBasis: px(formElementVars.sizing.height),
            maxWidth: percent(100),
            height: styleUnit(vars.sizing.height),
            ...{
                "&.isOpen": {
                    flex: 1,
                },
            },
        },
        mediaQueries.compact({ height: px(vars.sizing.mobile.height) }),
    );

    const compactSearchResults = style(
        "compactSearchResults",
        {
            position: "absolute",
            top: styleUnit(formElementVars.sizing.height + 2),
            width: percent(100),
            ...{
                "&:empty": {
                    display: "none",
                },
            },
        },
        layoutVariables()
            .mediaQueries()
            .xs({
                ...{
                    "&&&": {
                        width: viewWidth(100),
                        left: calc(`50% + ${styleUnit(40)}`), // This is not arbitrary, it's based on the hamburger placement, but because of the way it's calculated, it makes for a messy calculation. We need to refactor it.
                        transform: translateX("-50%"),
                        borderTopRightRadius: 0,
                        borderTopLeftRadius: 0,
                    },
                    ".suggestedTextInput-option": {
                        ...Mixins.padding({
                            horizontal: 21,
                        }),
                    },
                },
            }),
    );

    const extraMeBoxIcons = style("extraMeBoxIcons", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-end",
        marginLeft: "auto",
        ...{
            [`& + .${compactSearch}`]: {
                marginLeft: 0,
            },
            li: {
                listStyle: "none",
            },
        },
    });

    const topElement = style(
        "topElement",
        {
            color: vars.colors.fg.toString(),
            padding: `0 ${px(vars.sizing.spacer / 2)}`,
            margin: `0 ${px(vars.sizing.spacer / 2)}`,
            borderRadius: px(vars.button.borderRadius),
        },
        mediaQueries.compact({
            // fontSize: px(vars.button.mobile.fontSize),
        }),
    );

    const localeToggle = style(
        "localeToggle",
        {
            height: px(vars.sizing.height),
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const languages = style("languages", {
        marginLeft: "auto",
    });

    const button = style(
        "button",
        {
            ...buttonResetMixin(),
            height: px(vars.button.size),
            minWidth: px(vars.button.size),
            maxWidth: percent(100),
            padding: px(0),
            color: ColorsUtils.colorOut(vars.colors.fg),
            ...{
                "&&": {
                    ...allButtonStates(
                        {
                            allStates: {
                                color: ColorsUtils.colorOut(vars.colors.fg),
                                ...{
                                    ".meBox-buttonContent": {
                                        backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
                                    },
                                },
                            },
                            keyboardFocus: {
                                outline: 0,
                                color: ColorsUtils.colorOut(vars.colors.fg),
                                ...{
                                    ".meBox-buttonContent": {
                                        borderColor: ColorsUtils.colorOut(vars.colors.fg),
                                        backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
                                    },
                                },
                            },
                        },
                        {
                            ".meBox-buttonContent": {
                                ...Mixins.border({
                                    width: 1,
                                    color: rgba(0, 0, 0, 0),
                                }),
                            },
                            "&.isOpen": {
                                color: ColorsUtils.colorOut(vars.colors.fg),
                                ...{
                                    ".meBox-buttonContent": {
                                        backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
                                    },
                                    "&:focus": {
                                        color: ColorsUtils.colorOut(vars.colors.fg),
                                    },
                                    "&.focus-visible": {
                                        color: ColorsUtils.colorOut(vars.colors.fg),
                                    },
                                },
                            },
                        },
                    ),
                },
            },
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
            width: px(vars.sizing.mobile.width),
            minWidth: px(vars.sizing.mobile.width),
        }),
    );

    const linkButton = generateButtonClass(vars.linkButton);

    const buttonOffset = style("buttonOffset", {
        transform: `translateX(6px)`,
    });

    const centeredButton = style("centeredButton", {
        ...flex.middle(),
    });

    const searchCancel = style("searchCancel", {
        ...buttonResetMixin(),
        ...userSelect(),
        height: px(formElementVars.sizing.height),
        ...{
            "&.focus-visible": {
                ...{
                    "&.meBox-buttonContent": {
                        borderRadius: px(vars.button.borderRadius),
                        backgroundColor: vars.buttonContents.state.bg.toString(),
                    },
                },
            },
        },
    });

    const tabButtonActive = {
        color: globalVars.mainColors.primary.toString(),
        ...{
            ".titleBar-tabButtonContent": {
                color: vars.colors.fg.toString(),
                backgroundColor: ColorsUtils.colorOut(
                    ColorsUtils.modifyColorBasedOnLightness({ color: vars.colors.fg, weight: 1 }),
                ),
                borderRadius: px(vars.button.borderRadius),
            },
        },
    };

    const tabButton = style("tabButton", {
        display: "block",
        height: percent(100),
        padding: px(0),
        ...{
            "&:active": tabButtonActive,
            "&:hover": tabButtonActive,
            "&:focus": tabButtonActive,
        },
    });

    const dropDownContents = style("dropDownContents", {
        ...{
            "&&&": {
                minWidth: styleUnit(vars.dropDownContents.minWidth),
                maxHeight: styleUnit(vars.dropDownContents.maxHeight),
            },
        },
    });

    const count = style("count", {
        height: px(vars.count.size),
        fontSize: px(vars.count.fontSize),
        backgroundColor: vars.count.bg.toString(),
        color: vars.count.fg.toString(),
    });

    const rightFlexBasis = style(
        "rightFlexBasis",
        {
            display: "flex",
            height: px(vars.sizing.height),
            flexWrap: "nowrap",
            justifyContent: "flex-end",
            alignItems: "center",
            flexBasis: vars.endElements.flexBasis,
        },
        mediaQueries.compact({
            flexShrink: 1,
            flexBasis: px(vars.endElements.mobile.flexBasis),
            height: px(vars.sizing.mobile.height),
        }),
    );

    const leftFlexBasis = style("leftFlexBasis", {
        ...flex.middleLeft(),
        flexShrink: 1,
        flexBasis: px(vars.endElements.mobile.flexBasis),
    });

    const signIn = style("signIn", {
        marginLeft: styleUnit(vars.guest.spacer),
        marginRight: styleUnit(vars.guest.spacer),
        ...{
            "&&&": {
                color: ColorsUtils.colorOut(vars.signIn.fg),
                borderColor: ColorsUtils.colorOut(vars.colors.fg),
            },
        },
    });

    const register = style("register", {
        marginLeft: styleUnit(vars.guest.spacer),
        marginRight: styleUnit(vars.guest.spacer),
        backgroundColor: ColorsUtils.colorOut(vars.resister.bg),
        ...{
            "&&": {
                // Ugly solution, but not much choice until: https://github.com/vanilla/knowledge/issues/778
                ...allButtonStates({
                    allStates: {
                        borderColor: ColorsUtils.colorOut(vars.resister.borderColor),
                        color: ColorsUtils.colorOut(vars.resister.fg),
                    },
                    noState: {
                        backgroundColor: ColorsUtils.colorOut(vars.resister.bg),
                    },
                    hover: {
                        color: ColorsUtils.colorOut(vars.resister.fg),
                        backgroundColor: ColorsUtils.colorOut(vars.resister.states.bg),
                    },
                    focus: {
                        color: ColorsUtils.colorOut(vars.resister.fg),
                        backgroundColor: ColorsUtils.colorOut(vars.resister.states.bg),
                    },
                    active: {
                        color: ColorsUtils.colorOut(vars.resister.fg),
                        backgroundColor: ColorsUtils.colorOut(vars.resister.states.bg),
                    },
                }),
            },
        },
    });

    const clearButtonClass = style("clearButtonClass", {
        // opacity: 0.7,
        //     "&:hover, &:focus": {
        //         opacity: 1,
        //     },
        // },
    });

    const guestButton = style("guestButton", {
        minWidth: styleUnit(vars.button.guest.minWidth),
        borderRadius: styleUnit(vars.button.borderRadius),
    });

    const desktopNavWrap = style("desktopNavWrap", {
        position: "relative",
        flexGrow: 1,
        ...(addGradientsToHintOverflow(globalVars.gutter.half * 4, vars.colors.bg) as any),
    });

    const logoCenterer = style("logoCenterer", {
        ...absolutePosition.middleOfParent(true),
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
    });

    const logoLeftAligned = style("logoLeftAligned", {
        position: "relative",
        height: percent(100),
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
    });

    const hamburger = style("hamburger", {
        marginRight: styleUnit(12),
        marginLeft: negativeUnit(globalVars.buttonIcon.offset),
        ...{
            "&&": {
                ...allButtonStates({
                    allStates: {
                        color: ColorsUtils.colorOut(vars.colors.fg),
                    },
                }),
            },
        },
    });

    const isSticky = style("isSticky", {
        ...sticky(),
        top: 0,
        zIndex: 10,
    });

    const logoAnimationWrap = style("logoAnimationWrap", {
        display: "inline-flex",
        alignItems: "center",
    });

    const overlay = style("overlay", {
        ...absolutePosition.fullSizeOfParent(),
        background: vars.overlay.background,
    });

    const signInIconOffset = style("signInIconOffset", {
        marginRight: negativeUnit(globalVars.buttonIcon.offset + 3),
    });

    const titleBarContainer = style("titleBarContainer", {
        ...Mixins.border(vars.titleBarContainer.border),
    });

    const skipNav = style("skipNav", {
        position: "absolute",
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
        color: "gray",
        border: 0,
        borderRadius: styleUnit(6),
        clip: "rect(0 0 0 0)",
        height: styleUnit(0),
        width: styleUnit(0),
        margin: styleUnit(-1),
        padding: 0,
        overflow: "hidden",
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        ...{
            "&:focus, &:active": {
                // This is over the icon and we want it to be a little further to the left of the main nav
                left: styleUnit(-40),
                width: styleUnit(144),
                height: styleUnit(38),
                clip: "auto",
            },
        },
    });

    return {
        root,
        bg1,
        bg2,
        container,
        bgContainer,
        bgImage,
        negativeSpacer,
        bannerPadding,
        spacer,
        bar,
        logoContainer,
        meBox,
        nav,
        locales,
        messages,
        notifications,
        compactSearch,
        topElement,
        localeToggle,
        languages,
        button,
        buttonOffset,
        linkButton,
        searchCancel,
        tabButton,
        dropDownContents,
        count,
        extraMeBoxIcons,
        rightFlexBasis,
        leftFlexBasis,
        signIn,
        register,
        centeredButton,
        compactSearchResults,
        clearButtonClass,
        guestButton,
        logoFlexBasis,
        desktopNavWrap,
        logoCenterer,
        logoLeftAligned,
        hamburger,
        isSticky,
        logoAnimationWrap,
        overlay,
        swoop,
        signInIconOffset,
        titleBarContainer,
        skipNav,
    };
});

const getLogoMaxHeight = (vars, mobile: boolean) => {
    const titleBarHeight = mobile ? vars.sizing.mobile ?? vars.sizing.height : vars.sizing.height;
    let specifiedLogoHeight = mobile
        ? vars.logo.mobile.maxHeight ?? vars.logo.maxHeight ?? vars.sizing.mobile.height
        : vars.logo.maxHeight ?? vars.sizing.height;

    // Make sure it doesn't go over the size of the title bar
    if (specifiedLogoHeight > titleBarHeight) {
        specifiedLogoHeight = titleBarHeight;
    }

    return specifiedLogoHeight - (mobile ? vars.logo.mobile.heightOffset : vars.logo.heightOffset);
};

export const titleBarLogoClasses = useThemeCache(() => {
    const vars = titleBarVariables();
    const style = styleFactory("titleBarLogo");
    const mediaQueries = vars.mediaQueries();

    const logoFrame = style("logoFrame", {
        display: "inline-flex",
        alignSelf: "center",
        justifyContent: "center",
    });

    const mobileLogoStyles = {
        display: "flex",
        justifyContent: vars.mobileLogo.justifyContent,
        maxHeight: styleUnit(getLogoMaxHeight(vars, true)),
        maxWidth: styleUnit(vars.logo.mobile.maxWidth ?? vars.logo.maxWidth),
    };

    const logo = style(
        "logo",
        {
            display: "block",
            maxHeight: styleUnit(getLogoMaxHeight(vars, false)),
            maxWidth: styleUnit(vars.logo.maxWidth),
            width: "auto",
            ...{
                "&.isCentred": {
                    margin: "auto",
                },
            },
        },
        mediaQueries.compact(mobileLogoStyles),
    );

    const mobileLogo = style("mobileLogo", mobileLogoStyles);

    const isCenter = style("isCenter", {
        position: "absolute",
        left: percent(50),
        transform: translate(`-50%`, `-50%`),
    });

    return {
        logoFrame,
        logo,
        mobileLogo,
        isCenter,
    };
});

export const addGradientsToHintOverflow = (width: number | string, color: ColorHelper) => {
    return {
        "&:after": {
            ...absolutePosition.topRight(),
            background: linearGradient(
                "right",
                `${ColorsUtils.colorOut(color.fade(0))} 0%`,
                `${ColorsUtils.colorOut(color.fade(0.3))} 20%`,
                `${ColorsUtils.colorOut(color)} 90%`,
            ),
        },
        "&:before": {
            ...absolutePosition.topLeft(),
            background: linearGradient(
                "left",
                `${ColorsUtils.colorOut(color.fade(0))} 0%`,
                `${ColorsUtils.colorOut(color.fade(0.3))} 20%`,
                `${ColorsUtils.colorOut(color)} 90%`,
            ),
        },
        "&:before, &:after": {
            ...pointerEvents(),
            content: quote(``),
            height: percent(100),
            width: styleUnit(width),
            zIndex: 1,
        },
    };
};
