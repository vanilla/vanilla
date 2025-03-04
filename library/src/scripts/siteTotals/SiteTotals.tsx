/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef } from "react";
import { DeepPartial } from "redux";
import {
    siteTotalsVariables,
    ISiteTotalsOptions,
    ISiteTotalCount,
    SiteTotalsLabelType,
} from "@library/siteTotals/SiteTotals.variables";
import { siteTotalsClasses } from "@library/siteTotals/SiteTotals.classes";
import { useMeasure } from "@vanilla/react-utils";
import { twoColumnVariables } from "@library/layout/types/layout.twoColumns";
import { formatNumberText } from "@library/content/NumberFormatted";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";

export interface ISiteTotalsProps {
    options: DeepPartial<ISiteTotalsOptions>;
    totals: ISiteTotalCount[];
    labelType: SiteTotalsLabelType;
}

export function SiteTotals(props: ISiteTotalsProps) {
    const { options, totals, labelType } = props;
    const rootRef = useRef<HTMLDivElement | null>(null);
    const rootMeasure = useMeasure(rootRef);
    const shouldWrap = rootMeasure.width > 0 && rootMeasure.width < twoColumnVariables().panel.paddedWidth;
    const vars = siteTotalsVariables(options);
    const classes = siteTotalsClasses(shouldWrap, options);
    const showIcon = labelType === SiteTotalsLabelType.BOTH || labelType === SiteTotalsLabelType.ICON;
    const showLabel = labelType === SiteTotalsLabelType.BOTH || labelType === SiteTotalsLabelType.TEXT;

    return (
        <div ref={rootRef} className={classes.root} data-testid="site-totals-root">
            {totals &&
                totals.map((c) => (
                    <div
                        key={c.recordType}
                        className={classes.countContainer}
                        aria-label={t(`${formatNumberText({ value: c.count })["fullValue"]} ${c.label}`)}
                    >
                        {showIcon && <Icon icon={c.iconName} className={classes.icon} />}
                        <span className={classes.count}>
                            {c.isCalculating
                                ? "???"
                                : formatNumberText({ value: c.count })[
                                      vars.count.format ? "compactValue" : "fullValue"
                                  ]}
                        </span>
                        {showLabel && <span className={classes.label}>{t(c.label)}</span>}
                    </div>
                ))}
        </div>
    );
}
