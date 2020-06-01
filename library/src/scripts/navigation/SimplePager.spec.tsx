/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import "@testing-library/jest-dom/extend-expect";
import { render, fireEvent, waitFor, screen } from "@testing-library/react";
import SimplePager from "@library/navigation/SimplePager";

describe("SimplePager", () => {
    test("it renders back and forward links", async () => {
        const expectedPrevious = "https://domain.com/resource/page/1";
        const expectedNext = "https://domain.com/resource/page/3";

        render(<SimplePager pages={{ next: 3, prev: 1 }} url={"https://domain.com/resource/page/:page:"} />);
        await waitFor(() => screen.getByText("Next"));
        expect(screen.getByText("Next")).toHaveAttribute("href", expectedNext);
        expect(screen.getByText("Previous")).toHaveAttribute("href", expectedPrevious);

        // Make sure we added the head elements.
        let relNext = document.head.querySelector(`[data-testid=link-rel-next]`);
        expect(relNext).toHaveAttribute("href", expectedNext);
        expect(relNext).toHaveAttribute("rel", "next");

        // Make sure we added the head elements.
        let relPrev = document.head.querySelector(`[data-testid=link-rel-prev]`);
        expect(relPrev).toHaveAttribute("href", expectedPrevious);
        expect(relPrev).toHaveAttribute("rel", "prev");

        render(<SimplePager pages={{ next: 4, prev: 2 }} url={"https://domain.com/resource/page/:page:"} />);

        // Make sure we added the head elements.
        relNext = document.head.querySelector(`[data-testid=link-rel-next]`);
        expect(relNext).toHaveAttribute("href", "https://domain.com/resource/page/4");
        expect(relNext).toHaveAttribute("rel", "next");

        // Make sure we added the head elements.
        relPrev = document.head.querySelector(`[data-testid=link-rel-prev]`);
        expect(relPrev).toHaveAttribute("href", "https://domain.com/resource/page/2");
        expect(relPrev).toHaveAttribute("rel", "prev");

        render(<SimplePager pages={{ next: 4, prev: 2 }} url={"https://domain.com/resource/page/:page:"} />);
    });
});
