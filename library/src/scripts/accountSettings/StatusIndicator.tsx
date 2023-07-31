/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css, cx } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import React, { ReactNode } from "react";

interface IProps {
    className?: string;
    icon: ReactNode;
    statusText?: ReactNode;
}

const statusIndicatorLayout = css({
    display: "flex",
    alignItems: "center",
    gap: globalVariables().spacer.headingItem / 2,
    "& > span": {
        display: "flex",
    },
});

export function StatusIndicator(props: IProps) {
    const { className, icon, statusText } = props;
    return (
        <span className={cx(statusIndicatorLayout, className)}>
            <span>{icon}</span>
            {statusText && <span>{statusText}</span>}
        </span>
    );
}
