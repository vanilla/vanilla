/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { SiteTotalsWidget } from "@library/siteTotalsWidget/SiteTotalsWidget";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { ISiteTotalApiCount } from "@library/siteTotalsWidget/SiteTotals.variables";

interface IProps extends Omit<React.ComponentProps<typeof SiteTotalsWidget>, "totals"> {
    apiParams: {
        counts: ISiteTotalApiCount[];
        options: object;
        filter?: boolean;
        siteSectionID?: string;
    };
}

export function SiteTotalsWidgetPreview(props: IProps) {
    const { apiParams } = props;

    return <SiteTotalsWidget {...props} totals={LayoutEditorPreviewData.getSiteTotals(apiParams.counts)} />;
}
