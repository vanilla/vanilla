/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import DateTime, { DateFormats } from "@library/content/DateTime";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { DataList } from "@library/dataLists/dataList";
import { t } from "@vanilla/i18n/src";
import { dummyEventDetailsData } from "./dummyEventData";
import { FromToDateTime } from "@library/content/FromToDateTime";

export default {
    title: "Data List",
    parameters: {},
};

export function Standard(props: { data: [] }) {
    const dummyData = [
        {
            key: t("When"),
            value: (
                <FromToDateTime dateStart={dummyEventDetailsData.dateStart!} dateEnd={dummyEventDetailsData.dateEnd} />
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
