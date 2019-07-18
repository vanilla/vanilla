/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { expect } from "chai";
import sinon from "sinon";
import { FocusWatcher } from "./FocusWatcher";

describe("FocusWatcher", () => {
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

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

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

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

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

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

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

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

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

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

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

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

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
