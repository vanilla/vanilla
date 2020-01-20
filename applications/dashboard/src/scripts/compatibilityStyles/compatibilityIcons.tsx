/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReactDOM from "react-dom";
import React from "react";
import { DropDownMenuIcon } from "@vanilla/library/src/scripts/icons/common";
import { cssRule } from "typestyle";
import { important } from "csx";

export function applyCompatibilityIcons() {
    cssRule(".Arrow.SpFlyoutHandle::before", { display: important("none") });

    const cogWheels = document.querySelectorAll(".Arrow.SpFlyoutHandle");
    cogWheels.forEach(wheel => {
        ReactDOM.render(<DropDownMenuIcon />, wheel);
    });
}
