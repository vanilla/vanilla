/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { MemoryRouter as Router } from "react-router-dom";
import { render } from "enzyme";
import SiteNavNode from "@library/components/siteNav/SiteNavNode";
import { expect } from "chai";

describe("<SiteNavNode />", () => {
    it("render a single node", () => {
        const expected =
            '<span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link isFirstLevel" tabindex="0" href="/path/to/page"><span class="siteNavNode-label">Hello world.</span></a></div>';
        const rendered = render(
            <Router>
                <SiteNavNode
                    children={[]}
                    counter={0}
                    depth={0}
                    location={"/path/to/page"}
                    name="Hello world."
                    url={"/path/to/page"}
                />
            </Router>,
        );
        expect(rendered.html()).equals(expected);
    });

    it("render a node with children", () => {
        const location = "/path/to/first-child";
        const children = [
            {
                children: [],
                counter: 0,
                depth: 1,
                location,
                name: "First Child",
                url: "/path/to/first-child",
            },
            {
                children: [],
                counter: 1,
                depth: 1,
                location,
                name: "Second Child",
                url: "/path/to/second-child",
            },
            {
                children: [],
                counter: 2,
                depth: 1,
                location,
                name: "Third Child",
                url: "/path/to/third-child",
            },
        ];
        const expected =
            '<span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link hasChildren isFirstLevel" tabindex="0" href="/path/to/parent"><span class="siteNavNode-label">Parent</span></a><ul class="siteNavNode-children hasDepth-1" role="group"><li class="siteNavNode hasDepth-2" role="treeitem" aria-expanded="false"><span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link" tabindex="0" href="/path/to/first-child"><span class="siteNavNode-label">First Child</span></a></div></li><li class="siteNavNode hasDepth-2" role="treeitem" aria-expanded="false"><span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link" tabindex="0" href="/path/to/second-child"><span class="siteNavNode-label">Second Child</span></a></div></li><li class="siteNavNode hasDepth-2" role="treeitem" aria-expanded="false"><span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link" tabindex="0" href="/path/to/third-child"><span class="siteNavNode-label">Third Child</span></a></div></li></ul></div>';
        const rendered = render(
            <Router>
                <SiteNavNode children={children} counter={0} depth={0} location name="Parent" url={"/path/to/parent"} />
            </Router>,
        );
        expect(rendered.html()).equals(expected);
    });
});
