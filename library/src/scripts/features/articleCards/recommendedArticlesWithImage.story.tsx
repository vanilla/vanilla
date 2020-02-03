/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState } from "react";
import Container from "@library/layout/components/Container";
import { dummyRecommendedArticlesWithImage } from "./recommendedArticle.storyData";
import Tiles, { TileAlignment } from "../tiles/Tiles";
import ArticleCards from "./AricleCards";

const formsStory = storiesOf("Home Page", module);
formsStory.add(
    "With Image",
    () =>
        (() => {
            return (
                <Container>
                    <StoryHeading depth={1}>Recommended Articles</StoryHeading>
                    <StoryHeading>3 columns </StoryHeading>
                    <ArticleCards
                        columns={3}
                        alignment={TileAlignment.LEFT}
                        items={dummyRecommendedArticlesWithImage.items}
                        title={"Recommended Articles"}
                        emptyMessage={"No Articles found"}
                    />

                    <StoryHeading>2 columns </StoryHeading>
                    <ArticleCards
                        columns={2}
                        alignment={TileAlignment.LEFT}
                        items={dummyRecommendedArticlesWithImage.items}
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
