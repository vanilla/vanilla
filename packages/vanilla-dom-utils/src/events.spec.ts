/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { delegateEvent, removeAllDelegatedEvents, removeDelegatedEvent } from "./index";

describe("delegateEvent()", () => {
    beforeEach(() => {
        removeAllDelegatedEvents();

        document.body.innerHTML = `
            <div>
                <div class="scope1">
                    <button class="filterSelector">MyButton</button>
                    <button class="altFilterSelector">OtherButton</button>
                </div>
                <div class="scope2">
                    <button class="filterSelector">MyButton</button>
                    <button class="altFilterSelector">OtherButton</button>
                </div>
            </div>
        `;
    });

    it("A event can be registered successfully", () => {
        const callback = vitest.fn();
        delegateEvent("click", "", callback);

        const button = document.querySelector(".filterSelector") as HTMLElement;
        button.click();
        expect(callback).toHaveBeenCalledTimes(1);
    });

    it("identical events will not be registered twice", () => {
        const callback = vitest.fn();
        const hash1 = delegateEvent("click", "", callback);
        const hash2 = delegateEvent("click", "", callback);
        const hash3 = delegateEvent("click", "", callback);

        const button = document.querySelector(".filterSelector") as HTMLElement;
        button.click();
        expect(callback).toHaveBeenCalledTimes(1);

        // Hashes should all be for the same handler.
        expect(hash1).eq(hash2);
        expect(hash2).eq(hash3);
    });

    describe("delegation filtering works()", () => {
        const callback = vitest.fn();

        beforeEach(() => {
            delegateEvent("click", ".filterSelector", callback);
        });

        it("events can be delegated to their filterSelector", () => {
            document.querySelectorAll(".filterSelector").forEach((button: HTMLElement) => {
                button.click();
            });
            expect(callback).toHaveBeenCalledTimes(2);
        });

        it("delegated events only match their filterSelector", () => {
            callback.mockReset();

            document.querySelectorAll(".altFilterSelector").forEach((button: HTMLElement) => {
                button.click();
            });

            expect(callback).not.toHaveBeenCalled();
        });
    });

    describe("delegation scoping works", () => {
        const callback = vitest.fn();

        beforeEach(() => {
            delegateEvent("click", "", callback, ".scope1");
        });

        it("events can be scoped to their scopeSelector", () => {
            document.querySelectorAll(".scope1 button").forEach((button: HTMLElement) => {
                button.click();
            });
            expect(callback).toHaveBeenCalledTimes(2);
        });

        it("delegated events only match their scopeSelector", () => {
            callback.mockReset();

            document.querySelectorAll(".scope2 button").forEach((button: HTMLElement) => {
                button.click();
            });

            expect(callback).not.toHaveBeenCalled();
        });
    });
});

describe("removeDelegatedEvent() && removeAllDelegatedEvents()", () => {
    const callback1 = vitest.fn();
    const callback2 = vitest.fn();
    let eventHandler1;
    let eventHandler2;

    beforeEach(() => {
        callback1.mockReset();
        callback2.mockReset();
        eventHandler1 = delegateEvent("click", "", callback1);
        eventHandler2 = delegateEvent("click", ".scope1", callback2, ".filterSelector");
    });

    it("can remove a single event", () => {
        removeDelegatedEvent(eventHandler1);
        (document.querySelector(".scope1 .filterSelector") as HTMLElement).click();

        expect(callback1).not.toHaveBeenCalled();
        expect(callback2).toHaveBeenCalledTimes(1);
    });

    it("can remove all events", () => {
        removeAllDelegatedEvents();
        (document.querySelector(".scope1 .filterSelector") as HTMLElement).click();

        expect(callback1).not.toHaveBeenCalled();
        expect(callback2).not.toHaveBeenCalled();
    });
});
