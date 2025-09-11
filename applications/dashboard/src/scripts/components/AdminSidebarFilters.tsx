/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { t } from "@vanilla/i18n";

interface IProps {
    children?: React.ReactNode;
}

export function AdminSidebarFilters(props: IProps) {
    return <div className={classes.root}>{props.children}</div>;
}

const classes = {
    root: css({
        "& > h2, & > h3, & > h4, & > h5": {
            marginTop: 12,
            "&:first-child": {
                marginTop: 0,
            },
        },
    }),
};
