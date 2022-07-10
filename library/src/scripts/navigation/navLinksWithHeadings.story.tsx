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
import { storyWithConfig } from "@library/storybook/StoryContext";

export default {
    title: "Navigation/NavLinksWithHeadings",
    includeStories: ["StandardNavLinksStory", "ThreeColumnsNavLinksStory", "Placeholder"],
};

export function StoryNavLinks() {
    return (
        <NavLinksWithHeadingsComponent
            {...navLinksWithHeadingsData}
            depth={2}
            ungroupedTitle={t("Other Articles")}
            ungroupedViewAllUrl={navLinksWithHeadingsData.ungroupedViewAllUrl}
        />
    );
}

export const StandardNavLinksStory = storyWithConfig({}, StoryNavLinks);
StandardNavLinksStory.storyName = "Standard";

const global = {
    mainColors: {
        primary: "#038FF4",
        fg: "#6B829B",
    },
};

export const ThreeColumnsNavLinksStory = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            navLinks: {
                columns: {
                    desktop: 3,
                },
                separator: {
                    hidden: true,
                },
                link: {
                    fg: global.mainColors.primary,
                    fontWeight: 600,
                },
                title: {
                    font: {
                        color: global.mainColors.fg,
                    },
                },
                viewAll: {
                    color: global.mainColors.primary,
                    fontWeight: 600,
                    icon: true,
                },
            },
        },
    },
    StoryNavLinks,
);

ThreeColumnsNavLinksStory.storyName = "Three Columns Hidden Hr Tag";

export function Placeholder() {
    return <NavLinksPlaceholder title="Navigation Placeholder" showTitle />;
}
