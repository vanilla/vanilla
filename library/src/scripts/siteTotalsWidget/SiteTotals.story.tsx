/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { BorderType } from "@library/styles/styleHelpers";
import { SiteTotalsWidget } from "@library/siteTotalsWidget/SiteTotalsWidget";
import { DeepPartial } from "redux";
import {
    ISiteTotalsOptions,
    ISiteTotalCount,
    ISiteTotalsContainer,
    SiteTotalsAlignment,
    SiteTotalsLabelType,
} from "@library/siteTotalsWidget/SiteTotals.variables";
import { SiteTotalsWidgetPreview } from "@library/siteTotalsWidget/SiteTotalsWidget.preview";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { formatNumberText } from "@library/content/NumberFormatted";

export default {
    title: "Widgets/SiteTotals",
    parameters: {},
};

function SiteTotalsInit(props: Omit<React.ComponentProps<typeof SiteTotalsWidget>, "totals">) {
    const totals: ISiteTotalCount[] = LayoutEditorPreviewData.getSiteTotals([
        {
            recordType: "post",
            label: "Post",
        },
        {
            recordType: "discussion",
            label: "Discussions",
        },
        {
            recordType: "user",
            label: "Members",
        },
        {
            recordType: "onlineUser",
            label: "Online",
        },
    ]);

    return (
        <SiteTotalsWidget
            totals={totals}
            labelType={props.labelType}
            containerOptions={props.containerOptions}
            formatNumbers={props.formatNumbers}
        />
    );
}

export const LabelTypes = storyWithConfig({}, () => {
    const LabelTypeStory = (props: { labelType: SiteTotalsLabelType }) => (
        <SiteTotalsInit
            containerOptions={{
                alignment: SiteTotalsAlignment.CENTER,
            }}
            labelType={props.labelType}
        />
    );

    return (
        <>
            <StoryHeading>Icon and Text Label</StoryHeading>
            <LabelTypeStory labelType={SiteTotalsLabelType.BOTH} />
            <StoryHeading>Icon Only</StoryHeading>
            <LabelTypeStory labelType={SiteTotalsLabelType.ICON} />
            <StoryHeading>Text Only</StoryHeading>
            <LabelTypeStory labelType={SiteTotalsLabelType.TEXT} />
        </>
    );
});

export const FormatNumbers = storyWithConfig({}, () => {
    const FormatNumberStory = (props: { formatNumbers: boolean }) => (
        <SiteTotalsInit
            containerOptions={{
                alignment: SiteTotalsAlignment.CENTER,
            }}
            labelType={SiteTotalsLabelType.BOTH}
            formatNumbers={props.formatNumbers}
        />
    );

    return (
        <>
            <StoryHeading>Default formatting with commas</StoryHeading>
            <FormatNumberStory formatNumbers={false} />
            <StoryHeading>Format numbers compacted</StoryHeading>
            <FormatNumberStory formatNumbers={true} />
        </>
    );
});

export const Alignment = storyWithConfig({}, () => {
    const AlignmentStory = (props: { alignment: SiteTotalsAlignment }) => (
        <SiteTotalsInit
            containerOptions={{
                alignment: props.alignment,
            }}
            labelType={SiteTotalsLabelType.BOTH}
        />
    );

    return (
        <>
            <StoryHeading>Left Aligned</StoryHeading>
            <AlignmentStory alignment={SiteTotalsAlignment.LEFT} />
            <StoryHeading>Center Aligned</StoryHeading>
            <AlignmentStory alignment={SiteTotalsAlignment.CENTER} />
            <StoryHeading>Right Aligned</StoryHeading>
            <AlignmentStory alignment={SiteTotalsAlignment.RIGHT} />
            <StoryHeading>Justified</StoryHeading>
            <AlignmentStory alignment={SiteTotalsAlignment.JUSTIFY} />
        </>
    );
});

export const SiteTotalsPreview = storyWithConfig({}, () => (
    <>
        <StoryHeading>Site Totals Widget Preview (e.g. in Layout editor/overview pages)</StoryHeading>
        <SiteTotalsWidgetPreview
            labelType={SiteTotalsLabelType.BOTH}
            apiParams={{
                counts: [
                    {
                        recordType: "user",
                        label: "Members",
                    },
                    {
                        recordType: "post",
                        label: "Posts",
                    },
                    {
                        recordType: "onlineUser",
                        label: "Online Users",
                    },
                    {
                        recordType: "discussion",
                        label: "Discussions",
                        isHidden: true,
                    },
                    {
                        recordType: "comment",
                        label: "Comments",
                        isHidden: true,
                    },
                    {
                        recordType: "question",
                        label: "Questions",
                        isHidden: true,
                    },
                ],
                options: {
                    question: "Questions",
                    accepted: "Questions Answered",
                    onlineUser: "Online Users",
                    onlineMember: "Online Members",
                    group: "Groups",
                    event: "Events",
                    category: "Categories",
                    discussion: "Discussions",
                    comment: "Comments",
                    post: "Posts",
                    user: "Members",
                },
            }}
            containerOptions={{
                alignment: SiteTotalsAlignment.CENTER,
            }}
        />
    </>
));
