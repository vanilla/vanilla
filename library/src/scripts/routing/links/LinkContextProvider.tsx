/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useCallback } from "react";
import { formatUrl } from "@library/utility/appUtils";
import { createPath, LocationDescriptor, LocationDescriptorObject } from "history";
import { useHistory, useRouteMatch } from "react-router";
import { isLayoutRoute } from "@library/features/Layout/LayoutPage";

export interface IWithLinkContext {
    linkContexts: string[];
    pushSmartLocation(location: LocationDescriptor);
    isDynamicNavigation(location: LocationDescriptor): boolean;
    makeHref(location: LocationDescriptor): string;
    areLinksDisabled: boolean;
}

const defaultMakeHref = (location: LocationDescriptor) => {
    if (!location) {
        return "";
    }
    const stringUrl = typeof location === "string" ? location : createPath(location);
    return formatUrl(stringUrl, true);
};

export const LINK_CONTEXT_DEFAULTS: IWithLinkContext = {
    linkContexts: [formatUrl("/")],
    pushSmartLocation: (location) => {
        const href = defaultMakeHref(location);
        window.location.href = href;
    },
    isDynamicNavigation: () => {
        return false;
    },
    makeHref: defaultMakeHref,
    areLinksDisabled: false,
};

export const LinkContext = React.createContext<IWithLinkContext>(LINK_CONTEXT_DEFAULTS);

interface IProps {
    linkContexts: string[];
    children: React.ReactNode;
    urlFormatter?: (url: string, withDomain?: boolean) => string;
    useLayoutRouting?: boolean;
}

export const LinkContextProvider = (props: IProps) => {
    const history = useHistory();
    const { urlFormatter, linkContexts, useLayoutRouting } = props;

    const makeHref = useCallback(
        (location: LocationDescriptor): string => {
            const finalUrlFormatter = urlFormatter ? urlFormatter : formatUrl;
            const stringUrl = typeof location === "string" ? location : createPath(location);
            const href = finalUrlFormatter(stringUrl, true);
            return href;
        },
        [urlFormatter],
    );

    /**
     * Determine if the URL is one that we are able to navigate to dynamically.
     *
     * This should be true if we are nested inside of or are the linkContext.
     * The current URL is excluded so that a click on your own page does something the user can see.
     *
     * @param href The URL to check.
     */
    const isDynamicNavigation = useCallback(
        (href: string): boolean => {
            if (useLayoutRouting) {
                return isLayoutRoute(href);
            }
            const link = new URL(href, window.location.href);
            const isCurrentPage = link.pathname === window.location.pathname && link.search === window.location.search;

            let matchesContext = false;
            for (const context of linkContexts) {
                if (href.startsWith(context)) {
                    matchesContext = true;
                    break;
                }
            }

            return matchesContext && !isCurrentPage;
        },
        [linkContexts, useLayoutRouting],
    );

    const pushSmartLocation = useCallback(
        (location: LocationDescriptor) => {
            const href = makeHref(location);
            if (isDynamicNavigation(href)) {
                history.push(makeLocationDescriptorObject(location, href));
            } else {
                window.location.href = href;
            }
        },
        [makeHref, isDynamicNavigation, history],
    );

    return (
        <LinkContext.Provider
            value={{
                linkContexts: props.linkContexts,
                pushSmartLocation,
                isDynamicNavigation,
                makeHref,
                areLinksDisabled: false,
            }}
        >
            {props.children}
        </LinkContext.Provider>
    );
};

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
