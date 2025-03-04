/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { hasMultipleSiteSections, t } from "@library/utility/appUtils";

export function getLayoutFeatureFlagKey(layoutViewType: LayoutViewType): string {
    switch (layoutViewType) {
        case "discussionCategoryPage":
        case "nestedCategoryList":
        case "categoryList":
            return "customLayout.categoryList";
        case "post":
            return "customLayout.post";
        case "subcommunityHome":
        case "home":
            return "customLayout.home";
        case "discussionList":
            return "customLayout.discussionList";
        case "createPost":
            return "customLayout.createPost";
        case "event":
            return "customLayout.event";
        case "knowledgeBase":
        case "guideArticle":
        case "helpCenterArticle":
        case "helpCenterCategory":
        case "knowledgeHome":
        case "helpCenterKnowledgeBase":
            return "customLayout.knowledgeBase";
        default:
            return "";
    }
}
export function getLayoutTypeSettingsUrl(layoutViewType: LayoutViewType): string {
    switch (layoutViewType) {
        case "discussionCategoryPage":
        case "nestedCategoryList":
        case "categoryList":
            return `/appearance/layouts/categoryList/legacy`;
        case "post":
            return `/appearance/layouts/post/legacy`;
        case "subcommunityHome":
        case "home":
            return `/appearance/layouts/home/legacy`;
        case "discussionList":
            return `/appearance/layouts/discussionList/legacy`;
        case "knowledgeBase":
        case "guideArticle":
        case "helpCenterArticle":
        case "helpCenterCategory":
        case "knowledgeHome":
        case "helpCenterKnowledgeBase":
            return `/appearance/layouts/knowledgeBase/legacy`;
        case "event":
            return `/appearance/layouts/event/legacy`;
        case "createPost":
            return `/appearance/layouts/createPost/legacy`;
        default:
            return "";
    }
}
export function getLayoutTypeSettingsLabel(layoutViewType: LayoutViewType): string {
    switch (layoutViewType) {
        case "discussionCategoryPage":
        case "nestedCategoryList":
        case "categoryList":
            return t("Category Layout Settings");
        case "post":
            return t("Post Layout Settings");
        case "subcommunityHome":
        case "home":
            return t("Home Layout Settings");
        case "discussionList":
            return t("Recent Posts Layout Settings");
        case "knowledgeBase":
            return t("Knowledge Base Layout Settings");
        case "createPost":
            return t("Create Post Layout Settings");
        case "event":
            return t("Event Layout Settings");
        default:
            return "";
    }
}
export function getLayoutTypeGroupLabel(layoutViewType: LayoutViewType): string {
    switch (layoutViewType) {
        case "discussionCategoryPage":
        case "nestedCategoryList":
            return t("Category Pages");
        case "subcommunityHome":
        case "home":
            return t("Home Pages");
        case "discussionList":
            return t("Recent Posts Pages");
        case "post":
            return t("Post Pages");
        case "knowledgeBase":
            return t("Knowledge Base Pages");
        case "createPost":
            return t("Create Post Pages");
        case "event":
            return t("Event Pages");
        default:
            return "";
    }
}
export function getLayoutTypeLabel(layoutViewType: LayoutViewType): string {
    const hasHomeSplit = hasMultipleSiteSections();
    switch (layoutViewType) {
        case "categoryList":
            return t("Category List Pages");
        case "discussionCategoryPage":
            return t("Posting Category Pages");
        case "nestedCategoryList":
            return t("Nested Category Pages");
        case "post":
            return t("Post Pages");
        case "discussion":
            return t("Discussion Pages");
        case "idea":
            return t("Idea Pages");
        case "question":
            return t("Question Pages");
        case "subcommunityHome":
            if (hasHomeSplit) {
                return t("Subcommunity Home Pages");
            } else {
                return t("Home Pages");
            }
        case "home":
            if (hasHomeSplit) {
                return t("Site Home Pages");
            } else {
                return t("Home Pages");
            }
        case "discussionList":
            return t("Post List Pages");
        case "guideArticle":
            return t("Guide Article Pages");
        case "helpCenterArticle":
            return t("Help Center Article Pages");
        case "helpCenterCategory":
            return t("Help Center Category Pages");
        case "knowledgeHome":
            return t("Knowledge Base Home Pages");
        case "helpCenterKnowledgeBase":
            return t("Help Center Home Pages");
        case "createPost":
            return t("Create Post Pages");
        case "event":
            return t("Event Pages");
        default:
            return "";
    }
}
