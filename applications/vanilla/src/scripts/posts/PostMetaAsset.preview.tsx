/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import PostMetaAsset from "@vanilla/addon-vanilla/posts/PostMetaAsset";

interface IProps extends Omit<React.ComponentProps<typeof PostMetaAsset>, "postFields"> {}

export function PostMetaAssetPreview(_props: IProps) {
    const props = {
        ..._props,
        postFields: LayoutEditorPreviewData.postFields(),
    };

    return <PostMetaAsset {...props} />;
}
