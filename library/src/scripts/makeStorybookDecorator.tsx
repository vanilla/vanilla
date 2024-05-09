/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContextProvider } from "@library/storybook/StoryContext";

export function makeStorybookDecorator() {
    return function StorybookDecorator(storyFn) {
        return <StoryContextProvider>{storyFn()}</StoryContextProvider>;
    };
}
