/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorHelper, important, scale } from "csx";
import { pointerEvents } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Property } from "csstype";
import { TLength } from "@library/styles/styleShim";
import { css, cx } from "@emotion/css";
import { IconSize } from "@vanilla/icons";

interface IPathState {
    stroke?: ColorHelper | Property.Stroke;
    fill?: ColorHelper | Property.Fill;
    opacity?: Property.Opacity;
    strokeWidth?: Property.StrokeWidth<TLength>;
}

interface INestedPathState extends IPathState {
    state?: IPathState;
}

interface IBookmarkLoading extends INestedPathState {
    halfFill?: ColorHelper | string;
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
        stroke = props.stroke ? ColorsUtils.colorOut(props.stroke) : props.stroke;
    }

    let fill = props.fill;
    if (fill !== "none") {
        fill = props.fill ? ColorsUtils.colorOut(props.fill) : props.fill;
    }

    return {
        stroke: stroke,
        fill: fill,
        strokeWidth: props.strokeWidth ? styleUnit(props.strokeWidth) : undefined,
        opacity: props.opacity,
    };
};

export const iconVariables = useThemeCache(() => {
    const themeVars = variableFactory("defaultIconSizes");

    const standard = themeVars("defaultIcon", {
        width: IconSize.default,
        height: IconSize.default,
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
        width: 79,
        height: 33,
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

    const chevronUp = themeVars("selectedCategory", {
        width: 51,
        height: 17,
    });

    const plusCircle = themeVars("plusCircle", {
        width: 16,
        height: 16,
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

    const _bookmarkIcon = themeVars("_bookmarkIcon", {
        width: 12,
        height: 16,
        strokeWidth: 1,
    });

    const newPostMenuIcon = themeVars("newPostMenuIcon", {
        width: 16,
        height: 16,
    });

    // Search Types
    const typeAll = themeVars("typeAll", {
        width: 13,
        height: 13,
    });
    const typeDiscussions = themeVars("typeDiscussions", {
        width: 18.869,
        height: 15.804,
    });
    const typeArticles = themeVars("typeArticles", {
        width: 14.666,
        height: 14.666,
    });
    const typeCategoriesAndGroups = themeVars("TypeCategoriesAndGroups", {
        width: 15,
        height: 16.28,
    });

    const typePlaces = themeVars("TypePlaces", {
        width: 15 * 1.2,
        height: 16.28 * 1.2,
    });

    const typeFlag = themeVars("TypeFlag", {
        width: 26,
        height: 26,
    });

    const typeMember = themeVars("TypeMember", {
        width: 20,
        height: 20,
    });

    const typeIdeasIcon = themeVars("TypeIdeasIcon", {
        width: 18.444,
        height: 16.791,
    });

    const typePollsIcon = themeVars("TypePollsIcon", {
        width: 26,
        height: 26,
    });

    const typeQuestion = themeVars("TypeQuestion", {
        width: 26,
        height: 26,
    });

    const downvote = themeVars("downvote", {
        width: 24,
        height: 24,
    });

    const upvote = themeVars("upvote", {
        width: 24,
        height: 24,
    });

    const siteTotalMembersIcon = themeVars("SiteTotalMembersIcon", {
        width: 24,
        height: 24,
    });

    const siteTotalPostsIcon = themeVars("SiteTotalPostsIcon", {
        width: 24,
        height: 24,
    });

    const siteTotalOnlineIcon = themeVars("SiteTotalOnlineIcon", {
        width: 24,
        height: 24,
    });

    return {
        standard,
        newFolder,
        fileType,
        attachmentError,
        vanillaLogo,
        compact,
        settings,
        warning,
        user,
        userWarning,
        close,
        closeCompact,
        closeTiny,
        chevronLeftCompact,
        selectedCategory,
        chevronUp,
        plusCircle,
        categoryIcon,
        deleteIcon,
        editIcon,
        documentation,
        /**
         * @deprecated
         */
        _bookmarkIcon,
        newPostMenuIcon,
        typeAll,
        typeDiscussions,
        typeArticles,
        typeCategoriesAndGroups,
        typeMember,
        typeIdeasIcon,
        typePollsIcon,
        typeQuestion,
        typePlaces,
        typeFlag,
        downvote,
        upvote,
        siteTotalMembersIcon,
        siteTotalPostsIcon,
        siteTotalOnlineIcon,
    };
});

export const iconClasses = useThemeCache(() => {
    const vars = iconVariables();
    const globalVars = globalVariables();
    const style = styleFactory("icon");

    const standard = css({
        width: styleUnit(vars.standard.width),
        height: styleUnit(vars.standard.height),
    });

    const fileType = style("fileType", {
        width: styleUnit(vars.fileType.width),
        height: styleUnit(vars.fileType.height),
    });

    const newFolder = style("newFolder", {
        width: styleUnit(vars.newFolder.width),
        height: styleUnit(vars.newFolder.height),
        paddingRight: styleUnit(1),
    });

    const attachmentError = style("attachmentError", {
        width: styleUnit(vars.attachmentError.width),
        height: styleUnit(vars.attachmentError.height),
    });

    const vanillaLogo = style("vanillaLogo", {
        width: styleUnit(vars.vanillaLogo.width),
        height: styleUnit(vars.vanillaLogo.height),
    });

    const vanillaLogoMobile = style("vanillaLogoMobile", {
        width: styleUnit(vars.vanillaLogo.mobile.width ?? vars.vanillaLogo.width),
        height: styleUnit(vars.vanillaLogo.mobile.height ?? vars.vanillaLogo.height),
    });

    const compact = style("compact", {
        width: styleUnit(vars.compact.width),
        height: styleUnit(vars.compact.height),
    });

    const settings = style("settings", {
        width: styleUnit(vars.settings.width),
        height: styleUnit(vars.settings.height),
    });

    const triangeTiny = style("triangeTiny", {
        width: styleUnit(8),
        height: styleUnit(8),
    });

    const warning = style("warning", {
        width: styleUnit(vars.warning.width),
        height: styleUnit(vars.warning.height),
    });

    const user = style("user", {
        width: styleUnit(vars.user.width),
        height: styleUnit(vars.user.height),
    });

    const userWarning = style("userWarning", {
        width: styleUnit(vars.userWarning.width),
        height: styleUnit(vars.userWarning.height),
    });

    const close = style("close", {
        width: styleUnit(vars.close.width),
        height: styleUnit(vars.close.height),
    });

    // Same as close, but without extra padding
    const closeCompact = style("closeCompact", {
        width: styleUnit(vars.closeCompact.width),
        height: styleUnit(vars.closeCompact.height),
    });

    // For really small close buttons, like on tokens
    const closeTiny = style("closeTiny", {
        display: "block",
        width: styleUnit(vars.closeTiny.width),
        height: styleUnit(vars.closeTiny.height),
        margin: "auto",
    });

    const chevronLeftCompact = style("chevronLeftCompact", {
        width: styleUnit(vars.chevronLeftCompact().width),
        height: styleUnit(vars.chevronLeftCompact().height),
    });

    const chevronLeftSmallCompact = style("chevronLeftSmallCompact", {
        ...{
            [`&&, &.${chevronLeftCompact}`]: {
                width: styleUnit(vars.chevronLeftCompact(true).width),
                height: styleUnit(vars.chevronLeftCompact(true).height),
            },
        },
    });

    const selectedCategory = style("selectedCategory", {
        width: styleUnit(vars.selectedCategory.width),
        height: styleUnit(vars.selectedCategory.height),
    });

    const chevronUp = style("chevronUp", {
        width: styleUnit(vars.chevronUp.width),
        height: styleUnit(vars.chevronUp.height),
    });

    const plusCircle = style("plusCircle", {
        width: styleUnit(vars.plusCircle.width),
        height: styleUnit(vars.plusCircle.height),
    });

    const categoryIcon = style("categoryIcon", {
        width: styleUnit(vars.categoryIcon.width),
        height: styleUnit(vars.categoryIcon.height),
        opacity: vars.categoryIcon.opacity,
        marginRight: styleUnit(3),
    });

    const deleteIcon = style("deleteIcon", {
        width: styleUnit(vars.deleteIcon.width),
        height: styleUnit(vars.deleteIcon.height),
    });

    const isSmall = style("isSmall", {
        transform: scale(0.85),
        transformOrigin: "50% 50%",
    });

    const editIcon = style("editIcon", {
        width: styleUnit(vars.editIcon.width),
        height: styleUnit(vars.editIcon.height),
    });

    const discussionIcon = style("discussionIcon", {
        width: styleUnit(vars.standard.width),
        height: styleUnit(vars.standard.height),
    });

    const hamburger = style("hamburgerIcon", {
        width: styleUnit(vars.standard.width),
        height: styleUnit(vars.standard.height),
    });

    const featureIcon = style("featureIcon", {
        width: 22,
        height: 22,
    });

    const errorFgColor = style("errorFgColor", {
        color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
    });

    const warningFgColor = style("warningFgColor", {
        color: ColorsUtils.colorOut(globalVars.messageColors.warning.fg),
    });

    const documentation = style("documentation", {
        display: "block",
        width: styleUnit(vars.documentation.width),
        height: styleUnit(vars.documentation.height),
    });

    // Goes on link, not SVG to handle states
    const _bookmark = (
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
                stroke: globalVars.mainColors.fg,
                fill: "none",
                state: {
                    stroke: mainColors.statePrimary,
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
            svg: {
                width: styleUnit(vars._bookmarkIcon.width),
                height: styleUnit(vars._bookmarkIcon.height),
                opacity: 1,
                display: "block",
                position: "relative",
            },
            ".svgBookmark": {
                ...pointerEvents(),
            },
            ".svgBookmark-mainPath": svgStyles(normalState),
            "&.Bookmarked:not(.Bookmarking) .svgBookmark-mainPath": svgStyles(bookmarkedState),
            "&:hover:not(.Bookmarked) .svgBookmark-mainPath": svgStyles(normalState.state),
            "&:focus:not(.Bookmarked) .svgBookmark-mainPath": svgStyles(normalState.state),
            "&:active:not(.Bookmarked) .svgBookmark-mainPath": svgStyles(normalState.state),
            "&.Bookmarking .svgBookmark-mainPath": svgStyles({
                fill: important("none"),
                opacity: loadingState.opacity,
            }),
            ".svgBookmark-loadingPath": {
                display: "none",
            },
            "&.Bookmarking .svgBookmark-loadingPath": {
                display: "block",
                ...svgStyles(loadingState, true),
            },
        });
    };

    const newPostMenuIcon = style("newPostMenuIcon", {
        width: styleUnit(vars.newPostMenuIcon.width),
        height: styleUnit(vars.newPostMenuIcon.height),
        color: ColorsUtils.colorOut(globalVars.mainColors.primaryContrast),
        margin: "auto",
    });

    const itemFlyout = style("itemFlyout", {
        width: styleUnit(vars.standard.width),
        height: styleUnit(vars.standard.height),
    });

    // Search types
    const typeAll = style("typeAll", {
        width: styleUnit(vars.typeAll.width),
        height: styleUnit(vars.typeAll.height),
    });
    const typeDiscussions = style("typeDiscussions", {
        width: styleUnit(vars.typeDiscussions.width),
        height: styleUnit(vars.typeDiscussions.height),
    });
    const typeArticles = style("typeArticles", {
        width: styleUnit(vars.typeArticles.width),
        height: styleUnit(vars.typeArticles.height),
    });
    const typeCategoriesAndGroups = style("TypeCategoriesAndGroups", {
        width: styleUnit(vars.typeCategoriesAndGroups.width),
        height: styleUnit(vars.typeCategoriesAndGroups.height),
    });
    const typeMember = style("TypeMember", {
        width: styleUnit(vars.typeMember.width),
        height: styleUnit(vars.typeMember.height),
    });
    const typeIdeasIcon = style("TypeIdeasIcon", {
        width: styleUnit(vars.typeIdeasIcon.width),
        height: styleUnit(vars.typeIdeasIcon.height),
    });
    const typePollsIcon = style("TypePollsIcon", {
        width: styleUnit(vars.typePollsIcon.width),
        height: styleUnit(vars.typePollsIcon.height),
    });
    const typeQuestion = style("TypeQuestion", {
        width: styleUnit(vars.typeQuestion.width),
        height: styleUnit(vars.typeQuestion.height),
    });

    const typePlaces = style("TypePlaces", {
        width: styleUnit(vars.typePlaces.width),
        height: styleUnit(vars.typePlaces.height),
    });

    const typeFlag = style("TypeFlag", {
        width: styleUnit(vars.typeFlag.width),
        height: styleUnit(vars.typeFlag.height),
    });

    const typeGroups = style("TypeGroups", {
        width: styleUnit(vars.typeQuestion.width),
        height: styleUnit(vars.typeQuestion.height),
    });

    const typeKnowledgeBase = style("TypeKnowledgeBase", {
        width: styleUnit(vars.typeQuestion.width),
        height: styleUnit(vars.typeQuestion.height),
    });

    const downvote = css({
        width: styleUnit(vars.downvote.width),
        height: styleUnit(vars.downvote.height),
        stroke: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    const upvote = cx(
        downvote,
        css({
            transform: "rotate(180deg)",
        }),
    );

    const siteTotalMembersIcon = css({
        width: styleUnit(vars.siteTotalMembersIcon.width),
        height: styleUnit(vars.siteTotalMembersIcon.height),
    });

    const siteTotalPostsIcon = css({
        width: styleUnit(vars.siteTotalPostsIcon.width),
        height: styleUnit(vars.siteTotalPostsIcon.height),
    });

    const siteTotalOnlineIcon = css({
        width: styleUnit(vars.siteTotalOnlineIcon.width),
        height: styleUnit(vars.siteTotalOnlineIcon.height),
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
        user,
        userWarning,
        close,
        closeCompact,
        closeTiny,
        triangeTiny,
        chevronLeftCompact,
        chevronLeftSmallCompact,
        selectedCategory,
        chevronUp,
        plusCircle,
        categoryIcon,
        deleteIcon,
        editIcon,
        discussionIcon,
        isSmall,
        hamburger,
        featureIcon,
        documentation,
        /**
         * @deprecated
         */
        _bookmark,
        newPostMenuIcon,
        itemFlyout,
        typeAll,
        typeDiscussions,
        typeArticles,
        typeCategoriesAndGroups,
        typeMember,
        typeIdeasIcon,
        typePollsIcon,
        typeQuestion,
        typePlaces,
        typeFlag,
        typeGroups,
        typeKnowledgeBase,
        downvote,
        upvote,
        siteTotalMembersIcon,
        siteTotalPostsIcon,
        siteTotalOnlineIcon,
    };
});
