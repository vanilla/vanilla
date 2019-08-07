/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer, EmbedContainerSize } from "@library/embeddedContent/EmbedContainer";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { QuoteEmbed } from "@library/embeddedContent/QuoteEmbed";
import { IUserFragment } from "@library/@types/api/users";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import { StoryExampleModal } from "@library/embeddedContent/StoryExampleModal";
import { StoryExampleModalConfirm } from "@library/embeddedContent/StoryExampleModalConfirm";
import { StoryContent } from "@library/storybook/StoryContent";

const story = storiesOf("Components", module);

// tslint:disable:jsx-use-translation-function

const dummyDate = "2019-02-10T23:54:14+00:00";

story.add("Modals", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Modal Examples</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact>
                    <StoryExampleModalConfirm />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact>
                    <StoryExampleModal />
                </StoryTileAndTextCompact>
            </StoryTiles>
        </StoryContent>
    );
});
