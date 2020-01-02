/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryContextProvider } from "@library/storybook/StoryContext";
import { addDecorator } from "@storybook/react";
import React from "react";

addDecorator(storyFn => <StoryContextProvider>{storyFn()}</StoryContextProvider>);
