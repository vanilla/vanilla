/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import * as domUtility from "../dom-utility";
import { polyfillClosest } from "../polyfills";

const input = `<script>alert("Got you!")</script>`;
const output = `&lt;script&gt;alert("Got you!")&lt;/script&gt;`;

it("escapes html", () => {
    expect(domUtility.escapeHTML(input)).toBe(output);
});

it("unescapes html", () => {
    expect(domUtility.unescapeHTML(output)).toBe(input);
});

describe("delegateEvent", () => {

    beforeEach(() => {
        domUtility.removeAllDelegatedEvents();

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

    test("A event can be registered successfully", () => {
        const callback = jest.fn();
        domUtility.delegateEvent("click", null, callback);

        const button = document.querySelector(".filterSelector");
        button.click();
        expect(callback.mock.calls.length).toBe(1);
    });

    test("identical events will not be registered twice", () => {
        const callback = jest.fn();
        domUtility.delegateEvent("click", null, callback);
        domUtility.delegateEvent("click", null, callback);
        domUtility.delegateEvent("click", null, callback);

        const button = document.querySelector(".filterSelector");
        button.click();
        expect(callback.mock.calls.length).toBe(1);
    });

    describe("delegation filtering works", () => {
        const callback = jest.fn();

        beforeEach(() => {
            domUtility.delegateEvent("click", ".filterSelector", callback);
        });

        test("events can be delegated to their filterSelector", () => {
            document.querySelectorAll(".filterSelector").forEach(button => {
                button.click();
            });
            expect(callback.mock.calls.length).toBe(2);
        });

        test("delegated events only match their filterSelector", () => {
            callback.mockReset();

            document.querySelectorAll(".altFilterSelector").forEach(button => {
                button.click();
            })

            expect(callback.mock.calls.length).toBe(0);
        })
    });


    describe("delegation scoping works", () => {
        const callback = jest.fn();

        beforeEach(() => {
            domUtility.delegateEvent("click", null, callback, ".scope1");
        });

        test("events can be scoped to their scopeSelector", () => {
            document.querySelectorAll(".scope1 button").forEach(button => {
                button.click();
            });
            expect(callback.mock.calls.length).toBe(2);
        });

        test("delegated events only match their scopeSelector", () => {
            callback.mockReset();

            document.querySelectorAll(".scope2 button").forEach(button => {
                button.click();
            })

            expect(callback.mock.calls.length).toBe(0);
        })
    });
});

describe("removing delegated events", () => {
    const callback1 = jest.fn();
    const callback2 = jest.fn();
    let eventHandler1, eventHandler2;

    beforeEach(() => {
        callback1.mockReset();
        callback2.mockReset();
        eventHandler1 = domUtility.delegateEvent("click", null, callback1);
        eventHandler2 = domUtility.delegateEvent("click", ".scope1", callback2, ".filterSelector");
    });

    it("can remove a single event", () => {
        domUtility.removeDelegatedEvent(eventHandler1);
        document.querySelector(".scope1 .filterSelector").click();

        expect(callback1.mock.calls.length).toBe(0);
        expect(callback2.mock.calls.length).toBe(1);
    })

    it("can remove all events", () => {
        domUtility.removeAllDelegatedEvents();
        document.querySelector(".scope1 .filterSelector").click();

        expect(callback1.mock.calls.length).toBe(0);
        expect(callback2.mock.calls.length).toBe(0);
    })
});
