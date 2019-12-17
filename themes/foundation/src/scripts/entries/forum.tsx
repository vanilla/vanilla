/*
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";
import "../../../../theme-boilerplate/src/js/index";
import "../../scss/custom.scss";
import { DropDownMenuIcon } from "@library/icons/common";

document.querySelectorAll(".Arrow.SpFlyoutHandle").forEach(handle => {
    ReactDOM.render(<DropDownMenuIcon />, handle);
});
