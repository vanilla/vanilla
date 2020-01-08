/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { fullBackgroundClasses, bodyCSS } from "@library/layout/bodyStyles";

interface IProps {
    isHomePage?: boolean;
}

/**
 * Creates a drop down menu
 */
export default class Backgrounds extends React.Component<IProps> {
    public render() {
        bodyCSS(); // set styles on body tag

        // Make a backwards compatible body background (absolute positioned).
        const classes = fullBackgroundClasses(!!this.props.isHomePage);
        return <div className={classes.root} />;
    }
}
