/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NavLink, NavLinkProps } from "react-router-dom";
import { makeLocationDescriptorObject, useLinkContext } from "@library/routing/links/LinkContextProvider";

import { LocationDescriptor } from "history";
import React from "react";
import { cx } from "@emotion/css";
import { metasClasses } from "@library/metas/Metas.styles";
import { sanitizeUrl } from "@vanilla/utils";
import { siteUrl } from "@library/utility/appUtils";

// export interface ISmartLinkProps extends NavLinkProps {
export interface ISmartLinkProps extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
    tabIndex?: number;
    to: LocationDescriptor;
    disabled?: boolean;
    className?: string;
    active?: boolean;
    asMeta?: boolean;
    /** Force the link to open in a new tab */
    openInNewTab?: boolean;
    /** Pulling these in from react router so that we can compile fragment type definitions */
    replace?: boolean | undefined;
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
    const { replace, asMeta, active = false, to, openInNewTab = false, ...passthru } = props;
    const context = useLinkContext();

    // Filter out undefined props
    for (const prop in passthru) {
        if (!passthru[prop]) {
            delete passthru[prop];
        }
    }

    const classesMeta = metasClasses.useAsHook();
    const className = cx(asMeta && classesMeta.metaLink, props.className);

    let href = context.makeHref(to);

    const tabIndex = context.areLinksDisabled ? -1 : props.tabIndex ?? 0;
    const shouldOpenInNewTab = openInNewTab || props.target === "_blank";

    if (context.isDynamicNavigation(href)) {
        return (
            <NavLink
                rel={shouldOpenInNewTab ? "noopener ugc" : undefined}
                target={shouldOpenInNewTab ? "_blank" : props.target}
                {...passthru}
                innerRef={ref}
                to={makeLocationDescriptorObject(to, href)}
                tabIndex={tabIndex}
                replace={replace}
                data-link-type="modern"
                className={className}
            />
        );
    } else {
        const isForeign = !href.startsWith(siteUrl(""));
        const targetBlank = shouldOpenInNewTab || isForeign;
        return (
            <a
                aria-current={active ? "page" : false}
                ref={ref}
                href={sanitizeUrl(href)}
                target={targetBlank ? "_blank" : props.target}
                rel={targetBlank ? "noopener" : props.rel}
                {...(passthru as any)}
                tabIndex={tabIndex}
                data-link-type="legacy"
                className={className}
            />
        );
    }
});
