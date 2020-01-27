/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@vanilla/library/src/scripts/storybook/StoryContent";
import { StoryHeading } from "@vanilla/library/src/scripts/storybook/StoryHeading";
import { OpenApiEmbedPlaceholder } from "@openapi-embed/embed/OpenApiEmbedPlaceholder";
import { EmbedContainer } from "@vanilla/library/src/scripts/embeddedContent/EmbedContainer";
import { OpenApiForm } from "@openapi-embed/embed/OpenApiForm";
import { OPEN_API_EMBED_TYPE } from "@openapi-embed/embed/OpenApiEmbed";

export default {
    title: "Embeds/OpenApi",
};

export function Embed() {
    return (
        <StoryContent>
            <StoryHeading>OpenAPI placeholder</StoryHeading>
            <EmbedContainer>
                <OpenApiEmbedPlaceholder
                    data={{
                        url: "https://dev.test.com/openapi/v3",
                        name: "Vanilla API Spec",
                        embedType: OPEN_API_EMBED_TYPE,
                    }}
                />
            </EmbedContainer>
        </StoryContent>
    );
}

export function ConfigurationModal() {
    return <OpenApiForm onSave={() => {}} onDismiss={() => {}} data={{}} />;
}
