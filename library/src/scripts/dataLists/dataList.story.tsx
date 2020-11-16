/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { t } from "@vanilla/i18n/src";
import { FromToDateTime } from "@library/content/FromToDateTime";
import { DataList } from "@library/dataLists/DataList";
import { STORY_DATE } from "@library/storybook/storyData";

export default {
    title: "Components/Data List",
    parameters: {},
};

export function Standard(props: { data: [] }) {
    const dummyData = [
        {
            key: t("When"),
            value: <FromToDateTime dateStarts={STORY_DATE} dateEnds={STORY_DATE} />,
        },
        {
            key: t("Where"),
            value: "A beautiful sunny beach",
        },
        {
            key: t("Organizer"),
            value: "Adam Charron",
        },
    ];

    return (
        <StoryContent>
            <StoryHeading depth={1}>Data List</StoryHeading>
            <DataList data={dummyData} caption={t("Event Details")} />
        </StoryContent>
    );
}
