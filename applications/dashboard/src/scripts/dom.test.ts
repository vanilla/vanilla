/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import {
    escapeHTML,
    unescapeHTML,
    removeAllDelegatedEvents,
    delegateEvent,
    removeDelegatedEvent,
    getFormData,
    getNextTabbableElement,
    watchFocusInDomTree,
    stickyHeader,
} from "@dashboard/dom";
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

describe("getNextTabbableElement()", () => {
    it("can find a tabbable element", () => {
        document.body.innerHTML = `
        <div>
            <button id="item1"></button>
            <button id="item2"></button>
        </div>`;

        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;

        item1.focus();
        const nextItem = getNextTabbableElement();

        expect(nextItem).eq(item2);
    });

    it("works in reverse", () => {
        document.body.innerHTML = `
        <div>
            <button id="item1"></button>
            <button id="item2"></button>
        </div>`;

        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;

        item2.focus();
        const nextItem = getNextTabbableElement({ reverse: true });

        expect(nextItem).eq(item1);
    });

    it("can be scoped within a DOM tree", () => {
        document.body.innerHTML = `
        <div>
            <div id="tree1">
                <button id="item1"></button>
                <button id="item2"></button>
            </div>
            <div id="tree2">
                <button id="item3"></button>
            </div>
        </div>`;

        const tree1 = document.getElementById("tree1")!;
        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;

        item2.focus();
        const nextItem = getNextTabbableElement({ root: tree1 });

        expect(nextItem).eq(item1);
    });

    it("can exclude elements", () => {
        document.body.innerHTML = `
        <div>
            <button id="item1"></button>
            <button id="item2"></button>
            <button id="item3"></button>
            <button id="item4"></button>
        </div>`;

        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const item3 = document.getElementById("item3")!;
        const item4 = document.getElementById("item4")!;

        item2.focus();
        const nextItem = getNextTabbableElement({ excludedElements: [item3, item4] });

        expect(nextItem).eq(item1);
    });

    it("can exclude roots", () => {
        document.body.innerHTML = `
        <div>
            <button id="item1"></button>
            <button id="item2"></button>
            <div id="root1"><button id="item3"></button></div>
            <button id="item4"></button>
        </div>`;

        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const root1 = document.getElementById("root1")!;
        const item4 = document.getElementById("item4")!;

        item2.focus();
        const nextItem = getNextTabbableElement({ excludedRoots: [root1], excludedElements: [item4] });

        expect(nextItem).eq(item1);
    });

    it("excluding a root doesn't exclude the element", () => {
        document.body.innerHTML = `
        <div>
            <button id="item1"></button>
            <button id="item2"></button>
            <div tabindex="0" id="root1"><button id="item3"></button></div>
            <button id="item4"></button>
        </div>`;

        const root1 = document.getElementById("root1")!;
        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const item4 = document.getElementById("item4")!;

        item2.focus();
        const nextItem = getNextTabbableElement({ excludedRoots: [root1] });
        expect(nextItem).eq(root1);
    });

    it("the currently selected root isn't excluded", () => {
        document.body.innerHTML = `
        <div>
            <div tabindex="0" id="root1"><button id="item1"></button></div>
            <button id="item2"></button>
        </div>`;

        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const root1 = document.getElementById("root1")!;

        item1.focus();
        const nextItem = getNextTabbableElement({ excludedRoots: [root1] });
        expect(nextItem).eq(item2);
    });

    it("the currently selected item cannot be excluded", () => {
        document.body.innerHTML = `
        <div>
            <div tabindex="0" id="root1"><button id="item1"></button></div>
            <button id="item2"></button>
        </div>`;

        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const root1 = document.getElementById("root1")!;

        root1.focus();
        const nextItem = getNextTabbableElement({ excludedRoots: [root1], excludedElements: [root1] });
        expect(nextItem).eq(item2);
    });

    it("can find focus from an arbitrary element", () => {
        document.body.innerHTML = `
        <div>
            <button id="item1"></button>
            <button id="item2"></button>
        </div>`;

        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;

        item2.focus();
        const nextItem = getNextTabbableElement({ fromElement: item1 });

        expect(nextItem).eq(item2);
    });

    it("returns the currently focused element if none is found", () => {
        document.body.innerHTML = `
        <div>
            <button id="item1"></button>
            <span id="item2"></span>
        </div>`;

        const item1 = document.getElementById("item1")!;

        item1.focus();
        const nextItem = getNextTabbableElement();

        expect(nextItem).eq(item1);
    });

    it("can prevent looping", () => {
        document.body.innerHTML = `
        <div>
            <button id="item1"></button>
            <button id="item2"></button>
        </div>`;

        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;

        item2.focus();
        const nextItem = getNextTabbableElement({ allowLooping: false });

        expect(nextItem).eq(null);
    });

    it("can handle all options at once", () => {
        document.body.innerHTML = `
        <div>
            <button id="initialFocus"></button>
            <button id="beforeRoot"></button>
            <div id="treeRoot">
                <div id="tree1">
                    <button id="item1"></button>
                    <button id="item2"></button>
                </div>
                <div id="tree2">
                    <button id="itemExcluded"></button>
                    <button id="item3"></button>
                </div>
                <div id="tree3">
                    <button id="item4"></button>
                    <button id="item5"></button>
                </div>
            </div>
        </div>`;

        const tree1 = document.getElementById("tree1")!;
        const tree2 = document.getElementById("tree2")!;
        const item3 = document.getElementById("item3")!;
        const item5 = document.getElementById("item5")!;
        const treeRoot = document.getElementById("treeRoot")!;
        const itemExcluded = document.getElementById("itemExcluded")!;
        const initialFocus = document.getElementById("initialFocus")!;

        initialFocus.focus();
        const nextItem = getNextTabbableElement({
            root: treeRoot,
            fromElement: item3,
            reverse: true,
            excludedRoots: [tree1],
            excludedElements: [itemExcluded],
        });

        expect(nextItem).eq(item5);
    });
});

describe("watchFocusInDomTree()", () => {
    beforeEach(() => {
        document.body.innerHTML = `
        <div>
            <div tabindex="0" id="root1">
                <button id="item1"></button>
                <button id="item2"></button>
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
});


// describe("stickyHeader()", () => {
//     beforeEach(() => {
//         document.body.innerHTML = `<div id="root" style="height: 200vh;">
//             <div class="js-scrollVisibility">Header</div>
//         </div>`;
//     });
//
//     it("initializes Vanilla's default sticky header", () => {
//         //window.scrollBy
//         //window.scrollTo
//     });
//
//
// });
