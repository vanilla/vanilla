/**
 * This file contains code for the our new embed system that run inside of the iframe.
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { EmbedEvent } from "@library/embed/embedEvents";
import { getMeta, siteUrl } from "@library/utility/appUtils";
import { delegateEvent, removeDelegatedEvent } from "@vanilla/dom-utils";
import debounce from "lodash/debounce";

/**
 * Initalization of our modern embed.
 *
 * @return A teardown function if applicable.
 */
export function initModernEmbed() {
    const remoteUrl = getMeta("embed.remoteUrl");
    const isEnabled = getMeta("embed.enabled") && getMeta("embed.isModernEmbed");
    if (!isEnabled || !remoteUrl) {
        // Modern embed is not configured.
        return null;
    }

    const forceEmbed = getMeta("embed.forceModernEmbed");
    const inIframe = window.top !== window.self;
    if (!inIframe && forceEmbed) {
        // Force embed is on and someone is visiting directly through the embed.
        const bypassEmbed = getBypassEmbed();

        const fullRemoteUrl = new URL(remoteUrl);
        if (!bypassEmbed && fullRemoteUrl.host !== window.location.host) {
            // We don't have an embed bypass and the host is different.
            // Redirect the user to the embed site.
            fullRemoteUrl.hash = `${escape(window.location.href)}`;
            window.location.href = fullRemoteUrl.toString();
            return null;
        }
    }

    sendDocumentPath();
    sendDocumentTitle();
    const removeLinkListener = addLinkListener();
    const removeScrollListener = addScrollListener();
    const interval = setInterval(() => {
        sendDocumentPath();
        sendDocumentTitle();
    }, 200);

    return () => {
        clearInterval(interval);
        removeLinkListener();
        removeScrollListener();
    };
}

let _lastDocumentHref: string | null = null;

/**
 * Send a postMessage with our document path if it has changed since the last time we were called.
 */
function sendDocumentPath() {
    const newHref = window.location.href;
    if (_lastDocumentHref === newHref) {
        return;
    }
    _lastDocumentHref = newHref;
    sendMessageToParentFrame({
        type: "embeddedHrefUpdate",
        href: newHref,
    });
}

let _lastDocumentTitle: string | null = null;
/**
 * Send a postMessage with our document title if it has changed since the last time we were called.
 */
function sendDocumentTitle() {
    const newTitle = document.title;
    if (_lastDocumentTitle === newTitle) {
        return;
    }
    _lastDocumentTitle = newTitle;
    sendMessageToParentFrame({
        type: "embeddedTitleUpdate",
        title: newTitle,
    });
}

/**
 * Send our current scrolling state to the remote embed.
 */
function sendScrollState() {
    if (!document.scrollingElement) {
        // Bailout, we don't have a scrolling element by default in our tests.
        return;
    }
    requestAnimationFrame(() => {
        sendMessageToParentFrame({
            type: "embeddedScrollUpdate",
            // If we have scrolled past a threshold trigger this.
            isScrolled: document.scrollingElement!.scrollTop > 50,
            // This is sent if we are at the end or start of the document.
            isScrollEnd:
                document.scrollingElement!.scrollTop === 0 ||
                document.scrollingElement!.scrollTop + document.scrollingElement!.clientHeight >=
                    document.scrollingElement!.scrollHeight,
        });
    });
}

/**
 * Register our scroll listener.
 */
function addScrollListener() {
    sendScrollState();
    const _sendScrollState = debounce(sendScrollState, 1000 / 60);
    document.addEventListener("scroll", _sendScrollState);
    return () => {
        document.removeEventListener("scroll", _sendScrollState);
    };
}

function addLinkListener() {
    const linkListener = delegateEvent("click", "a", (e: MouseEvent) => {
        if (e.metaKey) {
            // When meta key is pressed user is already opening this in another tab.
            return;
        }
        if (!(e.target instanceof HTMLAnchorElement)) {
            // This is to satisfy the type checker.
            return;
        }

        if (e.target.target === "_blank") {
            // Nothing to do, this is already opening another tab.
            return;
        }
        const linkTarget = e.target.href;
        if (!siteUrl(linkTarget).startsWith(siteUrl(""))) {
            // The url is on a different domain or site.
            e.preventDefault();
            sendMessageToParentFrame({
                type: "navigateExternalDomain",
                href: linkTarget,
            });
        }
    });

    return () => {
        removeDelegatedEvent(linkListener);
    };
}

/**
 * Send a message to our parent iframe.
 */
function sendMessageToParentFrame(event: EmbedEvent) {
    window.top?.postMessage(event, getMeta("embed.remoteUrl"));
}

/**
 * Check if the user should bypass our forceEmbed config.
 */
function getBypassEmbed(): boolean {
    const existingSessionBypass = Boolean(sessionStorage.getItem("bypassEmbed") ?? false);
    if (existingSessionBypass) {
        return true;
    }

    const searchParams = new URLSearchParams(window.location.search);
    const bypassEmbed = Boolean(searchParams.get("bypassEmbed") ?? false);
    if (bypassEmbed) {
        try {
            sessionStorage.setItem("bypassEmbed", "true");
            return true;
        } catch (err) {
            console.error("Failed to stash bypassEmbed in session storage");
            return true;
        }
    }

    return false;
}
