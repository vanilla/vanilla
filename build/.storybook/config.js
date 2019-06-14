/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// @ts-check

import { configure, addDecorator } from "@storybook/react";
import {checkA11y, withA11Y} from "@storybook/addon-a11y";
import { withKnobs } from "@storybook/addon-knobs";
import { withViewport } from "@storybook/addon-viewport";
import { withNotes } from "@storybook/addon-notes";
import { withConsole } from "@storybook/addon-console";
import { withActions } from "@storybook/addon-actions";

/**
 * Utility for importing everything from a wepback require.context
 * https://webpack.js.org/guides/dependency-management/#context-module-api
 */
function importAll(r) {
    r.keys().forEach(r);
}

require("../../library/src/scripts/storybookConfig");

function loadStories() {
    const storyFiles = require.context(
        "../..",
        true,
        /^(?!.*(?:\/node_modules\/|\/vendor\/$)).*\.story\.tsx?$/);
    importAll(storyFiles);
}

addDecorator(checkA11y);
addDecorator(withKnobs);
addDecorator(withView);
// addDecorator(withViewport);
// addDecorator(withA11Y);
// addDecorator(withActions);
// addDecorator(withConsole);
// addDecorator(withNotes);

configure(loadStories, module);
