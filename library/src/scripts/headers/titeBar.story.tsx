/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState } from "react";
import { StoryContent } from "@library/storybook/StoryContent";
//import classNames from "classnames";
import TitleBar from "@library/headers/TitleBar";
//import { t } from "@library/utility/appUtils";

const story = storiesOf("TitleBar", module);

story.add("TitleBar", () => {
    return (
        <StoryContent>
            <StoryHeading>TitleBar</StoryHeading>
            <TitleBar useMobileBackButton={false} />
        </StoryContent>
    );
});
