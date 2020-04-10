/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReactDOM from "react-dom";
import React from "react";
import { DropDownMenuIcon, BookmarkIcon } from "@vanilla/library/src/scripts/icons/common";
import { cssRule } from "typestyle";
import { important } from "csx";

export function applyCompatibilityIcons() {
    // Cog Wheels
    cssRule(".Arrow.SpFlyoutHandle::before", { display: important("none") });

    const cogWheels = document.querySelectorAll(".Arrow.SpFlyoutHandle");
    cogWheels.forEach(wheel => {
        ReactDOM.render(<DropDownMenuIcon />, wheel);
    });

    // Bookmarks
    cssRule(".Content a.Bookmark::before", {
        display: important("none"),
    });

    const bookMarks = document.querySelectorAll(".Bookmark");
    bookMarks.forEach(bookmark => {
        ReactDOM.render(<BookmarkIcon />, bookmark);
    });
}
