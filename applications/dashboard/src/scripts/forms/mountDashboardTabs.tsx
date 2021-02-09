/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITabData, Tabs } from "@vanilla/library/src/scripts/sectioning/Tabs";
import { mountReact } from "@vanilla/react-utils";
import React from "react";

export function mountDashboardTabs() {
    const toMount = document.querySelectorAll("[data-tabsReact]");
    toMount.forEach((tabRoot) => {
        if (!(tabRoot instanceof HTMLElement)) {
            return;
        }
        tabRoot.removeAttribute("data-tabsReact");
        const buttons = tabRoot.querySelectorAll("[data-tabButton]");
        const panels = tabRoot.querySelectorAll("[data-tabPanel]");
        const tabs: ITabData[] = [];

        panels.forEach((panel, i) => {
            const button = buttons[i];
            const tab: ITabData = {
                contentNodes: Array.from(panel.childNodes),
                label: button.textContent ?? "Untitled",
            };
            tabs.push(tab);
        });

        mountReact(<Tabs data={tabs} legacyButtons />, tabRoot as HTMLElement, undefined, {
            overwrite: true,
        });
    });
}
