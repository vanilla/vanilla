/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ILayoutDetails, LayoutRecordType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Translate from "@library/content/Translate";
import { MetaItem, MetaLink, Metas } from "@library/metas/Metas";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import React from "react";
import { sprintf } from "sprintf-js";

interface IProps {
    layout: ILayoutDetails;
    mode: "meta" | "tooltipContents";
}

/**
 * Component for display the locations that a layout is applied to.
 */
export function AppliedLayoutViewLocationDetails(props: IProps) {
    const { layout, mode } = props;
    const appliedLayoutRecordType = layout.layoutViews[0]?.recordType ?? null;

    if (layout.layoutViews.length === 0 || appliedLayoutRecordType === null) {
        // Early return for when the layout isn't actually applied.
        return <></>;
    }

    // Get together a list of records the layout is applied to.
    const namedLayoutViews = layout.layoutViews.filter(
        (layoutView) =>
            ![LayoutRecordType.GLOBAL, LayoutRecordType.ROOT].includes(layoutView.recordType) &&
            layoutView.record !== undefined,
    );

    const names = namedLayoutViews.length > 0 && (
        <Metas>
            {namedLayoutViews.map((layoutView, index) => {
                return (
                    <React.Fragment key={index}>
                        <MetaLink to={layoutView.record.url}>{layoutView.record.name}</MetaLink>
                        {index < namedLayoutViews.length - 1 && <span>•</span>}
                    </React.Fragment>
                );
            })}
        </Metas>
    );

    // Figure a top level label for the applied locations.
    let label: React.ReactNode = null;
    if ([LayoutRecordType.GLOBAL, LayoutRecordType.ROOT].includes(appliedLayoutRecordType)) {
        label = t("Applied as default");
    } else {
        const count = layout.layoutViews.length;
        const labelString = (() => {
            switch (appliedLayoutRecordType) {
                case LayoutRecordType.CATEGORY:
                    return count > 1 ? t("%d Categories") : t("%d Category");
                case LayoutRecordType.SUBCOMMUNITY:
                    return count > 1 ? t("%d Subcommunities") : t("%d Subcommunity");
                case LayoutRecordType.KNOWLEDGE_BASE:
                    return count > 1 ? t("%d Knowledge Bases") : t("%d Knowledge Base");
                default:
                    return t("Unknown");
            }
        })();
        label = <Translate source="Applied on <0/>" c0={<strong>{sprintf(labelString, count)}</strong>} />;
    }

    // Depending on our mode we are either returning a metaItem with a tooltip on it
    // or a label with title and description to go inside of a toolip.
    switch (mode) {
        case "meta":
            if (!names) {
                return <MetaItem>{label}</MetaItem>;
            }

            return (
                <MetaItem>
                    <ToolTip label={names}>
                        <span>{label}</span>
                    </ToolTip>
                </MetaItem>
            );
        case "tooltipContents":
            return (
                <div>
                    <span>{label}</span>
                    {names}
                </div>
            );
    }
}
