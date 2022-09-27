/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums, Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { render, screen, within } from "@testing-library/react";
import SiteTotalsWidget from "@library/siteTotalsWidget/SiteTotalsWidget";
import {
    SiteTotalsAlignment,
    SiteTotalsLabelType,
    ISiteTotalCount,
} from "@library/siteTotalsWidget/SiteTotals.variables";
import { useValidCounts } from "@library/siteTotalsWidget/SiteTotals";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { formatNumberText } from "@library/content/NumberFormatted";

describe("SiteTotals", () => {
    const allCounts: ISiteTotalCount[] = LayoutEditorPreviewData.getSiteTotals([
        {
            recordType: "post",
            label: "Post",
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

    const generateLabel = (label: string, value: number, format: string, space?: boolean): string => {
        const numberString: string = formatNumberText({ value })[format];
        return [numberString, label].join(space ? " " : "");
    };

    it("Display member, posts, and online totals with icon and label in the center of the container", async () => {
        const componentProps = {
            containerOptions: {
                alignment: SiteTotalsAlignment.CENTER,
            },
            labelType: SiteTotalsLabelType.BOTH,
            totals: allCounts,
        };

        render(<SiteTotalsWidget {...componentProps} />);

        const root = await screen.getByTestId("site-totals-root");
        expect(root).toHaveStyle(`justify-content: ${SiteTotalsAlignment.CENTER}`);
        expect.objectContaining({
            labelType: SiteTotalsLabelType.BOTH,
        });

        allCounts.forEach(async (c) => {
            const expectedText = generateLabel(c.label, c.count, "fullValue");
            const countContainer = await screen.getByLabelText(generateLabel(c.label, c.count, "fullValue", true));
            expect(countContainer).toBeInTheDocument();
            expect(countContainer).toHaveTextContent(expectedText);
        });
    });

    it("Display only the member totals with just an icon at the beginning of the container", async () => {
        const componentProps = {
            containerOptions: {
                alignment: SiteTotalsAlignment.LEFT,
            },
            labelType: SiteTotalsLabelType.ICON,
            totals: LayoutEditorPreviewData.getSiteTotals([
                {
                    recordType: "user",
                    label: "Members",
                },
            ]),
        };

        render(<SiteTotalsWidget {...componentProps} />);

        const root = await screen.getByTestId("site-totals-root");
        expect(root).toHaveStyle(`justify-content: ${SiteTotalsAlignment.LEFT}`);
        expect.objectContaining({
            labelType: SiteTotalsLabelType.ICON,
        });

        allCounts.forEach(async (c) => {
            const countContainer = await screen.queryByLabelText(generateLabel(c.label, c.count, "fullValue", true));
            if (c.recordType === "user" && countContainer) {
                expect(countContainer).toBeInTheDocument();
                expect(countContainer).not.toHaveTextContent(generateLabel(c.label, c.count, "fullValue"));
            } else {
                expect(countContainer).not.toBeInTheDocument();
            }
        });
    });

    it("Display the posts and online users totals with just a text label at the end of the container", async () => {
        const componentProps = {
            containerOptions: {
                alignment: SiteTotalsAlignment.RIGHT,
            },
            labelType: SiteTotalsLabelType.TEXT,
            totals: LayoutEditorPreviewData.getSiteTotals([
                {
                    recordType: "post",
                    label: "Post",
                },
                {
                    recordType: "onlineUser",
                    label: "Online",
                },
            ]),
        };

        render(<SiteTotalsWidget {...componentProps} />);

        const root = await screen.getByTestId("site-totals-root");
        expect(root).toHaveStyle(`justify-content: ${SiteTotalsAlignment.RIGHT}`);

        allCounts.forEach(async (c) => {
            const countContainer = await screen.queryByLabelText(generateLabel(c.label, c.count, "fullValue", true));
            if ((c.recordType === "onlineUser" || c.recordType === "post") && countContainer) {
                expect(countContainer).toBeInTheDocument();
                expect(within(countContainer).queryByRole("icon")).not.toBeInTheDocument();
                expect(countContainer).toHaveTextContent(generateLabel(c.label, c.count, "fullValue"));
            } else {
                expect(countContainer).not.toBeInTheDocument();
            }
        });
    });

    it("Change the background and text/icon color and display with even spacing around each count", async () => {
        const componentProps = {
            containerOptions: {
                alignment: SiteTotalsAlignment.JUSTIFY,
                background: { color: "#BFCBD8" },
                textColor: "#555A62",
            },
            labelType: SiteTotalsLabelType.BOTH,
            totals: allCounts,
        };

        render(<SiteTotalsWidget {...componentProps} />);

        const root = await screen.getByTestId("site-totals-root");
        expect(root).toHaveStyle(`justify-content: ${SiteTotalsAlignment.JUSTIFY}`);
        expect(root).toHaveStyle(`background-color: ${componentProps.containerOptions.background.color}`);
        expect.objectContaining({
            labelType: SiteTotalsLabelType.BOTH,
        });

        allCounts.forEach(async (c) => {
            const countContainer = await within(root).getByLabelText(
                generateLabel(c.label, c.count, "fullValue", true),
            );
            const text = within(countContainer).getByText(c.label);
            expect(text).toHaveStyle(`color: ${componentProps.containerOptions.textColor}`);
        });
    });

    it("Arrange counts with even spacing around each item", async () => {
        const componentProps = {
            containerOptions: {
                alignment: SiteTotalsAlignment.JUSTIFY,
            },
            labelType: SiteTotalsLabelType.ICON,
            totals: allCounts,
        };

        render(<SiteTotalsWidget {...componentProps} />);

        const root = await screen.getByTestId("site-totals-root");
        expect(root).toHaveStyle(`justify-content: ${SiteTotalsAlignment.JUSTIFY}`);
        expect.objectContaining({
            labelType: SiteTotalsLabelType.ICON,
        });
    });

    it("Get valid count list based on enabled addons for the widget editor", () => {
        const mockInstance = [
            { recordType: "discussion", label: "Discussions", isHidden: false },
            { recordType: "onlineUser", label: "Online", isHidden: false },
            { recordType: "post", label: "Posts", isHidden: false },
            { recordType: "answered", label: "Answered", isHidden: false },
            { recordType: "comment", label: "Comments", isHidden: false },
            { recordType: "question", label: "Questions", isHidden: true },
        ];

        const mockSchema = {
            description: "Site Totals",
            type: "object",
            required: ["apiParams"],
            properties: {
                apiParams: {
                    type: "object",
                    required: [],
                    properties: {
                        options: {
                            type: "object",
                            default: {
                                group: "Groups",
                                event: "Events",
                                category: "Categories",
                                discussion: "Discussions",
                                comment: "Comments",
                                post: "Posts",
                                user: "Members",
                            },
                        },
                    },
                },
            },
        };

        const expectedInstance = [
            { recordType: "discussion", label: "Discussions", isHidden: false },
            { recordType: "post", label: "Posts", isHidden: false },
            { recordType: "comment", label: "Comments", isHidden: false },
            { recordType: "group", label: "Groups", isHidden: true },
            { recordType: "event", label: "Events", isHidden: true },
            { recordType: "category", label: "Categories", isHidden: true },
        ];

        expect(useValidCounts(mockInstance, mockSchema)).toStrictEqual(expectedInstance);
    });
});
