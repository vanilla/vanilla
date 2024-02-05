/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@vanilla/i18n";
import { IComment } from "@dashboard/@types/api/comment";
import SmartLink from "@library/routing/links/SmartLink";
import ViewInContextClasses from "./ViewInContext.classes";

interface IProps {
    comment: IComment;
}

export default function ViewInContext(props: IProps) {
    const { comment } = props;

    const classes = ViewInContextClasses();

    return (
        <div className={classes.root}>
            <SmartLink className={classes.link} to={comment.url}>
                {t("View in context")}
            </SmartLink>
        </div>
    );
}
