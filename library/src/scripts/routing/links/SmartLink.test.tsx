/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { LocationDescriptor } from "history";
import SmartLink from "@library/routing/links/SmartLink";
import { MemoryRouter, Route } from "react-router";
import { mount } from "enzyme";
import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";
import { Link } from "react-router-dom";
import { expect } from "chai";

const DOMAIN = "https://mysite.com";
const SUBPATH = "/test";
const CONTEXT_BASE = DOMAIN + SUBPATH;

function renderLocation(loc: LocationDescriptor, formatter: any) {
    return mount(
        <div>
            <MemoryRouter>
                <LinkContextProvider linkContext={CONTEXT_BASE} urlFormatter={formatter}>
                    <Route>
                        <SmartLink to={loc} />
                    </Route>
                </LinkContextProvider>
            </MemoryRouter>
        </div>,
    );
}

describe("<SmartLink />", () => {
    const urlFormatter = (url: string, withDomain: boolean) => url;

    const passthroughs: Array<[LocationDescriptor, string]> = [
        ["https://test.com", "https://test.com"],
        ["https://myforum.com/somePath", "https://myforum.com/somePath"],
        ["http://test.com", "http://test.com"],
        [DOMAIN + "/someOther", DOMAIN + "/someOther"],
        [
            {
                pathname: "/somePathName",
                search: "?someSearch=true",
            },
            "/somePathName?someSearch=true",
        ],
        ["//test.com", "//test.com"],
        ["/test/relative/path", "/test/relative/path"],
    ];

    it("renders links on different domains as plain normal browser links.", () => {
        passthroughs.forEach(([loc, expectedHref]) => {
            const result = renderLocation(loc, urlFormatter);
            expect(result.find("a").prop("href"), `Input was ${loc}`).eq(expectedHref);
            expect(result.find(Link).length).eq(0);
        });
    });
    it("uses relative URLs and react links within its context", () => {
        const valid: Array<[LocationDescriptor, string]> = [
            [CONTEXT_BASE + "/somePath", SUBPATH + "/somePath"],
            [
                CONTEXT_BASE + "/someOtherPath?withQuery=true&OtherQuery=false",
                SUBPATH + "/someOtherPath?withQuery=true&OtherQuery=false",
            ],
            [
                {
                    pathname: CONTEXT_BASE + "/nested/deeper",
                    search: "?someQuery=true",
                },
                SUBPATH + "/nested/deeper?someQuery=true",
            ],
        ];

        valid.forEach(([loc, expectedHref]) => {
            const result = renderLocation(loc, urlFormatter);
            expect(result.find("a").prop("href"), `Input was ${loc}`).eq(expectedHref);
            expect(result.find(Link).length).eq(1);
        });
    });

    it("uses the urlFormatter passed to parse urls, even if they are not directly in the subpath", () => {
        const FORCED_RESULT = "https://directFormatterResult.com";
        const forcedFormatter = (input, withDomain) => FORCED_RESULT;

        const data = [CONTEXT_BASE + "/test", SUBPATH + "/testOther"];
        data.forEach(loc => {
            const result = renderLocation(loc, forcedFormatter);
            expect(result.find("a").prop("href"), `Input was ${loc}`).eq(FORCED_RESULT);
        });
    });
});
