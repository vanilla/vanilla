/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import type { ISiteTotalApiCount } from "@library/siteTotals/SiteTotals.variables";
import { getMeta } from "@library/utility/appUtils";
import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import type SiteTotalsFragment from "@library/widget-fragments/SiteTotalsFragment.injectable";
import { uuidv4 } from "@vanilla/utils";

const metaOptions: Record<string, string> = getMeta("siteTotals.availableOptions");

const allCounts: ISiteTotalApiCount[] = [];

for (const [recordType, label] of Object.entries(metaOptions)) {
    allCounts.push({
        recordType,
        label,
    });
}

const allTotals = LayoutEditorPreviewData.getSiteTotals(allCounts);

const filteredTotals = LayoutEditorPreviewData.getSiteTotals([
    {
        label: "Posts",
        recordType: "discussion",
    },
    {
        label: "Articles",
        recordType: "article",
    },
    {
        label: "Answered Questions",
        recordType: "accepted",
    },
]);

const previewData: Array<IFragmentPreviewData<SiteTotalsFragment.Props>> = [
    {
        previewDataUUID: uuidv4(),
        name: "All Counts & Labels",
        description: "A site totals fragment with all of the counts with default labels.",
        data: {
            totals: LayoutEditorPreviewData.getSiteTotals(allTotals),
            formatNumbers: true,
            containerOptions: {
                alignment: "center",
            },
        },
    },
    {
        previewDataUUID: uuidv4(),
        name: "Specific Counts & Labels",
        description: "Community managers may limit which totals get displayed and customize their labels.",
        data: {
            totals: LayoutEditorPreviewData.getSiteTotals(filteredTotals),
            formatNumbers: true,
        },
    },
    {
        previewDataUUID: uuidv4(),
        name: "Visual Options",
        description: "Community managers may limit which totals get displayed and customize their labels.",
        data: {
            totals: LayoutEditorPreviewData.getSiteTotals(filteredTotals),
            formatNumbers: false,
            containerOptions: {
                alignment: "flex-start",
                background: {
                    color: "#f0f0f0",
                },
            },
        },
    },
];

export default previewData;
