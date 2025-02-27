/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { PostPageContextProvider } from "@vanilla/addon-vanilla/posts/PostPageContext";
import { PostPagePreviewContext } from "@vanilla/addon-vanilla/posts/PostPagePreviewContext";
import PostTagsAsset from "@vanilla/addon-vanilla/posts/PostTagsAsset";

import React from "react";

export default {
    title: "Widgets/PostTagsAsset",
};

export const Default = () => {
    return (
        <PostPagePreviewContext>
            <PostTagsAsset title={"Find more posts tagged with"} />
        </PostPagePreviewContext>
    );
};
