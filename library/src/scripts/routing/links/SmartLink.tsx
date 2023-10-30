/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { NavLink, NavLinkProps } from "react-router-dom";
import { makeLocationDescriptorObject, useLinkContext } from "@library/routing/links/LinkContextProvider";
import { sanitizeUrl } from "@vanilla/utils";
import { LocationDescriptor } from "history";
import { siteUrl } from "@library/utility/appUtils";

export interface ISmartLinkProps extends NavLinkProps {
    tabIndex?: number;
    to: LocationDescriptor;
    disabled?: boolean;
    className?: string;
    active?: boolean;
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
export default React.forwardRef(function SmartLink(props: ISmartLinkProps, ref: React.Ref<HTMLAnchorElement>) {
    const { replace, active = false, to, ...passthru } = props;
    const context = useLinkContext();

    // Filter out undefined props
    for (const prop in passthru) {
        if (!passthru[prop]) {
            delete passthru[prop];
        }
    }

    let href = context.makeHref(to);

    const tabIndex = context.areLinksDisabled ? -1 : props.tabIndex ?? 0;
    if (context.isDynamicNavigation(href)) {
        return (
            <NavLink
                rel={props.target === "_blank" ? "noopener ugc" : undefined}
                {...passthru}
                innerRef={ref}
                to={makeLocationDescriptorObject(to, href)}
                tabIndex={tabIndex}
                replace={replace}
                data-link-type="modern"
            />
        );
    } else {
        const isForeign = !href.startsWith(siteUrl(""));
        return (
            <a
                aria-current={active ? "page" : false}
                ref={ref}
                href={sanitizeUrl(href)}
                target={isForeign ? "_blank" : undefined}
                rel={isForeign ? "noopener" : props.rel}
                {...passthru}
                tabIndex={tabIndex}
                data-link-type="legacy"
            />
        );
    }
});
