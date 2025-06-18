/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import TagWidget from "@vanilla/addon-vanilla/tag/TagWidget";
import React from "react";

interface IProps extends Omit<React.ComponentProps<typeof TagWidget>, "onlineUsers"> {}

export function TagWidgetPreview(props: IProps) {
    return (
        <LayoutWidget>
            <TagWidget {...props} tags={LayoutEditorPreviewData.tags()} />
        </LayoutWidget>
    );
}
