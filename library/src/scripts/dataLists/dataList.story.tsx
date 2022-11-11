/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { t } from "@vanilla/i18n/src";
import { FromToDateTime } from "@library/content/FromToDateTime";
import { DataList, IDataListNode } from "@library/dataLists/DataList";
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

export function MixedContentTypes(props: { data: [] }) {
    const dummyData: IDataListNode[] = [
        {
            key: "Text Content",
            value: "Deeds will not be less valiant because they are unpraised",
        },
        {
            key: "My 111th birthday",
            value: "Bilbo Baggins, a remarkably old and eccentric hobbit, throws a spectacular all-day party to celebrate his 111th birthday and his cousin Frodo's 33rd. Although old, Bilbo has the appearance and energy of someone half his age, while Frodo is now legally able to inherit Bilbo's estate. During his after-dinner speech, Bilbo announces that he is leaving, slips on his magic ring, and disappears in front of his guests' astonished eyes. Back in his hobbit hole, Bag End, he meets with his old friend the wizard Gandalf, and they discuss his plan to leave everything — including the ring — to Frodo. Bilbo becomes agitated and suspicious, and he nearly keeps the ring, but he finally leaves it behind. After he commits to the decision, he feels relieved, as though a heavy burden has been lifted.",
        },
        {
            key: "Number Content",
            value: 1954,
        },
        {
            key: "List Content",
            value: ["Aragorn", "Galadriel", "Elrond"],
        },
        {
            key: "Boolean Content True",
            value: true,
        },
        {
            key: "Boolean Content False",
            value: false,
        },
        {
            key: "Generic React Content",
            value: (
                <ol>
                    {[
                        "Dwalin",
                        "Balin",
                        "Kili",
                        "Fili",
                        "Dori",
                        "Nori",
                        "Ori",
                        "Oin",
                        "Gloin",
                        "Bifur",
                        "Bofur",
                        "Bombur",
                        "Thorin",
                    ].map((x) => (
                        <li key={x}>{x}</li>
                    ))}
                </ol>
            ),
        },
        {
            key: "Bilbo Baggins, a remarkably old and eccentric hobbit, throws a spectacular all-day party to celebrate his 111th birthday and his cousin Frodo's 33rd. Although old, Bilbo has the appearance and energy of someone half his age, while Frodo is now legally able to inherit Bilbo's estate. During his after-dinner speech, Bilbo announces that he is leaving, slips on his magic ring, and disappears in front of his guests' astonished eyes. Back in his hobbit hole, Bag End, he meets with his old friend the wizard Gandalf, and they discuss his plan to leave everything — including the ring — to Frodo. Bilbo becomes agitated and suspicious, and he nearly keeps the ring, but he finally leaves it behind. After he commits to the decision, he feels relieved, as though a heavy burden has been lifted.",
            value: "My 111th birthday",
        },
    ];

    return (
        <StoryContent>
            <StoryHeading depth={1}>Mixed content data list</StoryHeading>
            <DataList data={dummyData} title={"My cool Tolkien list"} />
        </StoryContent>
    );
}

export function LoadingData(props: { data: [] }) {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Loading content data list</StoryHeading>
            <DataList
                data={undefined as unknown as IDataListNode[]}
                isLoading
                loadingRows={12}
                caption={t("Event Details")}
            />
        </StoryContent>
    );
}

export function NoData(props: { data: [] }) {
    return (
        <StoryContent>
            <StoryHeading depth={1}>No content data list</StoryHeading>
            <DataList data={undefined as unknown as IDataListNode[]} caption={t("Event Details")} />
        </StoryContent>
    );
}
