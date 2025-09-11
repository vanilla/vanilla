import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { RichEmbedElement } from "@library/vanilla-editor/plugins/richEmbedPlugin/elements/RichEmbedElement";
import { createVanillaEditor } from "@library/vanilla-editor/createVanillaEditor";
import { ELEMENT_RICH_EMBED_CARD } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";

export default {
    title: "Embeds/Pieces",
};

export const _RichEmbedElement = () => {
    return (
        <>
            <StoryHeading depth={1}>Loading error element when cannot get embed data</StoryHeading>
            <RichEmbedElement
                element={{
                    type: ELEMENT_RICH_EMBED_CARD,
                    url: "https://test.com",
                    children: [{ text: "" }],
                    dataSourceType: "url",
                    error: { message: "Could not get data from https://test.com resource." },
                }}
                isInline={false}
                editor={createVanillaEditor()}
                attributes={{ "data-slate-node": "element", "data-slate-void": true, ref: undefined }}
            >
                <></>
            </RichEmbedElement>
        </>
    );
};

_RichEmbedElement.story = {
    name: "RichEmbedElement",
};
