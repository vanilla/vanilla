/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useLayoutEffect, useMemo } from "react";
import Banner from "@library/banner/Banner";
import { MemoryRouter } from "react-router";
import { bannerVariables } from "@library/banner/Banner.variables";
import { css, CSSObject } from "@emotion/css";
import { contentBannerVariables } from "@library/banner/contentBannerStyles";
import { NoDescription } from "@library/banner/banner.story";

interface IProps {
    title?: string; // Often the message to display isn't the real H1
    description?: string;
    className?: string;
    backgroundImage?: string;
    contentImage?: string;
}

type BannerOptions = ReturnType<typeof bannerVariables>["options"];

const fontHiddenCss = css({
    // This works for now.
    // We don't display none, because there can be specific elements
    // Like the "Category Following" that is not duplicated.
    // It has it's own font size.
    fontSize: "0 !important",
});

const hiddenCss = css({
    display: "none !important",
});

/**
 * Hook to remove duplicate page titles and descriptions where possible.
 */
function useTitleDescriptionDeduplication(props: IProps, options: BannerOptions) {
    const hasTitle = options.deduplicateTitles && props.title && !options.hideTitle;
    const hasDescription = options.deduplicateTitles && props.description && !options.hideDescription;

    useLayoutEffect(() => {
        let closestBox: HTMLElement | null = null;
        let stillHasTitle = false;
        let stillHasDescription = false;

        //on some pages we don't want to hide the content title (e.g. discussion page with content banner)
        const omitDeduplication = [".Section-Discussion"].some((selector) => document.querySelector(selector));

        // The title does this font-size hack for handling things like category following actions.
        // If there isn't an indicator though, it's better to just remove the element.
        if (hasTitle) {
            const titleElement = document.querySelector(".Content h1");
            if (titleElement instanceof HTMLElement) {
                closestBox = titleElement.closest(".pageHeadingBox") ?? closestBox;
                if (titleElement.textContent?.trim() === props.title?.trim()) {
                    titleElement.classList.add(hiddenCss);
                } else if (!omitDeduplication) {
                    titleElement.classList.add(fontHiddenCss);
                    stillHasTitle = true;
                }
            }
        }

        if (hasDescription) {
            const descriptionElement = document.querySelector(".Content h1 + .PageDescription");
            if (descriptionElement instanceof HTMLElement) {
                closestBox = descriptionElement.closest(".pageHeadingBox") ?? closestBox;
                if (descriptionElement.textContent?.trim() === props.description?.trim()) {
                    descriptionElement.classList.add(hiddenCss);
                } else if (!omitDeduplication) {
                    descriptionElement.classList.add(fontHiddenCss);
                    stillHasDescription = true;
                }
            }
        }

        if (!stillHasTitle && !stillHasDescription && closestBox && !omitDeduplication) {
            // We can clear the whole box.
            closestBox.classList.add(hiddenCss);
        }
    }, []);
}

/**
 * Main banner for the community.
 */
export function CommunityBanner(props: IProps) {
    useTitleDescriptionDeduplication(props, bannerVariables().options);
    return (
        <MemoryRouter>
            <Banner {...props} />
        </MemoryRouter>
    );
}

/**
 * Content banner for the community.
 */
export function CommunityContentBanner(props: IProps) {
    useTitleDescriptionDeduplication(props, contentBannerVariables().options);
    return (
        <MemoryRouter>
            <Banner {...props} isContentBanner />
        </MemoryRouter>
    );
}
