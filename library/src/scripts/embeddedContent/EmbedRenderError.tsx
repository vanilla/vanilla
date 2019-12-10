/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { WarningIcon } from "@library/icons/common";
import { t } from "@library/utility/appUtils";
import { embedErrorClasses } from "@library/embeddedContent/embedErrorStyles";
import SmartLink from "@library/routing/links/SmartLink";
import { FOCUS_CLASS } from "@library/embeddedContent/embedService";
import classNames from "classnames";
import { iconClasses } from "@library/icons/iconClasses";

interface IProps {
    url: string;
}

export function EmbedRenderError(props: IProps) {
    const warningTitle = t("This embed could not be loaded in your browser.");
    const classes = embedErrorClasses();
    const helpUrl =
        "https://success.vanillaforums.com/kb/articles/13-rich-editor#what-causes-the-warning-icon-while-inserting-a-rich-embed";

    return (
        <div className={classNames(FOCUS_CLASS, classes.renderErrorRoot, "embedLinkLoader-link")} tabIndex={-1}>
            <SmartLink to={props.url} rel="nofollow">
                {props.url}
            </SmartLink>
            <SmartLink className={classes.renderErrorIconLink} to={helpUrl}>
                <WarningIcon className={iconClasses().errorFgColor} warningMessage={warningTitle} />
            </SmartLink>
        </div>
    );
}
