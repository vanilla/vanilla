/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { EmbedEvent } from "@library/embed/embedEvents";
import { initModernEmbed } from "@library/embed/modernEmbed.local";
import { setMeta } from "@library/utility/appUtils";
import isEqual from "lodash/isEqual";

jest.useFakeTimers();

describe("modernEmbed.local", () => {
    let _teardown: (() => void) | null = null;

    beforeEach(() => {
        // @ts-ignore
        delete window.location;
        window.location = new URL("https://forum.vanilla.com") as any;
        setMeta("embed", {
            enabled: true,
            isModernEmbed: true,
            remoteUrl: "https://embed.vanilla.com",
        });
    });

    afterEach(() => {
        _teardown?.();
    });

    function bootEmbed() {
        _teardown = initModernEmbed();
        return _teardown;
    }

    function assertDoesNothingWithMeta(embedConfig: any) {
        setMeta("embed", embedConfig);
        const teardown = bootEmbed();
        expect(teardown).toBe(null);
    }

    it("does nothing if embed is not configured", () => {
        assertDoesNothingWithMeta({});
    });

    it("does nothing if embed is disabled", () => {
        assertDoesNothingWithMeta({
            enabled: false,
            isModernEmbed: true,
            remoteUrl: "https://test.com",
        });
    });

    it("does nothing if modern embed is disabled", () => {
        assertDoesNothingWithMeta({
            enabled: true,
            isModernEmbed: false,
            remoteUrl: "https://test.com",
        });
    });

    it("does nothing if remoteUrl is not configured", () => {
        assertDoesNothingWithMeta({
            enabled: true,
            isModernEmbed: true,
            remoteUrl: "",
        });
        assertDoesNothingWithMeta({
            enabled: true,
            isModernEmbed: true,
            remoteUrl: null,
        });
        assertDoesNothingWithMeta({
            enabled: true,
            isModernEmbed: true,
            remoteUrl: false,
        });
    });

    it("redirects to the remoteUrl with forceEmbed", () => {
        setMeta("embed.forceModernEmbed", true);
        // @ts-ignore
        delete window.self;
        (window as any).self = makeWindowStub("innerStub");
        // @ts-ignore
        delete window.top;
        (window as any).top = window.self; // We have the same window.
        window.location.href = "https://forum.vanilla.com/path#with-hash";
        const teardown = bootEmbed();
        expect(teardown).toBeNull();
        expect(window.location.href).toBe("https://embed.vanilla.com/#https%3A//forum.vanilla.com/path%23with-hash");
    });

    it("sends title and href events to the client", () => {
        const windowStub = makeWindowStub("embedWindow");
        // @ts-ignore
        delete window.top;
        (window as any).top = windowStub;

        const teardown = bootEmbed();
        expect(teardown).not.toBeNull();

        assertEventWasFired(windowStub, {
            type: "embeddedHrefUpdate",
            href: "https://forum.vanilla.com/",
        });
        windowStub.postMessage.mockClear();

        window.location.href = "https://forum.vanilla.com/some/path#hash";
        document.title = "My Document Title";
        jest.advanceTimersToNextTimer();
        assertEventWasFired(windowStub, {
            type: "embeddedHrefUpdate",
            href: "https://forum.vanilla.com/some/path#hash",
        });
        assertEventWasFired(windowStub, {
            type: "embeddedTitleUpdate",
            title: "My Document Title",
        });
    });
});

function assertEventWasFired(windowStub: WindowStub, expectedEvent: EmbedEvent) {
    const mockCalls = windowStub.postMessage.mock.calls;
    const events = mockCalls.map((call) => call[0]) as EmbedEvent[];
    const found =
        events.find((event) => {
            return isEqual(event, expectedEvent);
        }) ?? null;

    expect(found).not.toBeNull();
}

function makeWindowStub(name: string) {
    const postMessageSpy = jest.fn();
    const windowStub = {
        stub: name,
        postMessage: postMessageSpy,
    };
    return windowStub;
}

type WindowStub = ReturnType<typeof makeWindowStub>;
