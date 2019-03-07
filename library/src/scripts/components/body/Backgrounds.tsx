/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { bodyClasses, bodyCSS } from "@library/styles/bodyStyles";

/**
 * Creates a drop down menu
 */
export default class Backgrounds extends React.Component {
    public render() {
        bodyCSS(); // set styles on body tag
        const classes = bodyClasses(); //Sets styles on body tag
        return (
            <>
                <div className={classes.root} />
            </>
        );
    }
}
