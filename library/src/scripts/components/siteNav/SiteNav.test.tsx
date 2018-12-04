/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { MemoryRouter as Router } from "react-router-dom";
import { render } from "enzyme";
import SiteNav from "@library/components/siteNav/SiteNav";
import { expect } from "chai";

describe("<SiteNav />", () => {
    it("Render Site Nav - collapsible", () => {
        const expected =
            '<h2 id="siteNav1-title" class="sr-only">Site Navigation</h2><ul class="siteNav-children hasDepth-0" role="tree" aria-labelledby="siteNav1-title"><li class="siteNavNode hasDepth-1" role="treeitem" aria-expanded="false"><span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link isFirstLevel" tabindex="0" href="/path/to/first-item"><span class="siteNavNode-label">First Item</span></a></div></li><li class="siteNavNode hasDepth-1" role="treeitem" aria-expanded="false"><span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link isFirstLevel" tabindex="0" href="/path/to/second-item"><span class="siteNavNode-label">Second Item</span></a></div></li><li class="siteNavNode hasDepth-1" role="treeitem" aria-expanded="false"><span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link isFirstLevel" tabindex="0" href="/path/to/third-item"><span class="siteNavNode-label">Third Item</span></a></div></li></ul>';
        const location = "/path/to/first-item";
        const children = [
            {
                children: [],
                counter: 0,
                depth: 0,
                location,
                name: "First Item",
                url: "/path/to/first-item",
            },
            {
                children: [],
                counter: 1,
                depth: 0,
                location,
                name: "Second Item",
                url: "/path/to/second-item",
            },
            {
                children: [],
                counter: 2,
                depth: 0,
                location,
                name: "Third Item",
                url: "/path/to/third-item",
            },
        ];
        const rendered = render(
            <Router>
                <SiteNav children={children} collapsible={true} />
            </Router>,
        );
        expect(rendered.html()).equals(expected);
    });

    it("Render Site Nav - not collapsible", () => {
        const expected =
            '<h2 id="siteNav2-title" class="sr-only">Site Navigation</h2><ul class="siteNav-children hasDepth-0" role="tree" aria-labelledby="siteNav2-title"><li class="siteNavNode hasDepth-1" role="treeitem" aria-expanded="false"><span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link isFirstLevel" tabindex="0" href="/path/to/first-item"><span class="siteNavNode-label">First Item</span></a></div></li><li class="siteNavNode hasDepth-1" role="treeitem" aria-expanded="false"><span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link isFirstLevel" tabindex="0" href="/path/to/second-item"><span class="siteNavNode-label">Second Item</span></a></div></li><li class="siteNavNode hasDepth-1" role="treeitem" aria-expanded="false"><span class="siteNavNode-spacer" aria-hidden="true"> </span><div class="siteNavNode-contents"><a class="siteNavNode-link isFirstLevel" tabindex="0" href="/path/to/third-item"><span class="siteNavNode-label">Third Item</span></a></div></li></ul>';
        const location = "/path/to/first-item";
        const children = [
            {
                children: [],
                counter: 0,
                depth: 0,
                location,
                name: "First Item",
                url: "/path/to/first-item",
            },
            {
                children: [],
                counter: 1,
                depth: 0,
                location,
                name: "Second Item",
                url: "/path/to/second-item",
            },
            {
                children: [],
                counter: 2,
                depth: 0,
                location,
                name: "Third Item",
                url: "/path/to/third-item",
            },
        ];
        const rendered = render(
            <Router>
                <SiteNav children={children} collapsible={false} />
            </Router>,
        );
        expect(rendered.html()).equals(expected);
    });
});
