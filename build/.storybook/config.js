/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// @ts-check

'use strict';

import { configure, addDecorator, addParameters } from '@storybook/react';
import {checkA11y, withA11y} from '@storybook/addon-a11y';
import { withKnobs } from '@storybook/addon-knobs';
import { INITIAL_VIEWPORTS } from '@storybook/addon-viewport';
import { unit } from '@library/styles/styleHelpers';
import { layoutVariables } from '@library/layout/panelLayoutStyles';

/**
 * Utility for importing everything from a wepback require.context
 * https://webpack.js.org/guides/dependency-management/#context-module-api
 */
function importAll(r) {
    r.keys().forEach(r);
}

require('../../library/src/scripts/storybookConfig');

function loadStories() {
    const storyFiles = require.context(
        '../..',
        true,
        /^(?!.*(?:\/node_modules\/|\/vendor\/$)).*\.story\.tsx?$/);
    importAll(storyFiles);
}



addDecorator(checkA11y);
addDecorator(withA11y);
addDecorator(withKnobs);

const panelLayoutBreakPoints = layoutVariables().panelLayoutBreakPoints;

const customViewports = {
    'panelLayout_withBleed': {
        name: 'Panel Layout - Full',
        styles: {
            width: unit(panelLayoutBreakPoints.noBleed + 100), // 100 is arbitrary. We just want more than being right up to the minimum margin
            height: '1000px',
        },
    },
    'panelLayout_noBleed': {
        name: 'Panel Layout - Minimum Margin',
        styles: {
            width: unit(panelLayoutBreakPoints.noBleed),
            height: '1000px',
        },
    },

    'panelLayout_twoColumns': {
        name: 'Panel Layout - Two Columns',
        styles: {
            width: unit(panelLayoutBreakPoints.twoColumn),
            height: '1000px',
        },
    },
    'panelLayout_oneColumn': {
        name: 'Panel Layout - One Columns',
        styles: {
            width: unit(panelLayoutBreakPoints.oneColumn),
            height: '1000px',
        },
    },
    'panelLayout_xs': {
        name: 'Panel Layout - Extra Small',
        styles: {
            width: unit(panelLayoutBreakPoints.xs),
            height: '1000px',
        },
    },
};

addParameters({
    viewport: {
        viewports: {
            ...customViewports,
            ...INITIAL_VIEWPORTS,
        },
    },
});

// Load Stories
configure(loadStories, module);

