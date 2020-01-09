/*
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReactDOM from "react-dom";
import React from "react";
import "../../../../theme-boilerplate/src/js/index";
import "../../scss/custom.scss";
import { DropDownMenuIcon } from "@vanilla/library/src/scripts/icons/common";

const cogWheels = document.querySelectorAll(".Arrow.SpFlyoutHandle");
cogWheels.forEach(wheel => {
    ReactDOM.render(<DropDownMenuIcon />, wheel);
});
