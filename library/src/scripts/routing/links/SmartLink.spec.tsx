/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";
import SmartLink from "@library/routing/links/SmartLink";
import { render, screen } from "@testing-library/react";
import { LocationDescriptor } from "history";
import { MemoryRouter, Route } from "react-router";

const DOMAIN = "https://mysite.com";
const SUBPATH = "/test";
const CONTEXT_BASE = DOMAIN + SUBPATH;

function renderLocation(loc: LocationDescriptor, formatter: any) {
    render(
        <MemoryRouter>
            <LinkContextProvider linkContexts={[CONTEXT_BASE]} urlFormatter={formatter}>
                <Route>
                    <SmartLink to={loc} />
                </Route>
            </LinkContextProvider>
        </MemoryRouter>,
    );

    return screen.getByRole("link");
}

describe("SmartLink", () => {
    const urlFormatter = (url: string, withDomain: boolean) => url;
    const FORCED_RESULT = "https://directFormatterResult.com";
    const forcedFormatter = (input, withDomain) => FORCED_RESULT;

    it("renders absolute URL string within link contexts as a relative url link", () => {
        const link = renderLocation(`${CONTEXT_BASE}/internal`, urlFormatter);
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute("href", `${SUBPATH}/internal`);
        expect(link).not.toHaveAttribute("target", "_blank");
    });

    it("renders absolute URL string within domain, but not within link contexts, as absolute url link that opens in new window/tab", () => {
        const url = `${DOMAIN}/subcommunity`;
        const link = renderLocation(url, urlFormatter);
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute("href", url);
        expect(link).toHaveAttribute("target", "_blank");
    });

    it("renders external URL string as a link that opens in new window/tab", () => {
        const url = "https://test.com";
        const link = renderLocation(url, urlFormatter);
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute("href", url);
        expect(link).toHaveAttribute("target", "_blank");
    });

    it("renders location object within link context as a relative url link that does not open in new window/tab", () => {
        const locationObj = {
            pathname: `${CONTEXT_BASE}/somePath`,
            search: "?someSearch=true",
        };
        const link = renderLocation(locationObj, urlFormatter);
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute("href", `${SUBPATH}/somePath?someSearch=true`);
        expect(link).not.toHaveAttribute("target", "_blank");
    });

    it("renders location object within domain, but not within link contexts, as relative url link that opens in new window/tab", () => {
        const locationObj = {
            pathname: "/somePathName",
            search: "?someSearch=true",
        };
        const link = renderLocation(locationObj, urlFormatter);
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute("href", "/somePathName?someSearch=true");
        expect(link).toHaveAttribute("target", "_blank");
    });

    it("uses the urlFormatter passed to parse url string, even if they are not directly in the subpath", () => {
        const link = renderLocation(`${CONTEXT_BASE}/somePath`, forcedFormatter);
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute("href", FORCED_RESULT);
        expect(link).toHaveAttribute("target", "_blank");
    });

    it("uses the urlFormatter passed to parse location object, even if they are not directly in subpath", () => {
        const locationObj = {
            pathname: `${CONTEXT_BASE}/somePath`,
            search: "?someSearch=true",
        };
        const link = renderLocation(locationObj, forcedFormatter);
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute("href", FORCED_RESULT);
        expect(link).toHaveAttribute("target", "_blank");
    });
});
