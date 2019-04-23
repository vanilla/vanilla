/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { formatUrl } from "@library/application";
import { NavLinkProps, NavLink } from "react-router-dom";
import { LocationDescriptor, createPath, createLocation } from "history";
import {sanitizeUrl} from "@library/utility";

export const LinkContext = React.createContext("https://changeme.dev.localhost");

interface IProps extends NavLinkProps {
    urlFormatter?: (url: string, withDomain?: boolean) => string;
}

/**
 * Link component that checks it's <LinkContext /> to know if it needs to do a full refresh
 * or a partial refresh of the page.
 *
 * If the passed `to` is a subset of the context then a partial navigation will be completed.
 * If the resulting URL has the same pathname as the current page we will do a full refresh.
 *
 * Eg.
 * Context = https://test.com/root
 * To = https://test.com/root/someUrl/deeper/nested
 * Result = /root/someUrl/deeper/nested (react router navigation)
 *
 * Context = https://test.com/otherRoot
 * To = https://test.com/root/someUrl/deeper/nested
 * Result = https://test.com/root/someUrl/deeper/nested (full refresh)
 */
export default function SmartLink(props: IProps) {
    const { replace, urlFormatter, ...passthru } = props;
    const finalUrlFormatter = urlFormatter ? urlFormatter : formatUrl;
    const stringUrl = typeof props.to === "string" ? props.to : createPath(props.to);
    const href = finalUrlFormatter(stringUrl, true);
    const link = document.createElement("a");
    link.href = href;
    const isCurrentPage = link.pathname === window.location.pathname;

    return (
        <LinkContext.Consumer>
            {contextRoot => {
                if (href.startsWith(contextRoot) && !isCurrentPage) {
                    return (
                        <NavLink
                            {...passthru}
                            to={makeDynamicHref(props.to, href)}
                            activeClassName="isCurrent"
                            tabIndex={props.tabIndex}
                            replace={replace}
                        />
                    );
                } else {
                    return <a {...passthru} href={sanitizeUrl(href)} tabIndex={props.tabIndex} />;
                }
            }}
        </LinkContext.Consumer>
    );
}

/**
 * Create a new LocationDescriptor with a "relative" path.
 *
 * This way we ensure we use react-router's navigation and not a full refresh.
 *
 * @param initial The starting location. We may want to preserve state here if we can.
 * @param newHref The new string url to point to.
 */
function makeDynamicHref(initial: LocationDescriptor, newHref: string): LocationDescriptor {
    // Get the search and pathName
    const link = document.createElement("a");
    link.href = newHref;
    const { search, pathname } = link;

    if (typeof initial === "string") {
        return {
            pathname,
            search,
        };
    } else {
        return {
            ...initial,
            pathname,
            search,
        };
    }
}
