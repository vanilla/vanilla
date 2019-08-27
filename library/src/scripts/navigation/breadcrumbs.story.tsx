/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storiesOf } from "@storybook/react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import LocationBreadcrumbs from "@knowledge/modules/locationPicker/components/LocationBreadcrumbs";
import { CategoryIcon } from "@library/icons/common";

const story = storiesOf("Components", module);

story.add("Breadcrumbs", () => {
    return (
        <>
            <StoryHeading depth={1}>Breadcrumbs</StoryHeading>
            <StoryHeading>Standard</StoryHeading>
            <Breadcrumbs forceDisplay={true}>
                {[
                    { name: "Success", url: "https://dev.vanilla.localhost/en-hutch/kb/success" },
                    {
                        name: "Appearance (Theming)",
                        url: "https://dev.vanilla.localhost/en-hutch/kb/categories/37-appearance-theming",
                    },
                ]}
            </Breadcrumbs>
            <StoryHeading>Location (Used in the location picker)</StoryHeading>
            <LocationBreadcrumbs
                locationData={[
                    { name: "Success", url: "https://dev.vanilla.localhost/en-hutch/kb/success" },
                    {
                        name: "Appearance (Theming)",
                        url: "https://dev.vanilla.localhost/en-hutch/kb/categories/37-appearance-theming",
                    },
                ]}
                icon={<CategoryIcon className={"pageLocation-icon"} />}
            />
        </>
    );
});
