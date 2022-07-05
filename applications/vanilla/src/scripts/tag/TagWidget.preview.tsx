/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { Widget } from "@library/layout/Widget";
import TagWidget from "@vanilla/addon-vanilla/tag/TagWidget";
import React from "react";

interface IProps extends Omit<React.ComponentProps<typeof TagWidget>, "onlineUsers"> {}

export function TagWidgetPreview(props: IProps) {
    return (
        <Widget>
            <TagWidget {...props} tags={LayoutEditorPreviewData.tags()} />
        </Widget>
    );
}
