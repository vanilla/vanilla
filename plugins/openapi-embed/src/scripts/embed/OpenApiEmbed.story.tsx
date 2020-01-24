/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@vanilla/library/src/scripts/storybook/StoryContent";
import { StoryHeading } from "@vanilla/library/src/scripts/storybook/StoryHeading";
import { EmbedContent } from "@vanilla/library/src/scripts/embeddedContent/EmbedContent";
import { OpenApiEmbedPlaceholder } from "@openapi-embed/embed/OpenApiEmbedPlaceholder";
import { EmbedContainer } from "@vanilla/library/src/scripts/embeddedContent/EmbedContainer";
import { OpenApiModal } from "@openapi-embed/embed/OpenApiModal";

export default {
    title: "Embeds/OpenApi",
};

export function Embed() {
    return (
        <StoryContent>
            <StoryHeading>OpenAPI placeholder</StoryHeading>
            <EmbedContainer>
                <OpenApiEmbedPlaceholder embedUrl="https://dev.test.com/openapi/v3" name="Test OpenApi Specification" />
            </EmbedContainer>
        </StoryContent>
    );
}

export function ConfigurationModal() {
    return <OpenApiModal onSave={() => {}} onDismiss={() => {}} />;
}
