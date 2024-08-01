/**
 * This file contains code for the modern embed that runs on an external site, outside of the iframe.
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { EmbedEvent, EmbedScrollEvent } from "./embedEvents";

type Position = "static" | "sticky";

/**
 * Custom Element for a vanilla embed.
 * <vanilla-embed remote-url="https://vanillasite.com"></vanilla-embed>
 */
export class VanillaEmbedElement extends HTMLElement {
    /** Reference to the current iframe element. */
    public iframe: HTMLIFrameElement;

    /** The remote url we connect to. */
    public remoteUrl: string;

    public remoteOrigin: string;

    /** The position mode of the element. */
    public position: Position;

    /** An extra element with a static position that we can use to determine our scrollOffset. */
    public scrollOffseter: HTMLDivElement;

    /** The JSConnect SSO string so that users are seamlessly authenticated. */
    public ssoString: string;

    /**
     * Get the IFrame source
     * @returns string
     */
    private getFrameSource(): string {
        // Check for an sso string
        this.ssoString = this.getAttribute("sso-string") ?? window.vanilla_sso ?? null;
        const initialHash = window.location.hash.replace(/^#/, "").trim();

        const frameSource =
            initialHash.length === 0 && this.getAttribute("initial-path")
                ? // Use the initial-path if provided
                  this.createFrameUrl(this.getAttribute("initial-path") ?? "/")
                : // Otherwise try to pull the current one from our hash.
                  this.currentFrameUrlFromHash();

        // if there is a ssoString, we need to append it to the end of the URL to get the server to authenticate
        if (this.ssoString) {
            const url = new URL(frameSource);
            const params = new URLSearchParams(url.search);
            params.set("sso", this.ssoString);
            /**
             * Remove all query params and rebuild the URL with new params
             * which include the SSO string.
             */
            return `${frameSource.split("?")[0]}?${params.toString()}`;
        }

        return frameSource;
    }

    /**
     * Runs when the element is attached to the DOM.
     */
    public connectedCallback() {
        // Validation
        this.remoteUrl = this.getAttribute("remote-url")!;
        if (!this.remoteUrl) {
            throw new Error("You must define a remote url");
        }
        this.remoteUrl.replace(/\/$/, "");
        // Trim off trailing slashes
        const remoteUrlObj = new URL(this.remoteUrl);
        this.remoteOrigin = remoteUrlObj.origin;
        this.position = this.validatePosition();

        // Create our offseter
        this.scrollOffseter = document.createElement("div");
        this.appendChild(this.scrollOffseter);

        // Create the iframe
        this.iframe = document.createElement("iframe");
        this.iframe.src = this.getFrameSource();

        this.handleUpdate({
            type: "embeddedHrefUpdate",
            href: this.iframe.src,
            force: true,
        });

        // Basic visual styling.
        this.iframe.width = "100%";
        this.iframe.style.height = "100vh";
        this.iframe.style.border = "none";
        this.iframe.style.width = "100%";

        // Some pages noticably lag while transitioning if the frame doesn't have an opaque background.
        this.iframe.style.background = "#fff";
        // Used for animation of the element.
        this.iframe.style.position = "relative";
        this.iframe.style.transition = "margin 0.5s ease-in-out";
        this.iframe.style.willChange = "margin";
        this.appendChild(this.iframe);

        // Register event listeners.
        window.addEventListener("message", this.postMessageHandler);
        window.addEventListener("hashchange", this.hashChangeHandler);
    }

    /**
     * Unregister event listeners when we unmount.
     */
    public disconnectedCallback() {
        window.removeEventListener("message", this.postMessageHandler);
        window.removeEventListener("hashchange", this.hashChangeHandler);
    }

    /**
     * Validate our position property and return it.
     */
    private validatePosition = (): Position => {
        const position = this.getAttribute("position") ?? "sticky";

        switch (position) {
            case "sticky":
            case "static":
                return position;
            default:
                console.error(
                    "Invalid 'position' property for VanillaEmbed. Only 'static' and 'sticky' are allowed. Falling back to 'sticky'.",
                );
                return "sticky";
        }
    };

    /**
     * Event handler to receive messages from the iframe.
     */
    private postMessageHandler = (event: MessageEvent) => {
        if (event.origin !== this.remoteOrigin && event.origin !== "") {
            // Don't accept messages unless they come from our frame or from our test.
            return;
        }

        if (this.validateEvent(event.data)) {
            // Validat events go to the event handler.
            this.handleUpdate(event.data);
        }
    };

    /**
     * Ensure the event is in a format we expect.
     */
    private validateEvent(maybeEvent: unknown): maybeEvent is EmbedEvent {
        const result = typeof maybeEvent === "object" && maybeEvent !== null && "type" in maybeEvent;
        if (!result) {
            console.error("Event was not an embedEvent", { maybeEvent });
        }
        return result;
    }

    /**
     * Event handler to react to direct changes to the hash
     *
     * For example when using the browser back and forward buttons.
     */
    private hashChangeHandler = (event: HashChangeEvent) => {
        if (this.state.currentFrameUrl === this.currentFrameUrlFromHash()) {
            // Nothing actually changed
            return;
        }

        // Replace the iframe.
        // DO NOT modify the iframe src directory as this messes with the browser history
        // and makes it impossible to navigate forwards
        this.state.currentFrameUrl = this.currentFrameUrlFromHash();
        const cloned = this.iframe.cloneNode(true) as HTMLIFrameElement;
        cloned.src = this.currentFrameUrlFromHash();
        this.iframe.replaceWith(cloned);
        this.iframe = cloned;
    };

    /// State management
    public state = {
        isScrolled: false,
        currentFrameUrl: "" as string,
        lastScrollEvent: null as EmbedScrollEvent | null,
    };

    /**
     * Handle events to update state and perform side effects.
     */
    public handleUpdate = (event: EmbedEvent) => {
        switch (event.type) {
            case "embeddedHrefUpdate": {
                // Trim off the trailing slash.
                const newFrameUrl = event.href.replace(/\/$/, "");
                this.state.currentFrameUrl = newFrameUrl;
                if (event.force || newFrameUrl !== this.currentFrameUrlFromHash()) {
                    // Side effect - Update our hash if it's changed.
                    // Split the incoming URL by its params
                    const newFrameParts = newFrameUrl.split("?");
                    // Get the existing has from the URL by clearing whatever matches the remoteUrl
                    const newFrameHash = newFrameParts[0].replace(this.remoteUrl, "");
                    // Create a placeholder string for params
                    let newFrameParams = "";

                    // If there are params in the URL, we want to strip our SSO but leave everything else intact
                    if (newFrameParts[1] && newFrameParts[1].length > 0) {
                        // Make new search params object
                        const params = new URLSearchParams(newFrameParts[1]);
                        // Omit the sso param
                        params.delete("sso");
                        // Create a new param string
                        newFrameParams = `?${params.toString()}`;
                    }

                    // Update the hash with the original frame hash and the stripped params if they exist
                    window.location.hash = escape(`${newFrameHash}${newFrameParams}`);
                }

                break;
            }
            case "embeddedScrollUpdate": {
                if (this.position === "static") {
                    // Nothing to do with static position elements.
                    return;
                }

                if (
                    this.state.lastScrollEvent &&
                    this.state.lastScrollEvent.isScrollEnd === event.isScrollEnd &&
                    this.state.lastScrollEvent.isScrolled === event.isScrolled
                ) {
                    // No actual change in scroll event. Don't do anything.
                    return;
                }
                this.state.lastScrollEvent = event;

                if (event.isScrolled) {
                    const iframeTop = this.scrollOffseter.getBoundingClientRect().top;
                    const documentScroll = document.scrollingElement!.scrollTop;
                    const offset = iframeTop + documentScroll;
                    // We are scrolling down in the embed.
                    // Stick the iframe to the top of the document.
                    this.iframe.style.marginTop = `-${offset}px`;

                    if (event.isScrollEnd) {
                        // The iframe is scrolled completely to the bottom,
                        // Make sure the document scrolls all the way to the bottom (and exposes a footer if there is is one).
                        document.scrollingElement!.scrollTo({
                            top: document.scrollingElement!.scrollHeight,
                            behavior: "smooth",
                        });
                    } else {
                        // The iframe is scrolling but not at the bottom.
                        // Make sure the document scrolls back to the top.
                        document.scrollingElement!.scrollTo({ top: 0, behavior: "smooth" });
                    }
                } else {
                    // We are near the top of the document.
                    // Expose the header again.

                    this.iframe.style.marginTop = `0px`;
                    // The iframe is scrolled completely to the top,
                    // Make sure the document scrolls back to the top.
                    document.scrollingElement!.scrollTo({ top: 0, behavior: "smooth" });
                }
                break;
            }
            case "embeddedTitleUpdate": {
                document.title = event.title;
                break;
            }
            case "navigateExternalDomain": {
                window.location.href = event.href;
                break;
            }
            // Should we handle frame load?
            default: {
                console.error("Received an unknown event", event);
                break;
            }
        }
    };

    /// URL utilities
    /**
     * Create a URL for the frame given a path.
     */
    public createFrameUrl = (path: string) => {
        path = path.replace(/^http:\/\//, "https://");
        path = path.replace(this.remoteUrl, "").replace(unescape(escape(this.remoteUrl)), "");
        return `${this.remoteUrl}${path}`;
    };

    /**
     * Create a url for the frame using the current location hash.
     */
    public currentFrameUrlFromHash = () => {
        const realHash = window.location.hash;
        // Trim off the trialing hash.
        const trimmedStart = realHash.replace(/^#/, "");
        const unescapeHash = unescape(trimmedStart);
        return this.createFrameUrl(unescapeHash);
    };
}

// Register our custom element.
customElements.define("vanilla-embed", VanillaEmbedElement);
