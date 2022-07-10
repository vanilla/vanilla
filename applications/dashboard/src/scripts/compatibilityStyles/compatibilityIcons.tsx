/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";
import { DocumentationIcon, _BookmarkIcon } from "@library/icons/common";
import { cssRule } from "@library/styles/styleShim";
import { important } from "csx";
import { iconClasses } from "@library/icons/iconStyles";
import { Icon } from "@vanilla/icons";

export function applyCompatibilityIcons(scope: HTMLElement | Document | undefined = document) {
    if (scope === undefined) {
        return;
    }
    // Cog Wheels
    cssRule(".Arrow.SpFlyoutHandle::before", { display: important("none") });

    const cogWheels = scope.querySelectorAll(".Arrow.SpFlyoutHandle:not(.compatIcons)");
    cogWheels.forEach((wheel) => {
        wheel.classList.add("compatIcons");
        ReactDOM.render(<Icon icon="navigation-ellipsis" />, wheel);
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
    const bookmarkLinkClass = iconClasses()._bookmark();
    bookmarks.forEach((bookmark) => {
        bookmark.classList.add(bookmarkLinkClass);
        bookmark.classList.add("compatIcons");
        ReactDOM.render(<_BookmarkIcon />, bookmark);
    });
}
