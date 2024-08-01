/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";

import { useUserProfileFields } from "@library/aboutMeWidget/AboutMe.hooks";
import { DataList } from "@library/dataLists/DataList";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { t } from "@vanilla/i18n";
import { useMeasure } from "@vanilla/react-utils";
import { RecordID } from "@vanilla/utils";
import { percent } from "csx";
import React, { useRef } from "react";

interface IProps {
    userID: RecordID;
}

/**
 * This widget will display custom user profile fields
 */
export function AboutMeWidget(props: IProps) {
    const { isLoading, profileFields } = useUserProfileFields(props.userID);
    const rootRef = useRef<HTMLDivElement | null>(null);
    const rootMeasure = useMeasure(rootRef);
    const isSmallViewport = rootMeasure.width < 501;

    const tableStyles = css({
        "& table": {
            width: percent(100),
            ...Mixins.margin({
                bottom: globalVariables().spacer.pageComponent,
            }),
        },
    });

    return (
        <ErrorBoundary>
            <div ref={rootRef}>
                <DataList
                    className={tableStyles}
                    title={t("About Me")}
                    isLoading={isLoading}
                    data={profileFields ?? []}
                    colgroups={isSmallViewport ? [40, 60] : [30, 70]}
                />
            </div>
        </ErrorBoundary>
    );
}
