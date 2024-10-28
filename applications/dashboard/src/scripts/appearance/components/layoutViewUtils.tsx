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
        case "discussionThread":
            return "customLayout.discussionThread";
        case "subcommunityHome":
        case "home":
            return "customLayout.home";
        case "discussionList":
            return "customLayout.discussionList";
        case "knowledgeBase":
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
        case "discussionThread":
            return `/appearance/layouts/discussionThread/legacy`;
        case "subcommunityHome":
        case "home":
            return `/appearance/layouts/home/legacy`;
        case "discussionList":
            return `/appearance/layouts/discussionList/legacy`;
        case "knowledgeBase":
            return `/appearance/layouts/knowledgeBase/legacy`;
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
        case "discussionThread":
            return t("Discussion Thread Layout Settings");
        case "subcommunityHome":
        case "home":
            return t("Home Layout Settings");
        case "discussionList":
            return t("Discussions Layout Settings");
        case "knowledgeBase":
            return t("Knowledge Base Layout Settings");
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
            return t("Discussions Pages");
        case "discussionThread":
            return t("Discussion Thread Pages");
        case "knowledgeBase":
            return t("Knowledge Base Pages");
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
            return t("Discussion Category Pages");
        case "nestedCategoryList":
            return t("Nested Category Pages");
        case "discussionThread":
            return t("Discussion Thread Pages");
        case "discussionPage":
            return t("Discussion Thread Page");
        case "ideaThread":
            return t("Idea Thread Page");
        case "questionThread":
            return t("Question Thread Page");
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
            return t("Discussion List Pages");
        case "guideArticle":
            return t("Guide Article Pages");
        case "helpCenterArticle":
            return t("Help Center Article Pages");
        default:
            return "";
    }
}
