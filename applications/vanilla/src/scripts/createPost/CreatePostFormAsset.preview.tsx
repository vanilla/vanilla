/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { ICategoryItem } from "@library/categoriesWidget/CategoryItem";
import { Widget } from "@library/layout/Widget";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import CreatePostFormAsset from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset";
import { ComponentProps } from "react";

interface IProps extends ComponentProps<typeof CreatePostFormAsset> {}

export function CreatePostAssetPreview(props: IProps) {
    const previewCategory = LayoutEditorPreviewData.categories(1)[0] as unknown as ICategory;
    return (
        <Widget>
            <CreatePostFormAsset {...props} category={previewCategory} isPreview />
        </Widget>
    );
}

export default CreatePostAssetPreview;
