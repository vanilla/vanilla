/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export interface ILayoutSectionInfo {
    regionNames: string[];
    oneWidgetPerRegion: boolean;
}

export const LayoutSectionInfos: Record<string, ILayoutSectionInfo> = {
    "react.section.3-columns": {
        regionNames: ["leftBottom", "middleBottom", "rightBottom"],
        oneWidgetPerRegion: false,
    },
    "react.section.2-columns": { regionNames: ["mainBottom", "rightBottom"], oneWidgetPerRegion: false },
    "react.section.1-column": { regionNames: ["children"], oneWidgetPerRegion: false },
    "react.section.full-width": { regionNames: ["children"], oneWidgetPerRegion: true },
};
