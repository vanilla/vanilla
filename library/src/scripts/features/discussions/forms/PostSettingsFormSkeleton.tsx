/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { css, cx } from "@emotion/css";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export function FormSkeleton({ numberOfRows }: { numberOfRows: number }) {
    const dashboardClass = dashboardClasses();
    const rowClass = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        borderBottom: singleBorder(),
        padding: "16px 16px",
    });
    const labelClass = css({ display: "flex", minWidth: "50%", flexDirection: "column", gap: 4 });

    return (
        <>
            {[...new Array(numberOfRows ?? 3)].map((_, index) => (
                <div key={index} className={cx(dashboardClass.extendRow, rowClass)}>
                    <div className={labelClass}>
                        <LoadingRectangle width={80} height={16} />
                        <LoadingRectangle width={230} height={12} />
                    </div>
                    <LoadingRectangle width={265} height={30} />
                </div>
            ))}
        </>
    );
}
