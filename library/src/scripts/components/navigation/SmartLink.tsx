/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { formatUrl } from "@library/application";
import { NavLinkProps, NavLink } from "react-router-dom";
import { LocationDescriptor, createPath } from "history";

export const LinkContext = React.createContext("https://changeme.dev.localhost");

interface IProps extends NavLinkProps {
    urlFormatter?: (url: string, withDomain?: boolean) => string;
}

/**
 * Link component that checks it's <LinkContext /> to know if it needs to do a full refresh
 * or a partial refresh of the page.
 *
 * If the passed `to` is a subset of the context then a partial navigation will be completed.
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
    const { urlFormatter, ...passthru } = props;
    const finalUrlFormatter = urlFormatter ? urlFormatter : formatUrl;
    const stringUrl = typeof props.to === "string" ? props.to : createPath(props.to);
    const href = finalUrlFormatter(stringUrl, true);

    return (
        <LinkContext.Consumer>
            {contextRoot => {
                if (href.startsWith(contextRoot)) {
                    return <NavLink {...passthru} to={makeSmartLocation(props.to, href)} />;
                } else {
                    return <a {...passthru} href={href} />;
                }
            }}
        </LinkContext.Consumer>
    );
}

function makeSmartLocation(initial: LocationDescriptor, newHref: string): LocationDescriptor {
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
