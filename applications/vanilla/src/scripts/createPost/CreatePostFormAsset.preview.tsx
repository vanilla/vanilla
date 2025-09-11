/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import WidgetPreviewNoPointerEventsWrapper from "@library/layout/WidgetPreviewNoPointerEventsWrapper";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import CreatePostFormAsset from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset";
import { ComponentProps } from "react";

interface IProps extends ComponentProps<typeof CreatePostFormAsset> {}

export function CreatePostAssetPreview(props: IProps) {
    const previewCategory = LayoutEditorPreviewData.categories(1)[0] as unknown as ICategory;
    return (
        <LayoutWidget>
            <WidgetPreviewNoPointerEventsWrapper>
                <CreatePostFormAsset {...props} category={previewCategory} taggingEnabled />
            </WidgetPreviewNoPointerEventsWrapper>
        </LayoutWidget>
    );
}

export default CreatePostAssetPreview;
