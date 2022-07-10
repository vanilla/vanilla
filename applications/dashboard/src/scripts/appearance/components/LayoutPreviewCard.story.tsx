/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryContent } from "@library/storybook/StoryContent";
import LayoutPreviewCardComponent from "@dashboard/appearance/components/LayoutPreviewCard";
import ModernLayout from "@dashboard/appearance/previews/ModernLayout";
import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";

export default {
    title: "Theme UI",
};

export function LayoutPreviewCard() {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Layout Preview Card</StoryHeading>
            <LayoutPreviewCardComponent
                previewImage={<ModernLayout className={css(Mixins.absolute.fullSizeOfParent())} />}
                editUrl="#"
                onApply={() => {}}
            />
            <br />
            <StoryHeading depth={1}>Layout Preview Card -- Applied</StoryHeading>
            <LayoutPreviewCardComponent
                previewImage={<ModernLayout className={css(Mixins.absolute.fullSizeOfParent())} />}
                editUrl="#"
                active
            />
        </StoryContent>
    );
}
