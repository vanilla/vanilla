/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RenderResult, fireEvent, render, screen } from "@testing-library/react";
import BlurContainer from "@dashboard/moderation/components/BlurContainerUserContent";
import UserContent from "@library/content/UserContent";
import { act } from "react-dom/test-utils";

const BUTTON_LABEL_SHOW = /show/i;
const BUTTON_LABEL_HIDE = /hide/i;

const MOCK_IMAGE = `<img class="embedImage-img" src="https://dev.vanilla.local/uploads/K5XXWLAZDS0X/vanilla-sundae.jpeg" alt="vanilla sundae.jpeg" height="1125" width="1500" loading="lazy" data-display-size="small" data-float="none" data-type="image/jpeg" data-embed-type="image">`;
const MOCK_PARAGRAPH = `<p>hello world</p>`;

const MOCK_CONTENT = `<div>${MOCK_PARAGRAPH}
<div class="moderationImageAndButtonContainer">
    <div class="moderationContainer blur">
       ${MOCK_IMAGE}
    </div></div></div>`;

describe("Blur container", () => {
    let renderResult: RenderResult;

    beforeEach(async () => {
        renderResult = render(
            <BlurContainer>
                <UserContent content={MOCK_CONTENT} moderateEmbeds={true} />
            </BlurContainer>,
        );

        await vi.dynamicImportSettled();
    });

    afterEach(() => {
        vitest.clearAllMocks();
    });

    it("should render the user content", () => {
        expect(renderResult.getByText("hello world")).toBeInTheDocument();
        expect(renderResult.getByRole("img")).toBeInTheDocument();
        const moderatedContainers = document.getElementsByClassName("moderationContainer");
        expect(moderatedContainers).toHaveLength(1);
    });

    it("should render a button to show/hide images", () => {
        const button = renderResult.getByRole("button");
        expect(button).toHaveClass("toggleButton");
        expect(button).toHaveTextContent(BUTTON_LABEL_SHOW);
        expect(button).toBeInTheDocument();
    });

    it("should blur images by default", () => {
        const moderatedContainers = document.getElementsByClassName("moderationContainer");
        expect(moderatedContainers[0]).toHaveClass("blur");
    });

    it("should unblur images when the button is clicked", async () => {
        const button = renderResult.getByRole("button");

        await act(async () => {
            fireEvent.click(button);
        });

        const moderatedContainers = document.getElementsByClassName("moderationContainer");
        expect(moderatedContainers[0]).not.toHaveClass("blur");
        expect(button).toHaveTextContent(BUTTON_LABEL_HIDE);
    });

    it("should blur images when the button is clicked again", async () => {
        const button = renderResult.getByRole("button");
        fireEvent.click(button);
        expect(button).toHaveTextContent(BUTTON_LABEL_HIDE);

        const moderatedContainers = document.getElementsByClassName("moderationContainer");
        expect(moderatedContainers[0]).not.toHaveClass("blur");

        await act(async () => {
            fireEvent.click(button);
        });

        expect(moderatedContainers[0]).toHaveClass("blur");
        expect(button).toHaveTextContent(BUTTON_LABEL_SHOW);
    });
});
