/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import PostTagsAsset from "@vanilla/addon-vanilla/posts/PostTagsAsset";
import { PostPagePreviewContext } from "@vanilla/addon-vanilla/posts/PostPagePreviewContext";

interface IProps extends React.ComponentProps<typeof PostTagsAsset> {}

export function PostTagsAssetPreview(props: IProps) {
    return (
        <PostPagePreviewContext>
            <PostTagsAsset {...props} />
        </PostPagePreviewContext>
    );
}
