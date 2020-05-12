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
import { dummyEventDetailsData } from "@groups/events/dummyEventData";

export default {
    title: "Data List",
    parameters: {},
};

export function Standard(props: { data: [] }) {
    const dummyData = [
        {
            key: t("When"),
            value: (
                <FromToDateTime dateStarts={dummyEventDetailsData.dateStart} dateEnds={dummyEventDetailsData.dateEnd} />
            ),
        },
        {
            key: t("Where"),
            value: dummyEventDetailsData.location,
        },
        {
            key: t("Organizer"),
            value: dummyEventDetailsData.organizer,
        },
    ];

    return (
        <StoryContent>
            <StoryHeading depth={1}>Data List</StoryHeading>
            <DataList data={dummyData} caption={t("Event Details")} />
        </StoryContent>
    );
}
