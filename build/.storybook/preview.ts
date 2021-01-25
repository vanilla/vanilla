/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { INITIAL_VIEWPORTS } from "@storybook/addon-viewport";
import { makeStorybookDecorator } from "@library/makeStorybookDecorator";
import { styleUnit } from "@library/styles/styleUnit";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { _mountComponents, addComponent } from "@library/utility/componentRegistry";
import { HomeWidget } from "@library/homeWidget/HomeWidget";

const panelLayoutBreakPoints = layoutVariables().panelLayoutBreakPoints;

const customViewports = {
    panelLayout_withBleed: {
        name: "Panel Layout - Full",
        styles: {
            width: styleUnit(panelLayoutBreakPoints.noBleed + 100), // 100 is arbitrary. We just want more than being right up to the minimum margin
            height: "1000px",
        },
    },
    panelLayout_noBleed: {
        name: "Panel Layout - Minimum Margin",
        styles: {
            width: styleUnit(panelLayoutBreakPoints.noBleed),
            height: "1000px",
        },
    },

    panelLayout_twoColumns: {
        name: "Panel Layout - Two Columns",
        styles: {
            width: styleUnit(panelLayoutBreakPoints.twoColumn),
            height: "1000px",
        },
    },
    panelLayout_oneColumn: {
        name: "Panel Layout - One Columns",
        styles: {
            width: styleUnit(panelLayoutBreakPoints.oneColumn),
            height: "1000px",
        },
    },
    panelLayout_xs: {
        name: "Panel Layout - Extra Small",
        styles: {
            width: styleUnit(panelLayoutBreakPoints.xs),
            height: "1000px",
        },
    },
};

export const parameters = {
    chromatic: {
        delay: 2000, // Add a slight delay to ensure everything has rendered properly.
        diffThreshold: 0.7, // Default is 0.67. Lower numbers are more accurate.
        // Set to prevent diffs like this https://www.chromaticqa.com/snapshot?appId=5d5eba16c782b600204ba187&id=5d8cef8dbc622e00202a6edd
        // From triggering
    },
    viewport: {
        viewports: {
            ...customViewports,
            ...INITIAL_VIEWPORTS,
        },
    },
    layout: "fullscreen",
    options: {
        storySort: (a, b) => (a[1].kind === b[1].kind ? 0 : a[1].id.localeCompare(b[1].id, { numeric: true })),
    },
    showRoots: true,
};

export const decorators = [makeStorybookDecorator()];

