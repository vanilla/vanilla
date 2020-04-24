/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { important, scale } from "csx";
import { unit, colorOut, pointerEvents, ColorValues } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { FillProperty, OpacityProperty, StrokeProperty, StrokeWidthProperty } from "csstype";
import { TLength } from "typestyle/lib/types";

interface IPathState {
    stroke?: ColorValues | StrokeProperty;
    fill?: ColorValues | FillProperty;
    opacity?: OpacityProperty;
    strokeWidth?: StrokeWidthProperty<TLength>;
}

interface INestedPathState extends IPathState {
    state?: IPathState;
}

interface IBookmarkLoading extends INestedPathState {
    halfFill?: ColorValues | string;
}

export interface IBookmarkProps {
    normalState: INestedPathState | undefined;
    loadingState: IBookmarkLoading | undefined;
    bookmarkedState: INestedPathState | undefined;
}

export const svgStyles = (props: INestedPathState | undefined, debug?: boolean) => {
    if (props === undefined) {
        return {};
    }
    let stroke = props.stroke;
    if (stroke !== "none") {
        stroke = props.stroke ? colorOut(props.stroke) : props.stroke;
    }

    let fill = props.fill;
    if (fill !== "none") {
        fill = props.fill ? colorOut(props.fill) : props.fill;
    }

    return {
        stroke: stroke,
        fill: fill,
        strokeWidth: props.strokeWidth ? unit(props.strokeWidth) : undefined,
        opacity: props.opacity,
    };
};

export const iconVariables = useThemeCache(() => {
    const themeVars = variableFactory("defaultIconSizes");

    const standard = themeVars("defaultIcon", {
        width: 24,
        height: 24,
    });

    const fileType = themeVars("defaultIcon", {
        width: 16,
        height: 16,
    });

    const newFolder = themeVars("newFolderIcon", {
        width: 17,
        height: 14.67,
    });

    const attachmentError = themeVars("attachmentError", {
        width: 20,
        height: 18,
    });

    const vanillaLogo = themeVars("vanillaLogo", {
        width: 80,
        height: 32.3,
        mobile: {
            width: undefined,
            height: undefined,
        },
    });

    const compact = themeVars("compact", {
        width: 12,
        height: 12,
    });

    const warning = themeVars("warning", {
        width: 16,
        height: 16,
    });

    const settings = themeVars("settings", {
        width: 20,
        height: 18,
    });

    const search = themeVars("settings", {
        width: 18,
        height: 20,
    });

    const notifications = themeVars("settings", {
        width: 18,
        height: 20,
    });

    const messages = themeVars("messages", {
        width: 20.051,
        height: 14.016,
    });

    const user = themeVars("user", {
        width: 20,
        height: 20,
    });

    const userWarning = themeVars("userWarning", {
        width: 40,
        height: 40,
    });

    const close = themeVars("close", {
        width: 20,
        height: 20,
    });

    const closeCompact = themeVars("closeCompact", {
        width: 16,
        height: 16,
    });

    const closeTiny = themeVars("closeTiny", {
        width: 10,
        height: 10,
    });

    const chevronLeftCompact = (isSmall?: boolean) => {
        const defaultWidth = 12;
        const defaultHeight = 21;
        const smallHeight = 16; // width is calculated

        const width = !isSmall ? defaultWidth : (smallHeight * defaultWidth) / defaultHeight;
        const height = !isSmall ? defaultHeight : smallHeight;

        return themeVars("chevronLeftCompact", {
            width: width,
            height: height,
        });
    };

    const selectedCategory = themeVars("selectedCategory", {
        width: 16.8,
        height: 13,
    });

    const signIn = themeVars("signIn", {
        width: 24,
        height: 18,
    });

    const chevronUp = themeVars("selectedCategory", {
        width: 51,
        height: 17,
    });

    const plusCircle = themeVars("plusCircle", {
        width: 14,
        height: 14,
    });

    const deleteIcon = themeVars("deleteIcon", {
        width: 24,
        height: 24,
    });

    const editIcon = themeVars("editIcon", {
        width: 24,
        height: 24,
    });

    const categoryIcon = themeVars("categoryIcon", {
        width: 18,
        height: 18,
        opacity: 0.8,
    });

    const documentation = themeVars("documentation", {
        width: 12.6,
        height: 16.02,
    });

    const bookmarkIcon = themeVars("bookmarkIcon", {
        width: 12,
        height: 16,
        strokeWidth: 1,
    });

    const newPostMenuIcon = themeVars("newPostMenuIcon", {
        width: 16,
        height: 16,
    });

    // const itemFlyoutIcon = themeVars("itemFlyoutIcon", {
    //     width: 24,
    //     height: 24,
    // });

    return {
        standard,
        newFolder,
        fileType,
        attachmentError,
        vanillaLogo,
        compact,
        settings,
        warning,
        search,
        notifications,
        messages,
        user,
        userWarning,
        close,
        closeCompact,
        closeTiny,
        chevronLeftCompact,
        selectedCategory,
        signIn,
        chevronUp,
        plusCircle,
        categoryIcon,
        deleteIcon,
        editIcon,
        documentation,
        bookmarkIcon,
        newPostMenuIcon,
        // itemFlyoutIcon,
    };
});

export const iconClasses = useThemeCache(() => {
    const vars = iconVariables();
    const globalVars = globalVariables();
    const style = styleFactory("icon");

    const standard = style("defaultIcon", {
        width: unit(vars.standard.width),
        height: unit(vars.standard.height),
    });

    const fileType = style("fileType", {
        width: unit(vars.fileType.width),
        height: unit(vars.fileType.height),
    });

    const newFolder = style("newFolder", {
        width: unit(vars.newFolder.width),
        height: unit(vars.newFolder.height),
        paddingRight: unit(1),
    });

    const attachmentError = style("attachmentError", {
        width: unit(vars.attachmentError.width),
        height: unit(vars.attachmentError.height),
    });

    const vanillaLogo = style("vanillaLogo", {
        width: unit(vars.vanillaLogo.width),
        height: unit(vars.vanillaLogo.height),
    });

    const vanillaLogoMobile = style("vanillaLogoMobile", {
        width: unit(vars.vanillaLogo.mobile.width ?? vars.vanillaLogo.width),
        height: unit(vars.vanillaLogo.mobile.height ?? vars.vanillaLogo.height),
    });

    const compact = style("compact", {
        width: unit(vars.compact.width),
        height: unit(vars.compact.height),
    });

    const settings = style("settings", {
        width: unit(vars.settings.width),
        height: unit(vars.settings.height),
    });

    const warning = style("warning", {
        width: unit(vars.warning.width),
        height: unit(vars.warning.height),
    });

    const search = style("search", {
        width: unit(vars.search.width),
        height: unit(vars.search.height),
    });

    const notifications = style("notifications", {
        width: unit(vars.notifications.width),
        height: unit(vars.notifications.height),
    });

    const messages = style("messages", {
        width: unit(vars.messages.width),
        height: unit(vars.messages.height),
    });

    const user = style("user", {
        width: unit(vars.user.width),
        height: unit(vars.user.height),
    });

    const userWarning = style("userWarning", {
        width: unit(vars.userWarning.width),
        height: unit(vars.userWarning.height),
    });

    const close = style("close", {
        width: unit(vars.close.width),
        height: unit(vars.close.height),
    });

    // Same as close, but without extra padding
    const closeCompact = style("closeCompact", {
        width: unit(vars.closeCompact.width),
        height: unit(vars.closeCompact.height),
    });

    // For really small close buttons, like on tokens
    const closeTiny = style("closeTiny", {
        display: "block",
        width: unit(vars.closeTiny.width),
        height: unit(vars.closeTiny.height),
        margin: "auto",
    });

    const chevronLeftCompact = style("chevronLeftCompact", {
        width: unit(vars.chevronLeftCompact().width),
        height: unit(vars.chevronLeftCompact().height),
    });

    const chevronLeftSmallCompact = style("chevronLeftSmallCompact", {
        $nest: {
            [`&&, &.${chevronLeftCompact}`]: {
                width: unit(vars.chevronLeftCompact(true).width),
                height: unit(vars.chevronLeftCompact(true).height),
            },
        },
    });

    const selectedCategory = style("selectedCategory", {
        width: unit(vars.selectedCategory.width),
        height: unit(vars.selectedCategory.height),
    });

    const signIn = style("signIn", {
        width: unit(vars.signIn.width),
        height: unit(vars.signIn.height),
    });

    const chevronUp = style("chevronUp", {
        width: unit(vars.chevronUp.width),
        height: unit(vars.chevronUp.height),
    });

    const plusCircle = style("plusCircle", {
        width: unit(vars.plusCircle.width),
        height: unit(vars.plusCircle.height),
    });

    const categoryIcon = style("categoryIcon", {
        width: unit(vars.categoryIcon.width),
        height: unit(vars.categoryIcon.height),
        opacity: vars.categoryIcon.opacity,
        marginRight: unit(3),
    });

    const deleteIcon = style("deleteIcon", {
        width: unit(vars.deleteIcon.width),
        height: unit(vars.deleteIcon.height),
    });

    const isSmall = style("isSmall", {
        transform: scale(0.85),
        transformOrigin: "50% 50%",
    });

    const editIcon = style("editIcon", {
        width: unit(vars.editIcon.width),
        height: unit(vars.editIcon.height),
    });

    const discussionIcon = style("discussionIcon", {
        width: unit(vars.standard.width),
        height: unit(vars.standard.height),
    });

    const globeIcon = style("globeIcon", {
        width: unit(vars.standard.width),
        height: unit(vars.standard.height),
    });

    const hamburger = style("alertIconCompact", {
        width: unit(vars.standard.width),
        height: unit(vars.standard.height),
    });

    const errorFgColor = style("errorFgColor", {
        color: colorOut(globalVars.messageColors.error.fg),
    });

    const warningFgColor = style("warningFgColor", {
        color: colorOut(globalVars.messageColors.warning.fg),
    });

    const documentation = style("documentation", {
        display: "block",
        width: unit(vars.documentation.width),
        height: unit(vars.documentation.height),
    });

    // Goes on link, not SVG to handle states
    const bookmark = (
        props: IBookmarkProps = {
            normalState: undefined as undefined | INestedPathState,
            loadingState: undefined as undefined | INestedPathState,
            bookmarkedState: undefined as undefined | INestedPathState,
        },
    ) => {
        const globalVars = globalVariables();
        const mainColors = globalVars.mainColors;

        const {
            normalState = {
                stroke: globalVars.mixPrimaryAndBg(0.7),
                fill: "none",
                state: {
                    stroke: mainColors.primary,
                },
            },
            loadingState = {
                opacity: 0.7,
                fill: mainColors.primary,
                strokeWidth: 0,
            },
            bookmarkedState = {
                stroke: mainColors.primary,
                fill: mainColors.primary,
            },
        } = props;

        return style("bookmark", {
            width: unit(vars.bookmarkIcon.width),
            height: unit(vars.bookmarkIcon.height),
            opacity: 1,
            display: "block",
            position: "relative",
            $nest: {
                "& .svgBookmark": {
                    ...pointerEvents(),
                },
                "& .svgBookmark-mainPath": svgStyles(normalState),
                "&.Bookmarked:not(.Bookmarking) .svgBookmark-mainPath": svgStyles(bookmarkedState),
                "&:hover:not(.Bookmarked) .svgBookmark-mainPath": svgStyles(normalState.state),
                "&:focus:not(.Bookmarked) .svgBookmark-mainPath": svgStyles(normalState.state),
                "&:active:not(.Bookmarked) .svgBookmark-mainPath": svgStyles(normalState.state),
                "&:Bookmarking .svgBookmark-mainPath": svgStyles({
                    fill: important("none"),
                    opacity: loadingState.opacity,
                }),
                "&:Bookmarking .svgBookmark-loadingPath": svgStyles({}),
                "& .svgBookmark-loadingPath": {
                    display: "none",
                },
                "&.Bookmarking .svgBookmark-loadingPath": {
                    display: "block",
                    ...svgStyles(loadingState, true),
                },
            },
        });
    };

    const newPostMenuIcon = style("newPostMenuIcon", {
        width: unit(vars.newPostMenuIcon.width),
        height: unit(vars.newPostMenuIcon.height),
        color: colorOut(globalVars.mainColors.primaryContrast),
        margin: "auto",
    });

    const itemFlyout = style("itemFlyout", {
        width: unit(vars.standard.width),
        height: unit(vars.standard.height),
    });

    return {
        standard,
        newFolder,
        warning,
        errorFgColor,
        warningFgColor,
        fileType,
        attachmentError,
        vanillaLogo,
        vanillaLogoMobile,
        compact,
        settings,
        search,
        notifications,
        messages,
        user,
        userWarning,
        close,
        closeCompact,
        closeTiny,
        chevronLeftCompact,
        chevronLeftSmallCompact,
        selectedCategory,
        signIn,
        chevronUp,
        plusCircle,
        categoryIcon,
        deleteIcon,
        editIcon,
        discussionIcon,
        globeIcon,
        isSmall,
        hamburger,
        documentation,
        bookmark,
        newPostMenuIcon,
        itemFlyout,
    };
});
