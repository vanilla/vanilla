/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { panelBackgroundClasses } from "@library/layout/panelBackgroundStyles";

/**
 * Implements the article's layout
 */
export class PanelBackground extends React.Component {
    public render() {
        const classes = panelBackgroundClasses();
        return <div className={classes.root}></div>;
    }
}
