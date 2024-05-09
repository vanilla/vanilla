/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { mountPortal, mountReact, mountReactMultiple, IMountable } from "./mounting";
import { vitest } from "vitest";

function HelloWorld(props: { id: string }) {
    return <div className="helloworld" id={props.id}></div>;
}

function PortalWorld(props: { id: string; contentID: string }) {
    return (
        <div className="portal" id={props.id}>
            <HelloWorld id={props.contentID} />
        </div>
    );
}

describe("mountPortal", () => {
    it("can create it's own container", async () => {
        document.body.innerHTML = "<div></div>";
        await mountPortal(<HelloWorld id={"world1-item"} />, "world1-cont");
        expect(document.getElementById("world1-cont")).toBeDefined();
        expect(document.getElementById("world1-item")).toBeDefined();
    });

    it("can push into an existing container", async () => {
        document.body.innerHTML = "<div></div>";
        await mountPortal(<HelloWorld id={"world1-item"} />, "world1-cont");
        await mountPortal(<HelloWorld id={"world1-item2"} />, "world1-cont");
        expect(document.getElementById("world1-cont")).toBeDefined();
        expect(document.getElementById("world1-item")).toBeDefined();
        expect(document.getElementById("world1-item2")).toBeDefined();
    });

    it("can be used as a portal to override existing containers", async () => {
        document.body.innerHTML = "<div></div>";
        await mountPortal(<HelloWorld id={"world1-item"} />, "world1-cont");
        await mountPortal(<PortalWorld id={"world1-portal"} contentID={"world1-item2"} />, "world1-cont");
        expect(document.getElementById("world1-cont")).toBeDefined();
        expect(document.getElementById("world1-item")).toBeDefined();
        expect(document.getElementById("world1-portal")).toBeDefined();
        expect(document.getElementById("world1-item2")).toBeDefined();
    });
});

describe("mountReact", () => {
    it("mounts the component to the target element", async () => {
        document.body.innerHTML = "";
        const callback = vitest.fn();
        const target = document.createElement("div");
        target.id = "target";
        document.body.appendChild(target);
        await mountReact(<HelloWorld id="world1-item" />, target, callback);
        expect(document.getElementById("target")).toBeDefined();
        expect(document.getElementById("world1-item")).toBeDefined();
        expect(callback).toHaveBeenCalledTimes(1);
    });

    it("mounts the component and overwrites the target", async () => {
        document.body.innerHTML = "";
        const callback = vitest.fn();
        const target = document.createElement("div");
        target.id = "target";
        document.body.appendChild(target);
        await mountReact(<HelloWorld id="world1-item" />, target, callback, { overwrite: true });
        expect(document.getElementById("target")).toBeNull();
        expect(document.getElementById("world1-item")).toBeDefined();
        expect(callback).toHaveBeenCalledTimes(1);
    });
});

describe("mountReactMultiple", () => {
    it("mounts an array of components to their respective targets", async () => {
        const callback = vitest.fn();
        const mountables = createMountables(4);
        await mountReactMultiple(mountables, callback);
        expect.assertions(mountables.length * 2 + 1);
        expect(callback).toHaveBeenCalledTimes(1);
        for (let idx = 0; idx < mountables.length; idx++) {
            expect(document.getElementById(`target-${idx}`)).toBeDefined();
            expect(document.getElementById(`world-${idx}`)).toBeDefined();
        }
    });

    it("mounts an array of components and overwites the targets", async () => {
        const callback = vitest.fn();
        const mountables = createMountables(6);
        await mountReactMultiple(mountables, callback, { overwrite: true });
        expect.assertions(mountables.length * 2 + 1);
        expect(callback).toHaveBeenCalledTimes(1);
        for (let idx = 0; idx < mountables.length; idx++) {
            expect(document.getElementById(`target-${idx}`)).toBeNull();
            expect(document.getElementById(`world-${idx}`)).toBeDefined();
        }
    });
});

function createMountables(count: number): IMountable[] {
    document.body.innerHTML = "";
    return Array(count)
        .fill(0)
        .map((idx) => {
            const target = document.createElement("div");
            target.id = `target-${idx}`;
            document.body.appendChild(target);
            return {
                target,
                component: <HelloWorld id={`world-${idx}`} />,
            };
        });
}
