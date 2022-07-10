/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storiesOf } from "@storybook/react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import { CategoryIcon } from "@library/icons/common";
import LocationBreadcrumbs from "@library/navigation/LocationBreadcrumbs";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { STORY_CRUMBS } from "@library/storybook/storyData";

export default {
    title: "Navigation/Breadcrumbs",
};

export const Defaults = storyWithConfig(
    {
        themeVars: {
            breadcrumbs: {
                link: {
                    font: {
                        size: "14px",
                    },
                },
                separator: {
                    font: {
                        size: "14px",
                    },
                },
            },
        },
    },
    () => (
        <>
            <StoryHeading depth={1}>Breadcrumbs</StoryHeading>
            <StoryHeading>Standard</StoryHeading>
            <Breadcrumbs forceDisplay={true}>{STORY_CRUMBS}</Breadcrumbs>
            <StoryHeading>Location (Used in the location picker)</StoryHeading>
            <LocationBreadcrumbs locationData={STORY_CRUMBS} icon={<CategoryIcon className={"pageLocation-icon"} />} />
        </>
    ),
);

export const Theme = storyWithConfig(
    {
        themeVars: {
            breadcrumbs: {
                link: {
                    font: {
                        size: "16px",
                        lineHeight: "24px",
                        color: "#767676",
                        transform: "capitalize",
                    },
                },
                separator: {
                    spacing: "28px",
                    font: {
                        size: "28px",
                    },
                },
            },
        },
    },
    () => <Breadcrumbs forceDisplay={true}>{STORY_CRUMBS}</Breadcrumbs>,
);
