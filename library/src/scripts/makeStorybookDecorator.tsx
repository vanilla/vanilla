/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContextProvider } from "@library/storybook/StoryContext";
import * as i18n from "@vanilla/i18n";

export function makeStorybookDecorator() {
    i18n.loadLocales([]);

    return function StorybookDecorator(storyFn) {
        return <StoryContextProvider>{storyFn()}</StoryContextProvider>;
    };
}
