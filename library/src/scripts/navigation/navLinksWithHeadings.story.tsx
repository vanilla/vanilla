/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import NavLinksWithHeadingsComponent from "@library/navigation/NavLinksWithHeadings";
import { navLinksWithHeadingsData } from "@library/navigation/navLinksWithHeadings.storyData";
import { t } from "@library/utility/appUtils";
import React from "react";
import { NavLinksPlaceholder } from "@library/navigation/NavLinksPlaceholder";

export default {
    title: "Navigation/NavLinksWithHeadings",
};

export function StandardNavLinksStory() {
    return (
        <NavLinksWithHeadingsComponent
            {...navLinksWithHeadingsData}
            depth={2}
            ungroupedTitle={t("Other Articles")}
            ungroupedViewAllUrl={navLinksWithHeadingsData.ungroupedViewAllUrl}
        />
    );
}
StandardNavLinksStory.storyName = "Standard";

export function Placeholder() {
    return <NavLinksPlaceholder title="Navigation Placeholder" showTitle />;
}
