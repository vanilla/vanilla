/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { INavigationTreeItem } from "@library/@types/api/core";
import { DynamicComponentTypes, useHamburgerMenuContext } from "@library/contexts/HamburgerMenuContext";
import { varItemToNavTreeItem } from "@library/flyouts/Hamburger";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import { QuickLinksView } from "@library/navigation/QuickLinks.view";
import { findMatchingPath } from "@library/routing/routingUtils";
import { getThemeVariables } from "@library/theming/getThemeVariables";
import { t } from "@vanilla/i18n";
import React, { useEffect, useMemo, useState } from "react";

interface IProps {
    title?: string;
    links?: INavigationVariableItem[];
    forcedCounts?: Record<string, number>;
    currentPath?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    extraHeader?: React.ReactNode;
}

export function QuickLinks(props: IProps) {
    const { addComponent, removeComponentByID, isCompact } = useHamburgerMenuContext();

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

    // Create a nav tree from the quick links
    const linksAsNavTree = useMemo<INavigationTreeItem[]>(() => {
        return [
            {
                name: props.title ?? t("Quick Links"),
                parentID: 0,
                sort: 0,
                recordID: "quickLinks",
                recordType: "quickLinks",
                children: linksWithCounts
                    .map((link) => varItemToNavTreeItem(link, "quickLinks"))
                    .filter((item) => item), // Omit hidden (undefined) links
            },
        ] as INavigationTreeItem[];
    }, [linksWithCounts, props.title]);

    const [menuID, setMenuID] = useState<number | null>(null);

    // Add the component to the context
    useEffect(() => {
        // Only add if we don't already have an ID
        if (!menuID) {
            const id = addComponent({
                type: DynamicComponentTypes.tree,
                tree: linksAsNavTree,
                title: props.title ?? t("Quick Links"),
            });
            setMenuID(id);
        }
    }, [addComponent, linksAsNavTree, props.title]);

    /**
     * The automatic hiding of this widget was discussed
     * and agreed to automatically hide on compact views as an MVP
     * This might be reconsidered after the initial scope is delivered
     */
    return !isCompact ? (
        <QuickLinksView
            title={props.title}
            links={linksWithCounts}
            activePath={activePath}
            containerOptions={props.containerOptions}
            extraHeader={props.extraHeader}
        />
    ) : null;
}

export default QuickLinks;
