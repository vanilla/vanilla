/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forum Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { NumberedPager } from "@library/features/numberedPager/NumberedPager";

const TOTAL_RESULTS = 12345;
const PAGE_LIMIT = 30;

const renderDefault = (props = {}) =>
    render(<NumberedPager totalResults={TOTAL_RESULTS} pageLimit={PAGE_LIMIT} currentPage={1} {...props} />);

describe("NumberedPager", () => {
    it("Default Properties", () => {
        render(<NumberedPager />);
        expect(screen.getByText("Next Page")).toBeInTheDocument();
        expect(screen.getByText("0 - 0 of 0")).toBeInTheDocument();
        expect(screen.getByLabelText("Jump to a specific page")).toBeInTheDocument();
    });

    it("Display range only", () => {
        renderDefault({ rangeOnly: true });

        waitFor(() => {
            expect(screen.getByText("1 - 30 of 3.0k+")).toBeInTheDocument();
            expect(screen.getByText("Next Page")).not.toBeInTheDocument();
            expect(screen.getByLabelText("Next Page")).not.toBeInTheDocument();
            expect(screen.getByLabelText("Previous Page")).not.toBeInTheDocument();
            expect(screen.getByLabelText("Jump to a specific page")).not.toBeInTheDocument();
        });
    });

    it("Render mobile view", () => {
        renderDefault({ isMobile: true });
        expect(screen.getByText("Next Page")).toBeInTheDocument();
        expect(screen.getByLabelText("Jump to a specific page")).toBeInTheDocument();

        waitFor(() => {
            expect(screen.getByText("1 - 30 of 3.0k+")).toBeInTheDocument();
            expect(screen.getByLabelText("Next Page")).not.toBeInTheDocument();
            expect(screen.getByLabelText("Previous Page")).not.toBeInTheDocument();
        });
    });

    it("Pass in total results count and page limit", () => {
        renderDefault();
        waitFor(() => {
            expect(screen.getByText("1 - 30 of 3.0k+")).toBeInTheDocument();
        });
    });

    it("Toggle the page jumper", () => {
        renderDefault();

        fireEvent.click(screen.getByLabelText("Jump to a specific page"));

        waitFor(() => {
            expect(screen.getByLabelText("Jump to a specific page")).not.toBeInTheDocument();
            expect(screen.getByText("1 - 30 of 3.0k+")).not.toBeInTheDocument();
            expect(screen.getByText("Jump to page")).toBeInTheDocument();
            expect(screen.getByLabelText("Jump to page")).toBeInTheDocument();
            expect(screen.getByText("Go")).toBeInTheDocument();
            expect(screen.getByLabelText("Back to post count")).toBeInTheDocument();
        });

        fireEvent.click(screen.getByLabelText("Back to post count"));

        waitFor(() => {
            expect(screen.getByLabelText("Jump to a specific page")).toBeInTheDocument();
            expect(screen.getByText("1 - 30 of 3.0k+")).toBeInTheDocument();
            expect(screen.getByText("Jump to page")).not.toBeInTheDocument();
            expect(screen.getByLabelText("Jump to page")).not.toBeInTheDocument();
            expect(screen.getByText("Go")).not.toBeInTheDocument();
            expect(screen.getByLabelText("Back to post count")).not.toBeInTheDocument();
        });
    });

    it("Navigate to next page using 'Next Page' button", () => {
        renderDefault();

        fireEvent.click(screen.getByText("Next Page"));

        waitFor(() => {
            expect(screen.getByText("31 - 60 of 3.0k+")).toBeInTheDocument();
            expect(screen.getByText("1 - 30 of 3.0k+")).not.toBeInTheDocument();
        });
    });

    it("Navigate to page using page jumper", () => {
        renderDefault();

        fireEvent.click(screen.getByLabelText("Jump to a specific page"));

        waitFor(() => {
            expect(screen.getByLabelText("Jump to a specific page")).not.toBeInTheDocument();
            expect(screen.getByText("1 - 30 of 3.0k+")).not.toBeInTheDocument();
            expect(screen.getByText("Jump to page")).toBeInTheDocument();
            expect(screen.getByLabelText("Jump to page")).toBeInTheDocument();
            expect(screen.getByText("Go")).toBeInTheDocument();
        });

        fireEvent.input(screen.getByLabelText("Jump to page"), { target: { value: "3" } });
        fireEvent.click(screen.getByText("Go"));

        waitFor(() => {
            expect(screen.getByLabelText("Jump to a specific page")).toBeInTheDocument();
            expect(screen.getByText("61 - 90 of 3.0k+")).toBeInTheDocument();
            expect(screen.getByText("Jump to page")).not.toBeInTheDocument();
            expect(screen.getByLabelText("Jump to page")).not.toBeInTheDocument();
            expect(screen.getByText("Go")).not.toBeInTheDocument();
        });
    });

    it("Navigate beyond page 100", () => {
        renderDefault({ currentPage: 100 });

        waitFor(() => {
            expect(screen.getByText("2,971 - 3,000 of 3.0k+")).toBeInTheDocument();
            expect(screen.getByText("100")).toBeInTheDocument();
        });

        fireEvent.click(screen.getByText("Next Page"));

        waitFor(() => {
            expect(screen.getByText("3,001 - 3,030 of 3.0k+")).toBeInTheDocument();
            expect(screen.getByText("101")).toBeInTheDocument();
        });
    });
});
