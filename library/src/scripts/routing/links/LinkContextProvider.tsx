/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { formatUrl } from "@library/utility/appUtils";
import { createPath, LocationDescriptor, LocationDescriptorObject } from "history";
import { RouteComponentProps, withRouter } from "react-router";
import { useContentTranslator } from "@vanilla/i18n";

export interface IWithLinkContext {
    linkContext: string;
    pushSmartLocation(location: LocationDescriptor);
    isDynamicNavigation(location: LocationDescriptor): boolean;
    makeHref(location: LocationDescriptor): string;
}

const defaultMakeHref = (location: LocationDescriptor) => {
    const stringUrl = typeof location === "string" ? location : createPath(location);
    return formatUrl(stringUrl, true);
};
export const LinkContext = React.createContext<IWithLinkContext>({
    linkContext: formatUrl("/"),
    pushSmartLocation: location => {
        const href = defaultMakeHref(location);
        window.location.href = href;
    },
    isDynamicNavigation: () => {
        return false;
    },
    makeHref: defaultMakeHref,
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

    /**
     * Determine if the URL is one that we are able to navigate to dynamically.
     *
     * This should be true if we are nested inside of or are the linkContext.
     * The current URL is excluded so that a click on your own page does something the user can see.
     *
     * @param href The URL to check.
     */
    const isDynamicNavigation = (href: string): boolean => {
        const link = new URL(href, window.location.href);
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

export function useLinkContext() {
    return useContext(LinkContext);
}

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
    const link = new URL(newHref, window.location.href);
    const { search, pathname } = link;

    const appRelativeLink = pathname.replace(formatUrl("/"), "/");

    if (typeof initial === "string") {
        return {
            pathname: appRelativeLink,
            search,
        };
    } else {
        return {
            ...initial,
            pathname: appRelativeLink,
            search,
        };
    }
}
