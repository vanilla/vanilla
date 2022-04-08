/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export interface ILayoutSectionInfo {
    regionNames: string[];
    oneWidgetPerRegion: boolean;
    allowColumnInvert: boolean;
    invertedRegionNames?: string[];
}

export const LayoutSectionInfos: Record<string, ILayoutSectionInfo> = {
    "react.section.3-columns": {
        regionNames: ["leftBottom", "middleBottom", "rightBottom"],
        oneWidgetPerRegion: false,
        allowColumnInvert: false,
    },
    "react.section.2-columns": {
        regionNames: ["mainBottom", "secondaryBottom"],
        oneWidgetPerRegion: false,
        allowColumnInvert: true,
        invertedRegionNames: ["secondaryBottom", "mainBottom"],
    },
    "react.section.1-column": { regionNames: ["children"], oneWidgetPerRegion: false, allowColumnInvert: false },
    "react.section.full-width": { regionNames: ["children"], oneWidgetPerRegion: true, allowColumnInvert: false },
};
