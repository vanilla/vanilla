/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { TitleBarMegaMenu } from "@library/headers/TitleBarMegaMenu";
import { slugify } from "@vanilla/utils";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { GlobalPreset } from "@library/styles/globalStyleVars";

export default {
    title: "Headers/Mega Menu",
};

function makeNavItem(name: string, children?: INavigationVariableItem[]): INavigationVariableItem {
    return {
        name,
        id: slugify(name),
        url: "#",
        children,
    };
}

export function FewItems() {
    return (
        <TitleBarMegaMenu
            expanded={makeNavItem("Top Item", [
                makeNavItem("Number 1", [makeNavItem("Number 1.1"), makeNavItem("Number 1.2")]),
                makeNavItem("Number 2"),
            ])}
        />
    );
}

export function ManyItems() {
    return (
        <TitleBarMegaMenu
            expanded={makeNavItem("Top Item", [
                makeNavItem("Kind of weird, but flex columns don't work well to stretch this", [
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.2"),
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.2"),
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.2"),
                    makeNavItem("Number 1.1"),
                ]),
                makeNavItem("Number 2", [
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.2"),
                    makeNavItem("Number 1.1"),
                ]),
                makeNavItem("Number 2"),
                makeNavItem("Number 2", [
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.2"),
                    makeNavItem("Number 1.1"),
                ]),
                makeNavItem("Number 2", [
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.2"),
                    makeNavItem("Number 1.1"),
                ]),
                makeNavItem("Number 2", [
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.2"),
                    makeNavItem("Number 1.1"),
                ]),
                makeNavItem("Number 2", [
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.2"),
                    makeNavItem("Number 1.1"),
                ]),
                makeNavItem("Number 2", [
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.2"),
                    makeNavItem("Number 1.1"),
                ]),
                makeNavItem("Number 2", [
                    makeNavItem("Number 1.1"),
                    makeNavItem("Number 1.2"),
                    makeNavItem("Number 1.1"),
                ]),
            ])}
        />
    );
}

export function ItemsWithoutChildren() {
    return (
        <TitleBarMegaMenu
            expanded={makeNavItem("Top Item", [
                makeNavItem("Item without children 1"),
                makeNavItem("Item without children 2"),
                makeNavItem("Item without children 3"),
                makeNavItem("Item without children 4"),
            ])}
        />
    );
}

export function ItemsWithAndWithoutChildren() {
    return (
        <TitleBarMegaMenu
            expanded={makeNavItem("Top Item", [
                makeNavItem("Item with children 1", [makeNavItem("Child"), makeNavItem("Child")]),
                makeNavItem("Item without children 1"),
                makeNavItem("Item without children 2"),
                makeNavItem("Item with children 2", [makeNavItem("Child"), makeNavItem("Child")]),
                makeNavItem("Item without children 3"),
            ])}
        />
    );
}

export const ItemsWithAndWithoutChildrenDark = storyWithConfig(
    {
        themeVars: {
            global: {
                options: {
                    preset: GlobalPreset.DARK,
                },
            },
        },
    },
    ItemsWithAndWithoutChildren,
);
