/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { userLabelClasses } from "@library/content/UserLabel.classes";
import { t } from "@vanilla/i18n";
import React from "react";

interface IProps {
    user: IUserFragment;
}

export function UserTitle(props: IProps): JSX.Element | null {
    const { user } = props;
    const classes = userLabelClasses();

    const { label, title } = user;
    if (user.banned) {
        return <div className={classes.rankLabel}>{t("Banned")}</div>;
    }
    if (title) {
        /* Title can be input by the user. */
        return <div className={classes.rankLabel}>{title}</div>;
    }
    if (!title && label) {
        /* Labels (from rank label) are sanitized server side. */
        return <div className={classes.rankLabel} dangerouslySetInnerHTML={{ __html: label }} />;
    }
    return null;
}
