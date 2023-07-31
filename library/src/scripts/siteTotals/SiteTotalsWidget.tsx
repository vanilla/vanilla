/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { SiteTotals } from "@library/siteTotals/SiteTotals";
import {
    ISiteTotalsOptions,
    ISiteTotalCount,
    ISiteTotalsContainer,
    SiteTotalsLabelType,
} from "@library/siteTotals/SiteTotals.variables";
import { DeepPartial } from "redux";
import { Widget } from "@library/layout/Widget";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { siteTotalsClasses } from "@library/siteTotals/SiteTotals.classes";

interface IProps {
    containerOptions?: ISiteTotalsContainer;
    totals: ISiteTotalCount[];
    labelType: SiteTotalsLabelType;
    formatNumbers?: boolean;
}

export function SiteTotalsWidget(props: IProps) {
    const { containerOptions, totals, labelType, formatNumbers } = props;
    const options: DeepPartial<ISiteTotalsOptions> = {
        ...containerOptions,
        formatNumbers,
    };
    const classes = siteTotalsClasses(false, options);

    return (
        <Widget className={classes.widget}>
            <SiteTotals options={options} totals={totals} labelType={labelType} />
        </Widget>
    );
}

export default SiteTotalsWidget;
