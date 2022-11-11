/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forum Inc.
 * @license Proprietary
 */

import React from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { NumberedPager } from "@library/features/numberedPager/NumberedPager";

export default {
    title: "Components/Numbered Pager",
};

const TOTAL_RESULTS = 12345;
const PAGE_LIMIT = 30;
const CURRENT_PAGE = 10;

export const NonFormattedNumbers = storyWithConfig(
    {
        themeVars: {},
    },
    () => (
        <>
            <StoryHeading depth={1}>Non-Formatted Numbers</StoryHeading>
            <StoryParagraph>
                Theme variables are set to not format any of the numbers into a compacted format.
            </StoryParagraph>
            <StoryHeading depth={2}>Default properties</StoryHeading>
            <NumberedPager />
            <StoryHeading depth={2}>More than 100 pages</StoryHeading>
            <StoryParagraph>
                totalResults = {TOTAL_RESULTS}
                <br />
                pageLimit = {PAGE_LIMIT}
                <br />
                A plus should be displayed next to the total results count.
                <br />
                Jumping to pages beyond page 100 is not allowed.
                <br />
                After page 100, only the next and previous buttons can be used.
            </StoryParagraph>
            <NumberedPager totalResults={TOTAL_RESULTS} pageLimit={PAGE_LIMIT} />
            <StoryHeading depth={2}>Less than 100 pages</StoryHeading>
            <StoryParagraph>
                totalResults = 500
                <br />
                pageLimit = 10
                <br />A plus should not be displayed next to the total results count.
            </StoryParagraph>
            <NumberedPager totalResults={500} pageLimit={10} />
            <StoryHeading depth={2}>Range only on page {CURRENT_PAGE}</StoryHeading>
            <NumberedPager totalResults={TOTAL_RESULTS} currentPage={CURRENT_PAGE} rangeOnly />
            <div style={{ maxWidth: 400, margin: "0px auto" }}>
                <StoryHeading depth={2}>Mobile View</StoryHeading>
                <NumberedPager totalResults={TOTAL_RESULTS} pageLimit={PAGE_LIMIT} />
            </div>
        </>
    ),
);

export const FormattedTotalCount = storyWithConfig(
    {
        themeVars: {
            numberedPager: {
                formatNumber: {
                    totalResults: true,
                },
            },
        },
    },
    () => (
        <>
            <StoryHeading depth={1}>Formatted Total Count</StoryHeading>
            <StoryParagraph>
                Theme variables are set to format the total results count into a compacted format.
            </StoryParagraph>
            <StoryHeading depth={2}>Default properties</StoryHeading>
            <NumberedPager />
            <StoryHeading depth={2}>More than 100 pages</StoryHeading>
            <StoryParagraph>
                totalResults = {TOTAL_RESULTS}
                <br />
                pageLimit = {PAGE_LIMIT}
                <br />
                A plus should be displayed next to the total results count.
                <br />
                Jumping to pages beyond page 100 is not allowed.
                <br />
                After page 100, only the next and previous buttons can be used.
            </StoryParagraph>
            <NumberedPager totalResults={TOTAL_RESULTS} pageLimit={PAGE_LIMIT} />
            <StoryHeading depth={2}>Less than 100 pages</StoryHeading>
            <StoryParagraph>
                totalResults = 500
                <br />
                pageLimit = 10
                <br />A plus should not be displayed next to the total results count.
            </StoryParagraph>
            <NumberedPager totalResults={500} pageLimit={10} />
            <StoryHeading depth={2}>Range only on page {CURRENT_PAGE}</StoryHeading>
            <NumberedPager totalResults={TOTAL_RESULTS} currentPage={CURRENT_PAGE} rangeOnly />
            <div style={{ maxWidth: 400, margin: "0px auto" }}>
                <StoryHeading depth={2}>Mobile View</StoryHeading>
                <NumberedPager totalResults={TOTAL_RESULTS} pageLimit={PAGE_LIMIT} isMobile />
            </div>
        </>
    ),
);
