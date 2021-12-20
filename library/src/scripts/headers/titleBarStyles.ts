/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
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
import { css, CSSObject } from "@emotion/css";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { buttonResetMixin } from "@library/forms/buttonMixins";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { titleBarVariables } from "./TitleBar.variables";
import { ButtonTypes } from "@library/forms/buttonTypes";

export const titleBarClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = titleBarVariables();
    const formElementVars = formElementsVariables();
    const mediaQueries = vars.mediaQueries();
    const flex = flexHelper();

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

    const root = css({
        maxWidth: percent(100),
        color: ColorsUtils.colorOut(vars.colors.fg),
        position: "relative",
        ...mediaQueries.compact({
            color: ColorsUtils.colorOut(vars.mobileColors.fg),
        }),
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

    const swoop = css({});

    const shadowAsBorder =
        vars.border.type === BorderType.SHADOW_AS_BORDER
            ? { boxShadow: `0 ${styleUnit(vars.border.width)} 0 ${ColorsUtils.colorOut(vars.border.color)}` }
            : {};

    const bg1 = css(
        {
            willChange: "opacity",
            ...Mixins.absolute.fullSizeOfParent(),
            backgroundColor: ColorsUtils.colorOut(vars.fullBleed.enabled ? vars.fullBleed.bgColor : vars.colors.bg),
            ...shadowAsBorder,
            overflow: "hidden",
            [`&.${swoop}`]: swoopStyles,
        },
        mediaQueries.compact({
            backgroundColor: ColorsUtils.colorOut(
                vars.fullBleed.enabled ? vars.fullBleed.bgColor : vars.mobileColors.bg,
            ),
        }),
    );

    const bg2 = css(
        {
            willChange: "opacity",
            ...Mixins.absolute.fullSizeOfParent(),
            backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
            ...shadowAsBorder,
            overflow: "hidden",
            ...{
                [`&.${swoop}`]: swoopStyles,
            },
        },
        mediaQueries.compact({
            backgroundColor: ColorsUtils.colorOut(vars.mobileColors.bg),
        }),
    );

    const container = css({
        position: "relative",
        height: percent(100),
        width: percent(100),
        ...Mixins.padding(vars.spacing.padding),
    });

    const bgContainer = css({
        ...Mixins.absolute.fullSizeOfParent(),
        height: percent(100),
        width: percent(100),
        ...Mixins.padding(vars.spacing.padding),
        boxSizing: "content-box",
        overflow: "hidden",
    });

    const bgImage = css({
        ...Mixins.absolute.fullSizeOfParent(),
        objectFit: "cover",
    });

    const bannerPadding = css(
        {
            paddingTop: px(vars.sizing.height / 2),
        },
        mediaQueries.compact({
            paddingTop: px(vars.sizing.mobile.height / 2 + 20),
        }),
    );

    const negativeSpacer = css(
        {
            marginTop: px(-vars.sizing.height),
        },
        mediaQueries.compact({
            marginTop: px(-vars.sizing.mobile.height),
        }),
    );

    const spacer = css(
        {
            height: px(vars.sizing.height),
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const bar = css(
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

    const logoContainer = css(
        {
            display: "inline-flex",
            alignSelf: "center",
            marginRight: styleUnit(vars.logo.offsetRight),
            justifyContent: vars.logo.justifyContent,
            ...logoOffsetDesktop,
            maxHeight: percent(100),
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
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
            marginRight: styleUnit(0),
            ...logoOffsetMobile,
            "&&": {
                color: ColorsUtils.colorOut(vars.mobileColors.fg),
            },
        }),
    );

    const logoFlexBasis = css({
        flexBasis: vars.endElements.flexBasis,
    });

    const meBox = css({
        justifyContent: "flex-end",
    });

    const nav = css(
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

    const locales = css(
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

    const messages = css({
        color: vars.colors.fg.toString(),
    });

    const notifications = css({
        color: "inherit",
    });

    const compactSearch = css(
        {
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            marginLeft: "auto",
            minWidth: styleUnit(formElementVars.sizing.height),
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

    const compactSearchResults = css(
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
        oneColumnVariables()
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

    const extraMeBoxIcons = css({
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

    const topElement = css(
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

    const localeToggle = css(
        {
            height: px(vars.sizing.height),
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
        }),
    );

    const languages = css({
        marginLeft: "auto",
    });

    const button = css(
        {
            ...buttonResetMixin(),
            height: px(vars.button.size),
            minWidth: px(vars.button.size),
            maxWidth: percent(100),
            padding: px(0),
            "&&": {
                ...allButtonStates(
                    {
                        allStates: {
                            color: ColorsUtils.colorOut(vars.colors.fg),
                            ".meBox-buttonContent": {
                                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
                            },
                        },
                        keyboardFocus: {
                            outline: 0,
                            color: ColorsUtils.colorOut(vars.colors.fg),
                            ".meBox-buttonContent": {
                                borderColor: ColorsUtils.colorOut(vars.colors.fg),
                                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
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
                ),
            },
        },
        mediaQueries.compact({
            height: px(vars.sizing.mobile.height),
            width: px(vars.sizing.mobile.width),
            minWidth: px(vars.sizing.mobile.width),

            "&&": {
                ...allButtonStates({
                    allStates: {
                        color: ColorsUtils.colorOut(vars.mobileColors.fg),
                    },
                    keyboardFocus: {
                        outline: 0,
                        color: ColorsUtils.colorOut(vars.mobileColors.fg),
                        ".meBox-buttonContent": {
                            borderColor: ColorsUtils.colorOut(vars.mobileColors.fg),
                        },
                    },
                }),
            },
        }),
    );

    const linkButton = css(Mixins.button(vars.linkButton));

    const buttonOffset = css({
        transform: `translateX(6px)`,
    });

    const centeredButton = css({
        ...flex.middle(),
    });

    const searchCancel = css({
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

    const tabButton = css({
        display: "block",
        height: percent(100),
        padding: px(0),
        ...{
            "&:active": tabButtonActive,
            "&:hover": tabButtonActive,
            "&:focus": tabButtonActive,
        },
    });

    const dropDownContents = css({
        ...{
            "&&&": {
                minWidth: styleUnit(vars.dropDownContents.minWidth),
                maxHeight: styleUnit(vars.dropDownContents.maxHeight),
            },
        },
    });

    const count = css({
        height: px(vars.count.size),
        fontSize: px(vars.count.fontSize),
        backgroundColor: vars.count.bg.toString(),
        color: vars.count.fg.toString(),
    });

    const rightFlexBasis = css(
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

    const leftFlexBasis = css({
        ...flex.middleLeft(),
        flexShrink: 1,
        flexBasis: px(vars.endElements.mobile.flexBasis),
    });

    const signIn = css(
        vars.guest.signInButtonType === ButtonTypes.TRANSPARENT && {
            "&&&": {
                color: ColorsUtils.colorOut(vars.signIn.fg),
                borderColor: ColorsUtils.colorOut(vars.signIn.border.color),
            },
        },
    );

    const register = css(
        vars.guest.signInButtonType === ButtonTypes.TRANSLUCID && {
            backgroundColor: ColorsUtils.colorOut(vars.resister.bg),
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
    );

    const clearButtonClass = css({});

    const guestButton = css({
        "&&": {
            marginLeft: styleUnit(vars.guest.spacer),
            marginRight: styleUnit(vars.guest.spacer),
            minWidth: styleUnit(vars.button.guest.minWidth),
            borderRadius: styleUnit(vars.button.borderRadius),
            ...Mixins.font({
                textDecoration: "none",
            }),

            "&:last-child": {
                marginRight: 0,
            },
        },
    });

    const desktopNavWrap = css({
        position: "relative",
        flexGrow: 1,
        ...(addGradientsToHintOverflow(globalVars.gutter.half * 4, vars.colors.bg) as any),
    });

    const logoCenterer = css({
        ...Mixins.absolute.middleOfParent(true),
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
    });

    const logoLeftAligned = css({
        position: "relative",
        height: percent(100),
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
    });

    const hamburger = css(
        {
            marginRight: styleUnit(12),
            marginLeft: negativeUnit(globalVars.buttonIcon.offset),
            "&&": {
                ...allButtonStates({
                    allStates: {
                        color: ColorsUtils.colorOut(vars.colors.fg),
                    },
                }),
            },
        },
        mediaQueries.compact({
            "&&": {
                ...allButtonStates({
                    allStates: {
                        color: ColorsUtils.colorOut(vars.mobileColors.fg),
                    },
                }),
            },
        }),
    );

    const isSticky = css({
        ...sticky(),
        top: 0,
        zIndex: 10,
    });

    const logoAnimationWrap = css({
        display: "inline-flex",
        alignItems: "center",
    });

    const overlay = css({
        ...Mixins.absolute.fullSizeOfParent(),
        background: vars.overlay.background,
    });

    const signInIconOffset = css({
        marginRight: negativeUnit(globalVars.buttonIcon.offset + 3),
    });

    const titleBarContainer = css({
        ...Mixins.border(vars.titleBarContainer.border),
    });

    const skipNav = css({
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
    const mediaQueries = vars.mediaQueries();

    const logoFrame = css({
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

    const logo = css(
        {
            display: "block",
            maxHeight: styleUnit(getLogoMaxHeight(vars, false)),
            maxWidth: styleUnit(vars.logo.maxWidth),
            ...{
                "&.isCentred": {
                    margin: "auto",
                },
            },
        },
        mediaQueries.compact(mobileLogoStyles),
    );

    const mobileLogo = css(mobileLogoStyles);

    const isCenter = css({
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
            ...Mixins.absolute.topRight(),
            background: linearGradient(
                "right",
                `${ColorsUtils.colorOut(color.fade(0))} 0%`,
                `${ColorsUtils.colorOut(color.fade(0.3))} 20%`,
                `${ColorsUtils.colorOut(color)} 90%`,
            ),
        },
        "&:before": {
            ...Mixins.absolute.topLeft(),
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
