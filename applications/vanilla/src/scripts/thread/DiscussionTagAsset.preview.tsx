/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import DiscussionTagAsset from "@vanilla/addon-vanilla/thread/DiscussionTagAsset";

interface IProps
    extends Omit<React.ComponentProps<typeof DiscussionTagAsset>, "commentsPreload" | "categoryID" | "discussion"> {}

export function DiscussionTagAssetPreview(_props: IProps) {
    const props = {
        ..._props,
        tags: LayoutEditorPreviewData.tags(),
    };

    return <DiscussionTagAsset {...props} />;
}
