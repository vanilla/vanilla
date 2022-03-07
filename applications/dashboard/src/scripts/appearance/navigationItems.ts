/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INavigationTreeItem } from "@library/@types/api/core";

const pseudoState: Array<Partial<INavigationTreeItem>> = [];

export function registerAppearanceNavItem(items: Partial<INavigationTreeItem> | Array<Partial<INavigationTreeItem>>) {
    pseudoState.push(...[items].flat());
}

export function registeredAppearanceNavItems(): Array<Partial<INavigationTreeItem>> {
    return pseudoState;
}
