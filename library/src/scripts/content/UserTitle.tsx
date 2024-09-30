/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { userLabelClasses } from "@library/content/UserLabel.classes";
import { t } from "@vanilla/i18n";
import { ReactElement } from "react";

interface IProps {
    user: IUserFragment;
    showOPTag?: boolean;
}

export function UserTitle(props: IProps): JSX.Element | null {
    const { user, showOPTag } = props;
    const classes = userLabelClasses();

    const { label, title } = user ?? {};

    let mainContent: ReactElement | null = null;

    if (user.banned) {
        mainContent = <div className={classes.rankLabel}>{t("Banned")}</div>;
    } else if (title) {
        // Title can be input by the user.
        mainContent = <div className={classes.rankLabel}>{title}</div>;
    } else if (label) {
        // Labels (from rank label) are sanitized server side.
        mainContent = <div className={classes.rankLabel} dangerouslySetInnerHTML={{ __html: label }} />;
    }

    return showOPTag ? (
        <div className={classes.flexWrapper}>
            {mainContent}
            <span className={classes.rankLabel}>{"OP"}</span>
        </div>
    ) : (
        mainContent
    );
}
