/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState } from "react";
import Container from "@library/layout/components/Container";
import { dummyRecommendedArticles } from "./recommendedArticle.storyData";
import Tiles, { TileAlignment } from "../tiles/Tiles";
import ArticleCards from "./AricleCards";

const formsStory = storiesOf("Home Page", module);
formsStory.add(
    "Recommended Articles",
    () =>
        (() => {
            return (
                <Container>
                    <StoryContent>
                        <StoryHeading depth={1}>Recommended Articles</StoryHeading>
                        <StoryHeading>As Tiles - 3 columns </StoryHeading>
                    </StoryContent>
                    <ArticleCards
                        columns={3}
                        alignment={TileAlignment.LEFT}
                        items={dummyRecommendedArticles.items}
                        title={"Recommended Articles"}
                        emptyMessage={"No Articles found"}
                    />
                </Container>
            );
        })(),
    {
        chromatic: {
            viewports: [1400],
        },
    },
);
