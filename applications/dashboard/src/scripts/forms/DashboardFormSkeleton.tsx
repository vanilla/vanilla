/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import React from "react";

const settingClasses = {
    setting: css({
        display: "flex",
        alignItems: "center",
        padding: "16px 0",
        borderBottom: singleBorder(),
        "& div": {
            marginBottom: 2,
        },
        "& > div": {
            width: "50%",
            margin: 0,
        },
    }),
};

/**
 * A skeleton for a side by side label input layout
 */
export function DashboardFormSkeleton() {
    return (
        <div className={settingClasses.setting}>
            <div>
                <LoadingRectangle width={`${Math.random() * (30 - 15) + 15}%`} height={19} />
                <LoadingRectangle width={`${Math.random() * (90 - 50) + 50}%`} height={14} />
                <LoadingRectangle width={`${Math.random() * (50 - 15) + 15}%`} height={14} />
            </div>
            <LoadingRectangle width="72%" height={34} />
        </div>
    );
}
