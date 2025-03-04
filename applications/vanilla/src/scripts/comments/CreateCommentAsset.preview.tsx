/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { Widget } from "@library/layout/Widget";
import CreateCommentAsset from "@vanilla/addon-vanilla/comments/CreateCommentAsset";

export function CreateCommentAssetPreview(props: React.ComponentProps<typeof CreateCommentAsset>) {
    return (
        <Widget>
            <CreateCommentAsset {...props} isPreview={true} />
        </Widget>
    );
}
