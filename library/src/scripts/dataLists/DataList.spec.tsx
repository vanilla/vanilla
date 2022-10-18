/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import { DataList, IDataListNode } from "@library/dataLists/DataList";

describe("DataList", () => {
    it("Renders a caption if only a title is provided", () => {
        const expected = "My test title";
        render(<DataList title={expected} data={undefined as unknown as IDataListNode[]} />);
        /**
         * We should be able to find by role here but there is bug in testing-library
         * which requires us to upgrade. Upgrading breaks a bunch of stuff so I have created
         * a TechDebt ticket to address it.
         * screen.findByRole("caption")
         *
         * BUG: [VNLA-2743] https://github.com/testing-library/dom-testing-library/issues/570
         */
        const caption = screen.getByText("My test title", { selector: "caption" });
        expect(caption).toHaveTextContent(expected);
    });
    it("Renders string content as string", () => {
        const stubData: IDataListNode[] = [
            {
                key: "test-key",
                value: "test-value",
            },
        ];
        render(<DataList title={"My test title"} data={stubData} />);
        expect(screen.getByText(/test-value/)).toBeInTheDocument();
    });
    it("Renders number content as string", () => {
        const stubData: IDataListNode[] = [
            {
                key: "test-key",
                value: 123456,
            },
        ];
        render(<DataList title={"My test title"} data={stubData} />);
        expect(screen.getByText(/123456/)).toBeInTheDocument();
    });
    it("Renders string array content as tokens", () => {
        const stubData: IDataListNode[] = [
            {
                key: "test-key",
                value: ["entry-one", "entry-two", "entry-three"],
            },
        ];
        const { container } = render(<DataList title={"My test title"} data={stubData} />);
        // Assuming tokens are spans here
        const spans = container.querySelectorAll("span");
        expect(spans.length).toBe(3);
        expect(screen.getByText(/entry-one/)).toBeInTheDocument();
        expect(screen.getByText(/entry-two/)).toBeInTheDocument();
        expect(screen.getByText(/entry-three/)).toBeInTheDocument();
    });
    it("Renders mixed array content as tokens", () => {
        const stubData: IDataListNode[] = [
            {
                key: "test-key",
                value: ["entry-one", 75, "entry-three"],
            },
        ];
        const { container } = render(<DataList title={"My test title"} data={stubData} />);
        // Assuming tokens are spans here
        const spans = container.querySelectorAll("span");
        expect(spans.length).toBe(3);
        expect(screen.getByText(/entry-one/)).toBeInTheDocument();
        expect(screen.getByText(/75/)).toBeInTheDocument();
        expect(screen.getByText(/entry-three/)).toBeInTheDocument();
    });
    it("Renders a disabled checked checkmark for instead of true", () => {
        const stubData: IDataListNode[] = [
            {
                key: "test-key",
                value: true,
            },
        ];
        render(<DataList title={"My test title"} data={stubData} />);
        const checkbox = screen.getByRole("checkbox");
        expect(checkbox).toHaveAttribute("checked");
        expect(checkbox).toHaveAttribute("disabled");
        expect(screen.getByText(/Yes/)).toBeInTheDocument();
    });
    it("Renders a disabled checked checkmark for instead of false", () => {
        const stubData: IDataListNode[] = [
            {
                key: "test-key",
                value: false,
            },
        ];
        render(<DataList title={"My test title"} data={stubData} />);
        const checkbox = screen.getByRole("checkbox");
        expect(checkbox).not.toHaveAttribute("checked");
        expect(checkbox).toHaveAttribute("disabled");
        expect(screen.getByText(/No/)).toBeInTheDocument();
    });
    it("Renders a loading state when the isLoading props is truthy", () => {
        const { container } = render(
            <DataList title={"Test"} data={undefined as unknown as IDataListNode[]} isLoading />,
        );
        // Some assumptions going on here
        const rows = container.querySelectorAll("tr");
        // Default isLoading will split out 5 rows
        expect(rows.length).toBe(5);

        rows.forEach((row) => {
            const loadingRects = row.querySelectorAll("div");
            // We expect 1 loading rect per cell, 2 cells thus 2 rects
            expect(loadingRects.length).toBe(2);
        });
    });
    it("Renders a defined amount of loading rows", () => {
        const { container } = render(
            <DataList title={"Test"} data={undefined as unknown as IDataListNode[]} isLoading loadingRows={10} />,
        );
        // Some assumptions going on here
        const rows = container.querySelectorAll("tr");
        // Default isLoading will split out 5 rows
        expect(rows.length).toBe(10);
    });
});
