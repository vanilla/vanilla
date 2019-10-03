/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { NavLink, NavLinkProps } from "react-router-dom";
import { makeLocationDescriptorObject, LinkContext } from "@library/routing/links/LinkContextProvider";
import { sanitizeUrl } from "@vanilla/utils";
import { LocationDescriptor } from "history";

interface IProps extends NavLinkProps {
    tabIndex?: number;
    to: LocationDescriptor;
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
    const { replace, ...passthru } = props;

    return (
        <LinkContext.Consumer>
            {context => {
                const href = context.makeHref(props.to);
                if (context.isDynamicNavigation(href)) {
                    return (
                        <NavLink
                            {...passthru}
                            to={makeLocationDescriptorObject(props.to, href)}
                            activeClassName="isCurrent"
                            tabIndex={props.tabIndex ? props.tabIndex : 0}
                            replace={replace}
                        />
                    );
                } else {
                    return <a {...passthru} href={sanitizeUrl(href)} tabIndex={props.tabIndex ? props.tabIndex : 0} />;
                }
            }}
        </LinkContext.Consumer>
    );
}
