/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    escapeHTML,
    unescapeHTML,
    removeAllDelegatedEvents,
    delegateEvent,
    removeDelegatedEvent,
    getFormData,
    watchFocusInDomTree,
} from "@library/dom";
import { expect } from "chai";
import sinon from "sinon";

const input = `<script>alert("Got you!")</script>`;
const output = `&lt;script&gt;alert("Got you!")&lt;/script&gt;`;

it("escapes html", () => {
    expect(escapeHTML(input)).to.deep.equal(output);
});

it("unescapes html", () => {
    expect(unescapeHTML(output)).to.deep.equal(input);
});

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
        const callback = sinon.spy();
        delegateEvent("click", "", callback);

        const button = document.querySelector(".filterSelector") as HTMLElement;
        button.click();
        sinon.assert.calledOnce(callback);
    });

    it("identical events will not be registered twice", () => {
        const callback = sinon.spy();
        delegateEvent("click", "", callback);
        delegateEvent("click", "", callback);
        delegateEvent("click", "", callback);

        const button = document.querySelector(".filterSelector") as HTMLElement;
        button.click();
        sinon.assert.calledOnce(callback);
    });

    describe("delegation filtering works()", () => {
        const callback = sinon.spy();

        beforeEach(() => {
            delegateEvent("click", ".filterSelector", callback);
        });

        it("events can be delegated to their filterSelector", () => {
            document.querySelectorAll(".filterSelector").forEach((button: HTMLElement) => {
                button.click();
            });
            sinon.assert.calledTwice(callback);
        });

        it("delegated events only match their filterSelector", () => {
            callback.resetHistory();

            document.querySelectorAll(".altFilterSelector").forEach((button: HTMLElement) => {
                button.click();
            });

            sinon.assert.notCalled(callback);
        });
    });

    describe("delegation scoping works", () => {
        const callback = sinon.spy();

        beforeEach(() => {
            delegateEvent("click", "", callback, ".scope1");
        });

        it("events can be scoped to their scopeSelector", () => {
            document.querySelectorAll(".scope1 button").forEach((button: HTMLElement) => {
                button.click();
            });
            sinon.assert.calledTwice(callback);
        });

        it("delegated events only match their scopeSelector", () => {
            callback.resetHistory();

            document.querySelectorAll(".scope2 button").forEach((button: HTMLElement) => {
                button.click();
            });

            sinon.assert.notCalled(callback);
        });
    });
});

describe("removeDelegatedEvent() && removeAllDelegatedEvents()", () => {
    const callback1 = sinon.spy();
    const callback2 = sinon.spy();
    let eventHandler1;
    let eventHandler2;

    beforeEach(() => {
        callback1.resetHistory();
        callback2.resetHistory();
        eventHandler1 = delegateEvent("click", "", callback1);
        eventHandler2 = delegateEvent("click", ".scope1", callback2, ".filterSelector");
    });

    it("can remove a single event", () => {
        removeDelegatedEvent(eventHandler1);
        (document.querySelector(".scope1 .filterSelector") as HTMLElement).click();

        sinon.assert.notCalled(callback1);
        sinon.assert.calledOnce(callback2);
    });

    it("can remove all events", () => {
        removeAllDelegatedEvents();
        (document.querySelector(".scope1 .filterSelector") as HTMLElement).click();

        sinon.assert.notCalled(callback1);
        sinon.assert.notCalled(callback2);
    });
});

describe("getFormData()", () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <form>
                <input type="text" name="foo" value="foo">
            </form>
        `;
    });

    it("can get data out of a form", () => {
        const form = document.querySelector("form");
        expect(getFormData(form)).deep.equals({ foo: "foo" });
    });
});

describe("watchFocusInDomTree()", () => {
    beforeEach(() => {
        document.body.innerHTML = `
        <div>
            <div tabindex="0" id="root1">
                <button id="item1"></button>
                <button id="item2"></button>
                <span id="notfocusable"></span>
            </div>
            <button id="item3"></button>
        </div>`;
    });

    it("notifies about focus entering", () => {
        const spy = sinon.spy();
        const root1 = document.getElementById("root1")!;
        const item2 = document.getElementById("item2")!;
        const item3 = document.getElementById("item3")!;

        watchFocusInDomTree(root1, spy);

        root1.focus();
        expect(spy.calledOnceWith(true));
        item3.focus();

        spy.resetHistory();
        item2.focus();
        expect(spy.calledOnceWith(true));
    });

    it("notifies about focus leaving", () => {
        const spy = sinon.spy();
        const root1 = document.getElementById("root1")!;
        const item2 = document.getElementById("item2")!;
        const item3 = document.getElementById("item3")!;

        watchFocusInDomTree(root1, spy);

        root1.focus();
        spy.resetHistory();
        item3.focus();
        expect(spy.calledOnceWith(false));

        item2.focus();
        spy.resetHistory();
        item3.focus();
        expect(spy.calledOnceWith(false));
    });

    it("does not notify about focus leaving into itself", () => {
        const spy = sinon.spy();
        const root1 = document.getElementById("root1")!;
        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const item3 = document.getElementById("item3")!;

        watchFocusInDomTree(root1, spy);

        root1.focus();
        spy.resetHistory();
        item1.focus();
        expect(spy.calledOnceWith(false));

        item1.focus();
        spy.resetHistory();
        item2.focus();
        expect(spy.calledOnceWith(false));
    });

    it("does not notify about focus going to the 'body'", () => {
        const spy = sinon.spy();
        const root1 = document.getElementById("root1")!;
        const item1 = document.getElementById("item1")!;

        watchFocusInDomTree(root1, spy);

        item1.focus();
        document.body.focus();
        expect(spy.notCalled).eq(true);
    });

    it("does not notify when an item inside of the root is clicked", () => {
        const spy = sinon.spy();
        const root1 = document.getElementById("root1")!;
        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const notfocusable = document.getElementById("notfocusable")!;

        watchFocusInDomTree(root1, spy);

        root1.focus();
        spy.resetHistory();
        item2.click();
        expect(spy.notCalled).eq(true);

        item1.focus();
        spy.resetHistory();
        notfocusable.click();
        expect(spy.notCalled).eq(true);
    });

    it("notifies false when items outside are clicked", () => {
        const spy = sinon.spy();
        const root1 = document.getElementById("root1")!;
        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const item3 = document.getElementById("item3")!;

        watchFocusInDomTree(root1, spy);

        root1.focus();
        spy.resetHistory();
        item3.click();
        expect(spy.calledOnceWith(false));

        item1.focus();
        spy.resetHistory();
        item3.click();
        expect(spy.calledOnceWith(false));
    });
});
