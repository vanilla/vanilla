/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import { QuickLinksView } from "@library/navigation/QuickLinks.view";
import { findMatchingPath } from "@library/routing/routingUtils";
import { getThemeVariables } from "@library/theming/getThemeVariables";
import React, { useMemo } from "react";

interface IProps {
    title?: string;
    links?: INavigationVariableItem[];
    forcedCounts?: Record<string, number>;
    currentPath?: string;
}

export function QuickLinks(props: IProps) {
    const links = !!props.links && props.links.length > 0 ? props.links : quickLinksVariables().links;

    // we don't have a react-router context here, so we determine the active link (if any) upfront.
    const activePath =
        findMatchingPath(
            links.map(({ url }) => url),
            props.currentPath ?? window.location.pathname,
        ) ?? undefined;

    // Get counts which are dynamically injected into the theme variables.
    const linksWithCounts = useMemo(() => {
        const counts = props.forcedCounts ?? getThemeVariables()?.quickLinks?.counts;
        if (!counts) {
            return links;
        }
        for (const [key, value] of Object.entries(links)) {
            const count = counts[value.id] ?? null;
            if (count !== null) {
                links[key].count = count;
            }
        }
        return links;
    }, [links, props.forcedCounts]);

    return <QuickLinksView title={props.title} links={linksWithCounts} activePath={activePath} />;
}
