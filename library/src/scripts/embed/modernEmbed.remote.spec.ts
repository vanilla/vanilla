/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { EmbedEvent } from "@library/embed/embedEvents";
import { VanillaEmbedElement } from "@library/embed/modernEmbed.remote";

describe("modernEmbed.remote - <vanilla-embed />", () => {
    beforeEach(() => {
        // @ts-ignore
        delete window.location;
        window.location = new URL("https://example.com") as any;
    });

    it("throws if connected without a remote-url", () => {
        const embed = new VanillaEmbedElement();
        expect(() => {
            embed.connectedCallback();
        }).toThrowErrorMatchingInlineSnapshot(`"You must define a remote url"`);
    });
    it("constructs an iframe with a proper src url", () => {
        const embed = new VanillaEmbedElement();
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com");
        attachEmbed(embed);
        const innerFrame = embed.querySelector("iframe")!;
        expect(innerFrame).toBeInstanceOf(HTMLIFrameElement);
        expect(innerFrame.src).toBe("https://mysite.vanillaforums.com/");
    });

    it("uses the initial-path attribute", () => {
        const embed = new VanillaEmbedElement();
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com");
        embed.setAttribute("initial-path", "/hub");
        attachEmbed(embed);
        const innerFrame = embed.querySelector("iframe")!;
        expect(innerFrame).toBeInstanceOf(HTMLIFrameElement);
        expect(innerFrame.src).toBe("https://mysite.vanillaforums.com/hub");
    });

    it("will not use an initial hash instead of an initial href", () => {
        const embed = new VanillaEmbedElement();
        window.location.hash = "/initial-path/to/thing#own-hash";
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com");
        embed.setAttribute("initial-path", "/dont-use-me");
        attachEmbed(embed);
        const innerFrame = embed.querySelector("iframe")!;
        expect(innerFrame).toBeInstanceOf(HTMLIFrameElement);
        expect(innerFrame.src).toBe("https://mysite.vanillaforums.com/initial-path/to/thing#own-hash");
    });

    it("can can use an entire initial url", () => {
        const embed = new VanillaEmbedElement();
        window.location.hash = "https://mysite.vanillaforums.com/initial-path/to/thing#own-hash";
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com");
        attachEmbed(embed);
        const innerFrame = embed.querySelector("iframe")!;
        expect(innerFrame).toBeInstanceOf(HTMLIFrameElement);
        expect(innerFrame.src).toBe("https://mysite.vanillaforums.com/initial-path/to/thing#own-hash");
    });

    it("can can use an entire initial url (with a subfolder as our root)", () => {
        const embed = new VanillaEmbedElement();
        window.location.hash = "https://mysite.vanillaforums.com/my-node/initial-path/to/thing#own-hash";
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com/my-node");
        attachEmbed(embed);
        const innerFrame = embed.querySelector("iframe")!;
        expect(innerFrame).toBeInstanceOf(HTMLIFrameElement);
        expect(innerFrame.src).toBe("https://mysite.vanillaforums.com/my-node/initial-path/to/thing#own-hash");
        expect(window.location.hash).toBe("#/initial-path/to/thing%23own-hash");
    });

    it("can handle hash changes from the iframe", async () => {
        const embed = new VanillaEmbedElement();
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com");
        attachEmbed(embed);
        await sendEmbedEvent({
            type: "embeddedHrefUpdate",
            href: "https://mysite.vanillaforums.com/new-path#with-hash",
        });
        expect(embed.state.currentFrameUrl).toBe("https://mysite.vanillaforums.com/new-path#with-hash");
        // We should be able to send the same thing again with no difference.
        await sendEmbedEvent({
            type: "embeddedHrefUpdate",
            href: "https://mysite.vanillaforums.com/new-path#with-hash",
        });
        expect(embed.state.currentFrameUrl).toBe("https://mysite.vanillaforums.com/new-path#with-hash");
    });

    it("can URL changes to external urls", async () => {
        const embed = new VanillaEmbedElement();
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com");
        attachEmbed(embed);
        await sendEmbedEvent({
            type: "navigateExternalDomain",
            href: "https://some-site.com/some-path",
        });
        expect(window.location.href).toBe("https://some-site.com/some-path");
    });

    it("can handle full redirects from http sites when configured for https", () => {
        const embed = new VanillaEmbedElement();
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com");
        window.location.hash = "http://mysite.vanillaforums.com/initial-path/to/thing#own-hash";
        attachEmbed(embed);
        const innerFrame = embed.querySelector("iframe")!;
        expect(innerFrame).toBeInstanceOf(HTMLIFrameElement);
        expect(innerFrame.src).toBe("https://mysite.vanillaforums.com/initial-path/to/thing#own-hash");
        expect(window.location.hash).toBe("#/initial-path/to/thing%23own-hash");
    });

    it("appends a sso parameter to the iframe when the sso-string is configured on the embed element", () => {
        const embed = new VanillaEmbedElement();
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com");
        embed.setAttribute("sso-string", "my-remote-generated-sso-string");
        attachEmbed(embed);
        const innerFrame = embed.querySelector("iframe")!;
        expect(innerFrame.src).toBe("https://mysite.vanillaforums.com/?sso=my-remote-generated-sso-string");
    });

    it("appends a sso parameter to the iframe when the sso-string is configured on the window object", () => {
        const embed = new VanillaEmbedElement();
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com");
        window.vanilla_sso = "my-remote-generated-sso-string";
        attachEmbed(embed);
        const innerFrame = embed.querySelector("iframe")!;
        expect(innerFrame.src).toBe("https://mysite.vanillaforums.com/?sso=my-remote-generated-sso-string");
    });

    it("appends sso-string to urls with existing parameters", async () => {
        const embed = new VanillaEmbedElement();
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com?query=test");
        embed.setAttribute("sso-string", "my-remote-generated-sso-string");
        attachEmbed(embed);
        const innerFrame = embed.querySelector("iframe")!;
        expect(innerFrame.src).toBe("https://mysite.vanillaforums.com/?query=test&sso=my-remote-generated-sso-string");
    });

    it("strips sso params before setting the window location", async () => {
        const embed = new VanillaEmbedElement();
        embed.setAttribute("remote-url", "https://mysite.vanillaforums.com");
        embed.setAttribute("sso-string", "my-remote-generated-sso-string");
        attachEmbed(embed);
        await sendEmbedEvent({
            type: "embeddedHrefUpdate",
            href: "https://mysite.vanillaforums.com/?query=test&sso=my-remote-generated-sso-string",
        });
        expect(window.location.hash).toBe(`#/${escape("?query=test")}`);
    });
});

async function sendEmbedEvent(event: EmbedEvent) {
    return new Promise((resolve) => {
        const listener = (e) => {
            resolve(e.data);
            window.removeEventListener("message", listener);
        };
        window.addEventListener("message", listener);
        window.postMessage(event, "*");
    });
}

function attachEmbed(embed: VanillaEmbedElement) {
    document.body.innerHTML = "<div id='root'></div>";
    document.querySelector("#root")?.appendChild(embed);
}
