/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IDiscussion } from "@dashboard/@types/api/discussion";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/comments/CommentThread.hooks";
import { CommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import type { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { createContext, useContext } from "react";

interface ICommon {
    discussion: IDiscussion;
    discussionApiParams: DiscussionsApi.GetParams;
}

type IContext = ICommon & {
    invalidateDiscussion: () => void;
};

type IProps = ICommon & {
    initialPage: number;
    children: React.ReactNode;
};

export const PostPageContext = createContext<IContext>({
    discussion: {} as any,
    discussionApiParams: {},
    invalidateDiscussion: () => {},
});

export function PostPageContextProvider(props: IProps) {
    const discussionQuery = useDiscussionQuery(
        props.discussion.discussionID,
        props.discussionApiParams,
        props.discussion,
    );
    const discussion = discussionQuery.query.data ?? props.discussion;

    return (
        <PostPageContext.Provider
            value={{
                discussion: discussionQuery.query.data!,
                discussionApiParams: props.discussionApiParams,
                invalidateDiscussion: discussionQuery.query.refetch,
            }}
        >
            <CommentThreadParentContext
                currentPage={props.initialPage}
                recordType={"discussion"}
                recordID={discussion.discussionID}
                url={discussion.url}
                closed={discussion.closed}
                dateInserted={discussion.dateInserted}
                insertUserID={discussion.insertUserID}
                categoryID={discussion.categoryID}
                permissionsOverrides={discussion.permissions}
            >
                {props.children}
            </CommentThreadParentContext>
        </PostPageContext.Provider>
    );
}

export function usePostPageContext() {
    return useContext(PostPageContext);
}
