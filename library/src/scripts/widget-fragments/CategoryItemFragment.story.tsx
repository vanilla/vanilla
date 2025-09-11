/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Vanilla
 * @license gpl-2.0-only
 */

import CategoryItemFragment from "@library/widget-fragments/CategoryItemFragment.template";
import "@library/widget-fragments/CategoryItemFragment.template.css";
import { Meta, StoryObj } from "@storybook/react";
import * as PreviewData from "./CategoryItemFragment.previewData";
import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import type React from "react";
import CategoryItemFragmentPreview from "@library/widget-fragments/CategoryItemFragment.preview";

const meta: Meta<typeof CategoryItemFragment> = {
    title: "Fragments/CategoryItem",
    component: CategoryItemFragment,
};

export default meta;

type Story = StoryObj<typeof CategoryItemFragment>;

const TemplateFn = (previewData: IFragmentPreviewData<React.ComponentProps<typeof CategoryItemFragment>>): Story => {
    return {
        render: () => {
            return (
                <CategoryItemFragmentPreview previewData={previewData.data}>
                    <CategoryItemFragment {...previewData.data} />
                </CategoryItemFragmentPreview>
            );
        },
        name: previewData.name,
    };
};

const TypeNone = TemplateFn(PreviewData.ImageTypeNoneData);
const TypeIcon = TemplateFn(PreviewData.ImageTypeIconData);
const TypeImage = TemplateFn(PreviewData.ImageTypeImageData);
const TypeBackground = TemplateFn(PreviewData.ImageTypeBackgroundData);

export { TypeNone, TypeIcon, TypeImage, TypeBackground };
