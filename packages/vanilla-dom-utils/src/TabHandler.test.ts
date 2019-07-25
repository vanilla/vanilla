/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { expect } from "chai";
import { TabHandler } from "./TabHandler";

describe("TabHandler", () => {
    it("can find a tabbable element", () => {
        document.body.innerHTML = `
        <div>
            <button id="item1"></button>
            <button id="item2"></button>
        </div>`;

        const item1 = document.getElementById("item1")!;
        const item2 = document.getElementById("item2")!;

        item1.focus();
        const handler = new TabHandler();
        const nextItem = handler.getNext();

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
        const handler = new TabHandler();
        const nextItem = handler.getNext(undefined, true);

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
        const handler = new TabHandler(tree1);
        const nextItem = handler.getNext();

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
        const handler = new TabHandler(undefined, [item3, item4]);
        const nextItem = handler.getNext();

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
        const handler = new TabHandler(undefined, [item4], [root1]);
        const nextItem = handler.getNext();

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
        const handler = new TabHandler(undefined, undefined, [root1]);
        const nextItem = handler.getNext();
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
        const handler = new TabHandler(undefined, undefined, [root1]);
        const nextItem = handler.getNext();
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
        const handler = new TabHandler(undefined, [root1], [root1]);
        const nextItem = handler.getNext();
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
        const handler = new TabHandler();
        const nextItem = handler.getNext(item1);

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
        const handler = new TabHandler();
        const nextItem = handler.getNext();

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
        const handler = new TabHandler();
        const nextItem = handler.getNext(undefined, undefined, false);

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
        const handler = new TabHandler(treeRoot, [itemExcluded], [tree1]);
        const nextItem = handler.getNext(item3, true);

        expect(nextItem).eq(item5);
    });
});
