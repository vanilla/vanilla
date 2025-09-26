/**
 * @author Unit Tests
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent } from "@testing-library/react";
import { blessStringAsSanitizedHtml } from "@vanilla/dom-utils";
import { TrollComment } from "./LegacyTrollComment";
import { expect, describe, it } from "vitest";

describe("TrollComment", () => {
    const hideText = blessStringAsSanitizedHtml("<p>Warning: This is a troll comment</p>");
    const commentBody = blessStringAsSanitizedHtml("<p>This is the comment body</p>");
    const comment = blessStringAsSanitizedHtml("<p>This is the legacy comment property</p>");
    // Use empty string as fallback for test
    const emptyContent = blessStringAsSanitizedHtml("");

    it("renders the hideText warning", () => {
        render(<TrollComment hideText={hideText} commentBody={commentBody} comment={comment} />);
        expect(screen.getByText("Warning: This is a troll comment")).toBeInTheDocument();
    });

    // Test for line 56: Fallback mechanism
    it("uses commentBody when available", () => {
        render(<TrollComment hideText={hideText} commentBody={commentBody} comment={comment} />);

        // Click to show the content
        fireEvent.click(screen.getByRole("button"));

        // The commentBody should be used and visible
        expect(screen.getByText("This is the comment body")).toBeVisible();
    });

    // Test for line 56: Fallback mechanism when commentBody is not provided
    it("falls back to comment when commentBody is not provided", () => {
        // Use empty content instead of undefined
        render(<TrollComment hideText={hideText} commentBody={emptyContent} comment={comment} />);

        // Click to show the content
        fireEvent.click(screen.getByRole("button"));

        // The comment property should be used as fallback
        expect(screen.getByText("This is the legacy comment property")).toBeVisible();
    });

    // Test for line 70: Content visibility is controlled by the button
    it("toggles content visibility when clicking the button", () => {
        const { container } = render(<TrollComment hideText={hideText} commentBody={commentBody} comment={comment} />);

        // Look for the div with data-visible attribute directly
        const blurContainer = container.querySelector("[data-visible]");
        expect(blurContainer).not.toBeNull();
        expect(blurContainer).toHaveAttribute("data-visible", "false");

        // Click to show the content
        fireEvent.click(screen.getByRole("button"));

        // The content should be visible
        expect(blurContainer).toHaveAttribute("data-visible", "true");

        // Click again to hide the content
        fireEvent.click(screen.getByRole("button"));

        // The content should be blurred again
        expect(blurContainer).toHaveAttribute("data-visible", "false");
    });
});
