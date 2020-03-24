/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReactDOM from "react-dom";
import React from "react";
import { logWarning } from "@vanilla/utils";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { userContentClasses } from "@vanilla/library/src/scripts/content/userContentStyles";
import classNames from "classnames";

interface IProps {
    children: React.ReactNode;
    targetID?: string | null; // Null causes a normal render.
}

const HELP_ID = "fixed-help";

export function DashboardHelpAsset(props: IProps) {
    const { children, targetID = HELP_ID } = props;

    const classes = dashboardClasses();

    const content = (
        <aside className={classNames(classes.helpAsset, userContentClasses().root)} role="note">
            {children}
        </aside>
    );

    if (targetID === null) {
        return content;
    } else {
        const target = document.getElementById(targetID);

        if (!target) {
            logWarning("Attempted to render <DashboardHelpAsset /> with an invalid targetID");
            return null;
        }

        return ReactDOM.createPortal(content, target);
    }
}
