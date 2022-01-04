/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { embedErrorClasses } from "@library/embeddedContent/components/embedErrorStyles";
import SmartLink from "@library/routing/links/SmartLink";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import classNames from "classnames";
import { iconClasses } from "@library/icons/iconStyles";
import { Icon } from "@vanilla/icons";

interface IProps {
    url: string;
}

export function EmbedRenderError(props: IProps) {
    const warningTitle = t("This embed could not be loaded in your browser.");
    const classes = embedErrorClasses();
    const helpUrl =
        "https://success.vanillaforums.com/kb/articles/13-rich-editor#what-causes-the-warning-icon-while-inserting-a-rich-embed";

    return (
        <div
            className={classNames(EMBED_FOCUS_CLASS, classes.renderErrorRoot, "embedLinkLoader-link")}
            tabIndex={-1}
            title={warningTitle}
        >
            <SmartLink to={props.url} rel="nofollow">
                {props.url}
            </SmartLink>
            <SmartLink className={classes.renderErrorIconLink} to={helpUrl}>
                <Icon className={iconClasses().errorFgColor} icon={"status-warning"} size={"compact"} />
            </SmartLink>
        </div>
    );
}
