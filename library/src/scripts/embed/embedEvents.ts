/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export type EmbedRemotePathEvent = {
    type: "embeddedHrefUpdate";
    href: string;
    force?: boolean;
};

export type EmbedScrollEvent = {
    type: "embeddedScrollUpdate";
    isScrolled: boolean;
    isScrollEnd: boolean;
};

export type EmbedTitleUpdate = {
    type: "embeddedTitleUpdate";
    title: string;
};

export type EmbedNavigateExternalDomain = {
    type: "navigateExternalDomain";
    href: string;
};

export type EmbedEvent = EmbedRemotePathEvent | EmbedScrollEvent | EmbedTitleUpdate | EmbedNavigateExternalDomain;
