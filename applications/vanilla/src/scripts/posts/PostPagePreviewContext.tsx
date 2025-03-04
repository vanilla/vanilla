/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { PostPageContext } from "@vanilla/addon-vanilla/posts/PostPageContext";

export function PostPagePreviewContext(props: { children?: React.ReactNode }) {
    return (
        <PostPageContext.Provider
            value={{
                discussion: { ...LayoutEditorPreviewData.discussion(), tags: LayoutEditorPreviewData.tags() },
                discussionApiParams: {},
                invalidateDiscussion: () => {},
            }}
        >
            {props.children}
        </PostPageContext.Provider>
    );
}
