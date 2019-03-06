/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { configure, addDecorator } from '@storybook/react';
import { checkA11y } from '@storybook/addon-a11y';
import { withKnobs } from "@storybook/addon-knobs";


/**
 * Utility for importing everything from a wepback require.context
 * https://webpack.js.org/guides/dependency-management/#context-module-api
 */
function importAll(r) {
    console.log(r.keys());
    r.keys().forEach(r);
}

function loadStories() {
    const storyFiles = require.context("../..", true, /^(?!.*(?:\/node_modules\/|\/vendor\/$)).*\.story\.tsx?$/);
    importAll(storyFiles);
}

addDecorator(checkA11y);
addDecorator(withKnobs);
configure(loadStories, module);
