/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { makeStorybookDecorator } from "@library/makeStorybookDecorator";
import { INITIAL_VIEWPORTS } from "@storybook/addon-viewport";
import { loadTranslations } from "@vanilla/i18n";
import "../../resources/fonts/Open Sans/font.css";
import iconSymbolsHtml from "../../resources/views/svg-symbols.html?raw";

loadTranslations({});
const iconDiv = document.createElement("div");
iconDiv.innerHTML = iconSymbolsHtml;
document.body.insertBefore(iconDiv, document.body.firstChild);

export const parameters = {
    chromatic: {
        delay: 2000, // Add a slight delay to ensure everything has rendered properly.
        diffThreshold: 0.7, // Default is 0.67. Lower numbers are more accurate.
        // Set to prevent diffs like this https://www.chromaticqa.com/snapshot?appId=5d5eba16c782b600204ba187&id=5d8cef8dbc622e00202a6edd
        // From triggering
    },
    viewport: {
        viewports: {
            default: {
                viewport: {
                    width: 1380,
                    height: 800,
                },
            },
            ...INITIAL_VIEWPORTS,
        },
    },
    layout: "fullscreen",
    options: {
        storySort: (a, b) => (a.title === b.title ? 0 : a.id.localeCompare(b.id, { numeric: true })),
    },
    showRoots: true,
};

export const decorators = [makeStorybookDecorator()];
