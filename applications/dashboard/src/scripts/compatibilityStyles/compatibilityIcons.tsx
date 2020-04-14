/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReactDOM from "react-dom";
import React from "react";
import { DropDownMenuIcon, DocumentationIcon } from "@vanilla/library/src/scripts/icons/common";
import { cssRule } from "typestyle";
import { important } from "csx";

export function applyCompatibilityIcons(scope: HTMLElement | Document | undefined = document) {
    if (scope === undefined) {
        return;
    }
    // Cog Wheels
    cssRule(".Arrow.SpFlyoutHandle::before", { display: important("none") });

    const cogWheels = scope.querySelectorAll(".Arrow.SpFlyoutHandle:not(.compatIcons)");
    cogWheels.forEach(wheel => {
        wheel.classList.add("compatIcons");
        ReactDOM.render(<DropDownMenuIcon />, wheel);
    });

    const docLinks = scope.querySelectorAll("a.documentationLink");
    docLinks.forEach(doc => {
        doc.classList.add("compatIcons");
        ReactDOM.render(<DocumentationIcon />, doc);
    });

    // Bookmarks
}
