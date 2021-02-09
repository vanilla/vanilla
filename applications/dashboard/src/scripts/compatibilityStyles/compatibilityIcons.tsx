/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";
import { DropDownMenuIcon, DocumentationIcon, BookmarkIcon } from "@vanilla/library/src/scripts/icons/common";
import { cssRule } from "@library/styles/styleShim";
import { important } from "csx";
import { iconClasses } from "@library/icons/iconStyles";

export function applyCompatibilityIcons(scope: HTMLElement | Document | undefined = document) {
    if (scope === undefined) {
        return;
    }
    // Cog Wheels
    cssRule(".Arrow.SpFlyoutHandle::before", { display: important("none") });

    const cogWheels = scope.querySelectorAll(".Arrow.SpFlyoutHandle:not(.compatIcons)");
    cogWheels.forEach((wheel) => {
        wheel.classList.add("compatIcons");
        ReactDOM.render(<DropDownMenuIcon />, wheel);
    });

    const docLinks = scope.querySelectorAll("a.documentationLink");
    docLinks.forEach((doc) => {
        doc.classList.add("compatIcons");
        ReactDOM.render(<DocumentationIcon />, doc);
    });

    // Bookmarks
    cssRule(".Content a.Bookmark::before", {
        display: important("none"),
    });

    const bookmarks = scope.querySelectorAll(".Bookmark:not(.compatIcons)");
    const bookmarkLinkClass = iconClasses().bookmark();
    bookmarks.forEach((bookmark) => {
        bookmark.classList.add(bookmarkLinkClass);
        bookmark.classList.add("compatIcons");
        ReactDOM.render(<BookmarkIcon />, bookmark);
    });
}
