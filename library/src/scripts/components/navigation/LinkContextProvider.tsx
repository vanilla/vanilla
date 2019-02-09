/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { formatUrl } from "@library/application";
import { NavLinkProps, NavLink, RouteComponentProps, withRouter } from "react-router-dom";
import { LocationDescriptor, createPath, createLocation, LocationDescriptorObject } from "history";

export interface IWithLinkContext {
    linkContext: string;
    pushSmartLocation(location: LocationDescriptor);
    isDynamicNavigation(location: LocationDescriptor): boolean;
    makeHref(location: LocationDescriptor): string;
}

export const LinkContext = React.createContext<IWithLinkContext>({
    linkContext: "https://testSite.com",
    pushSmartLocation: () => {
        throw new Error("Be sure to declare the <LinkContextProvider />");
        return {} as any;
    },
    isDynamicNavigation: () => {
        throw new Error("Be sure to declare the <LinkContextProvider />");
        return false;
    },
    makeHref: () => {
        throw new Error("Be sure to declare the <LinkContextProvider />");
    },
});

interface IProps extends RouteComponentProps<any> {
    linkContext: string;
    children: React.ReactNode;
    urlFormatter?: (url: string, withDomain?: boolean) => string;
}

export const LinkContextProvider = withRouter((props: IProps) => {
    const makeHref = (location: LocationDescriptor): string => {
        const { urlFormatter } = props;
        const finalUrlFormatter = urlFormatter ? urlFormatter : formatUrl;
        const stringUrl = typeof location === "string" ? location : createPath(location);
        const href = finalUrlFormatter(stringUrl, true);
        return href;
    };

    const isDynamicNavigation = (href: string): boolean => {
        const link = document.createElement("a");
        link.href = href;
        const isCurrentPage = link.pathname === window.location.pathname;
        return href.startsWith(props.linkContext) && !isCurrentPage;
    };

    const pushSmartLocation = (location: LocationDescriptor) => {
        const href = makeHref(location);
        if (isDynamicNavigation(href)) {
            props.history.push(makeLocationDescriptorObject(location, href));
        } else {
            window.location.href = href;
        }
    };

    return (
        <LinkContext.Provider
            value={{
                linkContext: props.linkContext,
                pushSmartLocation,
                isDynamicNavigation,
                makeHref,
            }}
        >
            {props.children}
        </LinkContext.Provider>
    );
});

/**
 * Create a new LocationDescriptor with a "relative" path.
 *
 * This way we ensure we use react-router's navigation and not a full refresh.
 *
 * @param initial The starting location. We may want to preserve state here if we can.
 * @param newHref The new string url to point to.
 */
export function makeLocationDescriptorObject(initial: LocationDescriptor, newHref: string): LocationDescriptorObject {
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
