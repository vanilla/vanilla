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

const renderDefault = (props: React.ComponentProps<typeof NumberedPager> = {}) => {
    render(<NumberedPager totalResults={TOTAL_RESULTS} pageLimit={PAGE_LIMIT} currentPage={1} {...props} />);
};

describe("NumberedPager", () => {
    it("Default Properties", () => {
        render(<NumberedPager />);
        expect(screen.getByText("Next Page")).toBeInTheDocument();
        expect(screen.getByText("0 - 0 of 0")).toBeInTheDocument();
        expect(screen.getByLabelText("Jump to a specific page")).toBeInTheDocument();
    });

    it("Display range only", () => {
        renderDefault({ rangeOnly: true });

        expect(screen.getByText("1 - 30 of 12.3k")).toBeInTheDocument();
        expect(screen.queryByText("Next Page")).not.toBeInTheDocument();
        expect(screen.queryByText("Next Page")).not.toBeInTheDocument();
        expect(screen.queryByText("Previous Page")).not.toBeInTheDocument();
        expect(screen.queryByText("Jump to a specific page")).not.toBeInTheDocument();
    });

    it("Pass in total results count and page limit", () => {
        renderDefault();

        expect(screen.getByText("1 - 30 of 12.3k")).toBeInTheDocument();
    });

    it("Toggle the page jumper", () => {
        renderDefault();

        act(() => {
            fireEvent.click(screen.getByLabelText("Jump to a specific page"));
        });

        expect(screen.queryByLabelText("Jump to a specific page")).not.toBeInTheDocument();
        expect(screen.queryByText("1 - 30 of 12.3k")).not.toBeInTheDocument();
        expect(screen.getByText(/Jump to page/)).toBeInTheDocument();

        expect(screen.getByLabelText("Jump to page")).toBeInTheDocument();
        expect(screen.getByText("Go")).toBeInTheDocument();
        expect(screen.getByLabelText("Back to post count")).toBeInTheDocument();

        act(() => {
            fireEvent.click(screen.getByLabelText("Back to post count"));
        });

        expect(screen.getByLabelText("Jump to a specific page")).toBeInTheDocument();
        expect(screen.getByText("1 - 30 of 12.3k")).toBeInTheDocument();
        expect(screen.queryByText("Jump to page")).not.toBeInTheDocument();
        expect(screen.queryByLabelText("Jump to page")).not.toBeInTheDocument();
        expect(screen.queryByText("Go")).not.toBeInTheDocument();
        expect(screen.queryByLabelText("Back to post count")).not.toBeInTheDocument();
    });

    it("Navigate to next page using 'Next Page' button", () => {
        renderDefault();

        act(() => {
            fireEvent.click(screen.getByText("Next Page"));
        });

        expect(screen.getByText("31 - 60 of 12.3k")).toBeInTheDocument();
        expect(screen.queryByText("1 - 30 of 12.3k")).not.toBeInTheDocument();
    });

    it("Navigate to page using page jumper", () => {
        renderDefault();

        act(() => {
            fireEvent.click(screen.getByLabelText("Jump to a specific page"));
        });

        expect(screen.queryByLabelText("Jump to a specific page")).not.toBeInTheDocument();
        expect(screen.queryByText("1 - 30 of 12.3k")).not.toBeInTheDocument();

        expect(screen.getByText(/Jump to page/)).toBeInTheDocument();
        expect(screen.getByLabelText("Jump to page")).toBeInTheDocument();
        expect(screen.getByText("Go")).toBeInTheDocument();

        act(() => {
            fireEvent.input(screen.getByLabelText("Jump to page"), { target: { value: "3" } });
        });

        act(() => {
            fireEvent.click(screen.getByText("Go"));
        });

        const jumper = screen.getByLabelText("Jump to a specific page");

        expect(jumper).toBeInTheDocument();
        expect(screen.getByText("61 - 90 of 12.3k")).toBeInTheDocument();
        expect(screen.queryByText("Jump to page")).not.toBeInTheDocument();
        expect(screen.queryByLabelText("Jump to page")).not.toBeInTheDocument();
        expect(screen.queryByText("Go")).not.toBeInTheDocument();
    });

    it("Navigate beyond page 100", () => {
        renderDefault({ currentPage: 100 });

        expect(screen.getByText("2,971 - 3,000 of 12.3k")).toBeInTheDocument();
        expect(screen.getByText("100")).toBeInTheDocument();

        act(() => {
            fireEvent.click(screen.getByText("Next Page"));
        });

        expect(screen.getByText("3,001 - 3,030 of 12.3k")).toBeInTheDocument();
        expect(screen.getByText("101")).toBeInTheDocument();
    });

    it("Actual pages are more than total results, APIs normally have limit for total", () => {
        renderDefault({ hasMorePages: true });

        expect(screen.getByText("1 - 30 of 12.3k+")).toBeInTheDocument();
    });
});
