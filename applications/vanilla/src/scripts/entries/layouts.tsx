/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { registerLayoutPage } from "@library/features/Layout/LayoutPage.registry";
import { getMeta, getSiteSection } from "@library/utility/appUtils";
import { registerDiscussionThreadPage } from "@vanilla/addon-vanilla/thread/registerDiscussionThreadPage";
import QueryStringParams from "qs";

const discussionThreadEnabled = getMeta("featureFlags.customLayout.discussionThread.Enabled", false);
const discussionListEnabled = getMeta("featureFlags.customLayout.discussionList.Enabled", false);
const categoryListEnabled = getMeta("featureFlags.customLayout.categoryList.Enabled", false);

discussionListEnabled &&
    registerLayoutPage("/discussions", (routeParams) => {
        const { location } = routeParams;
        const urlQuery = QueryStringParams.parse(location.search.substring(1));

        return {
            layoutViewType: "discussionList",
            recordType: "siteSection",
            recordID: getSiteSection().sectionID,
            params: {
                siteSectionID: getSiteSection().sectionID,
                locale: getSiteSection().contentLocale,
                ...urlQuery,
            },
        };
    });

/**
 * Register Categories Page
 */
categoryListEnabled &&
    registerLayoutPage("/categories", (routeParams) => {
        const { location } = routeParams;
        const urlQuery = QueryStringParams.parse(location.search.substring(1));
        return {
            layoutViewType: "categoryList",
            recordType: "siteSection",
            recordID: getSiteSection().sectionID,
            params: {
                siteSectionID: getSiteSection().sectionID,
                locale: getSiteSection().contentLocale,
                ...urlQuery,
            },
        };
    });

interface ICategoryPageParams {
    id: number | string;
    page: string;
}

/**
 * Register Category Pages
 */
categoryListEnabled &&
    registerLayoutPage<ICategoryPageParams>(
        ["/categories/:id([^/]+)?/p:page(\\d+)", "/categories/:id([^/]+)?"],
        (params) => {
            const { location } = params;
            const urlQuery = QueryStringParams.parse(location.search.substring(1));
            return {
                layoutViewType: "categoryList",
                recordType: "category",
                recordID: params.match.params.id,
                params: {
                    ...urlQuery,
                    siteSectionID: getSiteSection().sectionID,
                    locale: getSiteSection().contentLocale,
                    categoryID: params.match.params.id,
                    page: (params.match.params.page ?? urlQuery.page ?? 1).toString(),
                },
            };
        },
    );

discussionThreadEnabled && registerDiscussionThreadPage();
