/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { bodyClasses, bodyCSS } from "@library/layout/bodyStyles";

/**
 * Creates a drop down menu
 */
export default class Backgrounds extends React.Component {
    public render() {
        bodyCSS(); // set styles on body tag

        // Make a backwards compatible body background (absolute positioned).
        const classes = bodyClasses();
        return <div className={classes.root} />;
    }
}

export function fullBackgroundCompat() {
    bodyCSS(); // set styles on body tag

    // Make a backwards compatible body background (absolute positioned).
    const classes = bodyClasses();
    const fullBodyBackground = document.createElement("div");
    fullBodyBackground.classList.add(classes.root);
    const frameBody = document.querySelector(".Frame-body");
    if (frameBody) {
        frameBody.prepend(fullBodyBackground);
    }
}
