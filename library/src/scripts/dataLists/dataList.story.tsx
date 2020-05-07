/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import DateTime, { DateFormats } from "@library/content/DateTime";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { DataList, IData } from "@library/dataLists/dataList";
import { t } from "@vanilla/i18n/src";
import { dummyEventDetailsData } from "./dummyEventData";
import { renderToString } from "react-dom/server";

export default {
    title: "Data List",
    parameters: {},
};

export function Standard(props: { data: [] }) {
    const startDate = <DateTime {...dummyEventDetailsData.dateStart} type={DateFormats.EXTENDED} />;
    const endDate = <DateTime {...dummyEventDetailsData.dateEnd} type={DateFormats.EXTENDED} />;

    const dummyData = [
        {
            key: t("When"),
            value: (
                <span
                    dangerouslySetInnerHTML={{
                        __html: `${renderToString(startDate)}${
                            dummyEventDetailsData.dateEnd ? ` - ${renderToString(endDate)}` : ""
                        }`,
                    }}
                />
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
    ] as IData[];

    return (
        <StoryContent>
            <StoryHeading depth={1}>Data List</StoryHeading>
            <DataList data={dummyData} />
        </StoryContent>
    );
}
