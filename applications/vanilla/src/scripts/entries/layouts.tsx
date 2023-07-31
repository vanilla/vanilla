/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { registerLayoutPage } from "@library/features/Layout/LayoutPage";
import { getSiteSection } from "@library/utility/appUtils";
import QueryStringParams from "qs";

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
