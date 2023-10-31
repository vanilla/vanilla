/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { render, screen, fireEvent, within, waitFor } from "@testing-library/react";
import DiscussionListSort from "@library/features/discussions/DiscussionListSort";
import { DiscussionListSortOptions } from "@dashboard/@types/api/discussion";

const ListSortMock = ({ sort }: { sort?: DiscussionListSortOptions }) => {
    const [currentSort, setCurrentSort] = useState<DiscussionListSortOptions | undefined>(sort);

    return <DiscussionListSort currentSort={currentSort} selectSort={setCurrentSort} />;
};

describe("Discussion List Sort", () => {
    it("Defaults to 'Recently Commented' if current sort is not passed", () => {
        render(<ListSortMock />);
        const button = screen.getByRole("button");
        expect(button).toHaveTextContent(/Recently Commented/);
    });

    it("Defaults to passed sort value", () => {
        render(<ListSortMock sort={DiscussionListSortOptions.OLDEST} />);
        const button = screen.getByRole("button");
        expect(button).toHaveTextContent(/Oldest/);
    });

    it("Selects new sort by option", async () => {
        render(<ListSortMock />);
        const button = screen.getByRole("button");
        expect(button).toHaveTextContent(/Recently Commented/);

        fireEvent.click(button);
        const dropdown = await screen.findByRole("list");

        expect(dropdown).toBeInTheDocument();
        const trendingButton = within(dropdown).getByRole("button", { name: /Trending/ });

        fireEvent.click(trendingButton);

        waitFor(() => {
            expect(button).toHaveTextContent(/Trending/);
        });
    });
});
