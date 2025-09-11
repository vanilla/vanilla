/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { waitFor } from "@testing-library/react";
import { FocusWatcher } from "./FocusWatcher";

describe("FocusWatcher", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        document.body.innerHTML = `
        <div>
            <div tabindex="0" id="root1">
                <button id="item1"></button>
                <button id="item2"></button>
                <span id="notfocusable"></span>
            </div>
            <button id="item3"></button>
            <div id="modals"><button id="modalbutton"></button</div>
            <div data-reach-popover tabindex="-1" id="popover"><button id="inpopover" /></div>
        </div>`;
    });

    it("notifies about focus entering and leaving", () => {
        const spy = vitest.fn();
        const root1 = document.getElementById("root1")!;
        const item2 = document.getElementById("item2")!;
        const item3 = document.getElementById("item3")!;

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

        root1.focus();
        vi.advanceTimersToNextTimer();
        item3.focus();
        vi.advanceTimersToNextTimer();
        item2.focus();
        vi.advanceTimersToNextTimer();
        item3.focus();
        vi.advanceTimersToNextTimer();

        expect(spy).toHaveBeenCalledTimes(4);
        expect(spy.mock.calls[0][0]).toBe(true);
        expect(spy.mock.calls[1][0]).toBe(false);
        expect(spy.mock.calls[2][0]).toBe(true);
        expect(spy.mock.calls[3][0]).toBe(false);
    });

    it("does not notify about focus leaving into itself", () => {
        const spy = vitest.fn();
        const root1 = document.getElementById("root1")!;
        const item1 = document.getElementById("item1")!;
        const modalButton = document.getElementById("modalbutton")!;
        const popover = document.getElementById("popover")!;
        const inPopover = document.getElementById("inpopover")!;

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

        root1.focus();
        vitest.advanceTimersToNextTimer();
        spy.mockReset();
        item1.focus();
        vitest.advanceTimersToNextTimer();
        expect(spy.mock.calls[0][0]).toBe(true);

        spy.mockReset();

        popover.focus();
        vitest.advanceTimersToNextTimer();
        expect(spy).not.toHaveBeenCalled();

        inPopover.focus();
        vitest.advanceTimersToNextTimer();
        expect(spy).not.toHaveBeenCalled();
    });

    it("does not notify about focus going to the 'body'", () => {
        const spy = vitest.fn();
        const root1 = document.getElementById("root1")!;
        const item1 = document.getElementById("item1")!;

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

        item1.focus();
        document.body.focus();
        expect(spy).not.toHaveBeenCalled();
    });

    it("does not notify when an item inside of the root is clicked", () => {
        const spy = vitest.fn();
        const root1 = document.getElementById("root1")!;
        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const notfocusable = document.getElementById("notfocusable")!;

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

        root1.focus();
        spy.mockReset();
        item2.click();
        expect(spy).not.toHaveBeenCalled();

        item1.focus();
        spy.mockReset();
        notfocusable.click();
        expect(spy).not.toHaveBeenCalled();
    });

    it("does not notify when an item in popover of modal is clicked", () => {
        const spy = vitest.fn();
        const root1 = document.getElementById("root1")!;
        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const notfocusable = document.getElementById("notfocusable")!;

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

        root1.focus();
        spy.mockReset();
        item2.click();
        expect(spy).not.toHaveBeenCalled();

        item1.focus();
        spy.mockReset();
        notfocusable.click();
        expect(spy).not.toHaveBeenCalled();
    });

    it("notifies false when items outside are clicked", () => {
        const spy = vitest.fn();
        const root1 = document.getElementById("root1")!;
        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;
        const item3 = document.getElementById("item3")!;

        const watcher = new FocusWatcher(root1, spy);
        watcher.start();

        root1.focus();
        spy.mockReset();
        item3.click();
        expect(spy).toHaveBeenCalledExactlyOnceWith(false);

        item1.focus();
        spy.mockReset();
        item3.click();
        expect(spy).toHaveBeenCalledExactlyOnceWith(false);
    });
});
