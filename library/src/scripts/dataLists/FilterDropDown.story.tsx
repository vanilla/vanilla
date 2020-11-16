/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import FilterDropDown from "@library/dataLists/FilterDropDown";
import { StoryParagraph } from "@library/storybook/StoryParagraph";

export default {
    title: "Components",
    parameters: {},
};

export function FilterDropdowns(props) {
    const dummyData = [
        {
            value: "1",
            name: "One",
        },
        {
            value: "2",
            name: "Two",
        },
    ];

    return (
        <StoryContent>
            <StoryHeading depth={1}>Filter Dropdown</StoryHeading>
            <StoryParagraph>
                Filter dropdowns are placed above data lists. They are meant to provide sorting and filtering options.
            </StoryParagraph>
            <FilterDropDown {...props} id="filterDropdown" label="Filter" options={dummyData} />
        </StoryContent>
    );
}
