/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { DataList, IDataListNode } from "@library/dataLists/DataList";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { t } from "@vanilla/i18n";
import { useMeasure } from "@vanilla/react-utils";
import { percent } from "csx";
import React, { useRef } from "react";

interface IProps {
    data: IDataListNode[];
}

export function ProfileOverviewWidget(props: IProps) {
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
                    title={t("Overview")}
                    data={props.data}
                    colgroups={isSmallViewport ? [40, 60] : [30, 70]}
                />
            </div>
        </ErrorBoundary>
    );
}
