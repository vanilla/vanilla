/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { mountPortal } from "./mounting";

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
