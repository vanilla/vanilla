/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { addons } from "@storybook/manager-api";
import vanillaTheme from "./VanillaStorybookTheme";

addons.setConfig({
    // Hide the controls panel by default
    panelPosition: "right",
    rightPanelWidth: 0,
    theme: vanillaTheme,
});
