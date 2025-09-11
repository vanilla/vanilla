/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import RoleSpotlightWidgetPreview from "@library/roleSpotlight/RoleSpotlightWidget.preview";
import { setMeta } from "@library/utility/appUtils";

export default {
    title: "Widgets/Role Spotlight",
};

setMeta("tagging.enabled", true);

export function Default() {
    return (
        <RoleSpotlightWidgetPreview
            titleType="static"
            title="Role Spotlight"
            descriptionType="static"
            description="Discover the latest discussions and comments from your role."
            apiParams={{
                limit: "10",
                roleID: "1",
                includeComments: true,
                showLoadMore: true,
                sortExcludingComments: "latest",
                sortIncludingComments: "latest",
            }}
            itemOptions={{
                author: true,
                category: true,
                dateUpdated: true,
                excerpt: true,
                userTags: true,
            }}
            displayOptions={{
                featuredImage: true,
            }}
        />
    );
}
