/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forum Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";
import { NumberedPager } from "@library/features/numberedPager/NumberedPager";

const TOTAL_RESULTS = 12345;
const PAGE_LIMIT = 30;

const renderDefault = async (props: React.ComponentProps<typeof NumberedPager> = {}) => {
    await act(async () => {
        render(
            <NumberedPager
                totalResults={TOTAL_RESULTS}
                pageLimit={PAGE_LIMIT}
                currentPage={1}
                isMobile={false}
                {...props}
            />,
        );
    });
};

describe("NumberedPager", () => {
    it("Default Properties", async () => {
        await act(async () => {
            render(<NumberedPager />);
        });
        expect(screen.getByText("Next Page")).toBeInTheDocument();
        expect(screen.getByText("0 - 0 of 0")).toBeInTheDocument();
        expect(screen.getByLabelText("Jump to a specific page")).toBeInTheDocument();
    });

    it("Display range only", async () => {
        await renderDefault({ rangeOnly: true });

        expect(screen.getByText("1 - 30 of 3.0k+")).toBeInTheDocument();
        expect(screen.queryByText("Next Page")).not.toBeInTheDocument();
        expect(screen.queryByText("Next Page")).not.toBeInTheDocument();
        expect(screen.queryByText("Previous Page")).not.toBeInTheDocument();
        expect(screen.queryByText("Jump to a specific page")).not.toBeInTheDocument();
    });

    it("Render mobile view", async () => {
        await renderDefault({ isMobile: true });

        expect(screen.getByText("Next Page")).toBeInTheDocument();
        expect(screen.getByLabelText("Jump to a specific page")).toBeInTheDocument();

        expect(screen.getByText("1 - 30 of 3.0k+")).toBeInTheDocument();
        expect(screen.queryByLabelText("Next Page")).not.toBeInTheDocument();
        expect(screen.queryByLabelText("Previous Page")).not.toBeInTheDocument();
    });

    it("Pass in total results count and page limit", async () => {
        await renderDefault();

        expect(screen.getByText("1 - 30 of 3.0k+")).toBeInTheDocument();
    });

    it("Toggle the page jumper", async () => {
        await renderDefault();

        await act(async () => {
            fireEvent.click(screen.getByLabelText("Jump to a specific page"));
        });

        expect(screen.queryByLabelText("Jump to a specific page")).not.toBeInTheDocument();
        expect(screen.queryByText("1 - 30 of 3.0k+")).not.toBeInTheDocument();
        expect(screen.getByText(/Jump to page/)).toBeInTheDocument();

        expect(screen.getByLabelText("Jump to page")).toBeInTheDocument();
        expect(screen.getByText("Go")).toBeInTheDocument();
        expect(screen.getByLabelText("Back to post count")).toBeInTheDocument();

        await act(async () => {
            fireEvent.click(screen.getByLabelText("Back to post count"));
        });

        expect(screen.getByLabelText("Jump to a specific page")).toBeInTheDocument();
        expect(screen.getByText("1 - 30 of 3.0k+")).toBeInTheDocument();
        expect(screen.queryByText("Jump to page")).not.toBeInTheDocument();
        expect(screen.queryByLabelText("Jump to page")).not.toBeInTheDocument();
        expect(screen.queryByText("Go")).not.toBeInTheDocument();
        expect(screen.queryByLabelText("Back to post count")).not.toBeInTheDocument();
    });

    it("Navigate to next page using 'Next Page' button", async () => {
        await renderDefault();

        await act(async () => {
            fireEvent.click(screen.getByText("Next Page"));
        });

        expect(screen.getByText("31 - 60 of 3.0k+")).toBeInTheDocument();
        expect(screen.queryByText("1 - 30 of 3.0k+")).not.toBeInTheDocument();
    });

    it("Navigate to page using page jumper", async () => {
        await renderDefault();

        await act(async () => {
            fireEvent.click(screen.getByLabelText("Jump to a specific page"));
        });

        expect(screen.queryByLabelText("Jump to a specific page")).not.toBeInTheDocument();
        expect(screen.queryByText("1 - 30 of 3.0k+")).not.toBeInTheDocument();

        expect(screen.getByText(/Jump to page/)).toBeInTheDocument();
        expect(screen.getByLabelText("Jump to page")).toBeInTheDocument();
        expect(screen.getByText("Go")).toBeInTheDocument();

        await act(async () => {
            fireEvent.input(screen.getByLabelText("Jump to page"), { target: { value: "3" } });
        });

        await act(async () => {
            fireEvent.click(screen.getByText("Go"));
        });

        expect(screen.getByLabelText("Jump to a specific page")).toBeInTheDocument();
        expect(screen.getByText("61 - 90 of 3.0k+")).toBeInTheDocument();
        expect(screen.queryByText("Jump to page")).not.toBeInTheDocument();
        expect(screen.queryByLabelText("Jump to page")).not.toBeInTheDocument();
        expect(screen.queryByText("Go")).not.toBeInTheDocument();
    });

    it("Navigate beyond page 100", async () => {
        await renderDefault({ currentPage: 100 });

        expect(screen.getByText("2,971 - 3,000 of 3.0k+")).toBeInTheDocument();
        expect(screen.getByText("100")).toBeInTheDocument();

        await act(async () => {
            fireEvent.click(screen.getByText("Next Page"));
        });

        expect(screen.getByText("3,001 - 3,030 of 3.0k+")).toBeInTheDocument();
        expect(screen.getByText("101")).toBeInTheDocument();
    });
});
