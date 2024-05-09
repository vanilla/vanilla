/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only */

import React from "react";
import { render, screen } from "@testing-library/react";
import CallToActionWidget from "@library/callToAction/CallToActionWidget";

describe("CallToAction", () => {
    it("Basic props to test texts", async () => {
        const componentProps = {
            title: "My CTA",
            description: "My CTA description",
        };
        render(<CallToActionWidget {...componentProps} />);

        //texts should match what we set
        const titleText = await screen.findByText(componentProps.title);
        const descriptionText = await screen.findByText(componentProps.description);
        expect(titleText).toBeInTheDocument();
        expect(descriptionText).toBeInTheDocument();
    });

    it("Test button props", async () => {
        const componentProps = {
            title: "My CTA",
            description: "My CTA description",
            button: {
                title: "CTA button",
                url: "https://somewhere",
            },
        };
        const { container } = render(<CallToActionWidget {...componentProps} />);

        //button should be there with the right url
        const buttonNodes = container.querySelectorAll("a");
        expect(buttonNodes.length).toBeGreaterThan(0);
        expect(buttonNodes[0].innerHTML).toBe(componentProps.button.title);
        expect(buttonNodes[0]).toHaveAttribute("href");
        expect(buttonNodes[0].getAttribute("href")).toContain(componentProps.button.url);
    });

    it("Test background image", async () => {
        const componentProps = {
            title: "My CTA",
            description: "My CTA description",
            background: {
                image: "https://image-url",
                imageUrlSrcSet: {
                    10: "https://image-url/10",
                    300: "https://image-url/300",
                    800: "https://image-url/800",
                    1200: "https://image-url/1200",
                    1600: "https://image-url/1600",
                },
            },
        };
        const { container } = render(<CallToActionWidget {...componentProps} />);

        //should have <img> with src and srcset attributes
        const imgNodes = container.querySelectorAll("img");
        expect(imgNodes.length).toBeGreaterThan(0);
        expect(imgNodes[0]).toHaveAttribute("src");
        expect(imgNodes[0]).toHaveAttribute("srcset");
    });
});
