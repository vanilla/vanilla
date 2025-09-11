/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LayoutWidget } from "@library/layout/LayoutWidget";
import { SiteTotals } from "@library/siteTotals/SiteTotals";
import { siteTotalsClasses } from "@library/siteTotals/SiteTotals.classes";
import { ISiteTotalsOptions, SiteTotalsLabelType } from "@library/siteTotals/SiteTotals.variables";
import { useFragmentImpl } from "@library/utility/FragmentImplContext";
import type SiteTotalsFragmentInjectable from "@vanilla/injectables/SiteTotalsFragment";
import { DeepPartial } from "redux";

interface IProps extends SiteTotalsFragmentInjectable.Props {
    labelType: SiteTotalsLabelType;
}

export function SiteTotalsWidget(props: IProps) {
    const { containerOptions, totals, labelType, formatNumbers } = props;
    const CustomImpl = useFragmentImpl<SiteTotalsFragmentInjectable.Props>("SiteTotalsFragment");
    const options: DeepPartial<ISiteTotalsOptions> = {
        ...containerOptions,
        formatNumbers,
    };

    return CustomImpl ? (
        <CustomImpl totals={totals} containerOptions={containerOptions} formatNumbers={formatNumbers} />
    ) : (
        <LayoutWidget interWidgetSpacing="none">
            <SiteTotals options={options} totals={totals} labelType={labelType} />
        </LayoutWidget>
    );
}

export default SiteTotalsWidget;
