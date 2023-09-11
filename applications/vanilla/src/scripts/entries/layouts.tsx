/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { registerLayoutPage } from "@library/features/Layout/LayoutPage";
import { getMeta, getSiteSection } from "@library/utility/appUtils";
import { registerDiscussionThreadPage } from "@vanilla/addon-vanilla/thread/registerDiscussionThreadPage";
import QueryStringParams from "qs";

const discussionThreadEnabled = getMeta("featureFlags.customLayout.discussionThread.Enabled", false);

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

discussionThreadEnabled && registerDiscussionThreadPage();
