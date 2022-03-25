/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutEditSchema, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { t } from "@vanilla/i18n";

export function createLayoutJsonDraft(draft: Partial<LayoutEditSchema>): Omit<LayoutEditSchema, "layoutID"> {
    const defaults = {
        name: t("My Layout"),
        layoutViewType: "home" as LayoutViewType,
        layout: [],
    };

    return {
        ...draft,
        name: draft.name ?? defaults.name,
        layoutViewType: draft.layoutViewType ?? defaults.layoutViewType,
        layout: draft.layout ?? defaults.layout,
    };
}
