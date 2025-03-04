/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { Widget } from "@library/layout/Widget";
import React, { useMemo } from "react";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { QnAStatus } from "@dashboard/@types/api/comment";
import { CommentFixture } from "@vanilla/addon-vanilla/comments/__fixtures__/Comment.Fixture";
import toInteger from "lodash-es/toInteger";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import AnswerThreadAsset from "@QnA/asset/AnswerThreadAsset";

type IProps = Omit<React.ComponentProps<typeof AnswerThreadAsset>, "comments">;
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
            cacheTime: 0,
        },
    },
});

export default function AnswerThreadAssetPreview(props: IProps) {
    const { maxDepth, collapseChildDepth } = props.apiParams;
    const discussion = LayoutEditorPreviewData.discussion();

    const acceptedAnswers = LayoutEditorPreviewData.comments(1).map((comment) => ({
        ...comment,
        attributes: {
            answer: {
                status: QnAStatus.ACCEPTED,
            },
        },
    }));

    const rejectedAnswers = LayoutEditorPreviewData.comments(1).map((comment) => ({
        ...comment,
        attributes: {
            answer: {
                status: QnAStatus.REJECTED,
            },
        },
    }));

    const commentsThread = useMemo(() => {
        const commentsThreadFixture = CommentFixture.createMockThreadStructureResponse({
            maxDepth: toInteger(maxDepth ?? 5),
            collapseChildDepth: toInteger(collapseChildDepth ?? 3),
            minCommentsPerDepth: 2,
            includeHoles: true,
            randomizeCommentContent: false,
        });

        return commentsThreadFixture;
    }, [maxDepth, collapseChildDepth]);

    const key = `${maxDepth}-${collapseChildDepth}`;

    const limit = props.apiParams?.limit ?? 30;
    const otherComments = LayoutEditorPreviewData.comments(limit - acceptedAnswers.length - rejectedAnswers.length);
    const allComments = [...acceptedAnswers, ...rejectedAnswers, ...otherComments];

    return (
        <Widget>
            <QueryClientProvider client={queryClient}>
                <AnswerThreadAsset
                    key={key}
                    {...(props as any)}
                    defaultTabID={"all"}
                    discussion={discussion}
                    threadStyle={maxDepth == 1 ? "flat" : "nested"}
                    commentsThread={{
                        data: commentsThread,
                        paging: LayoutEditorPreviewData.paging(props.apiParams?.limit ?? 30),
                    }}
                    comments={{
                        data: allComments,
                        paging: LayoutEditorPreviewData.paging(limit, allComments.length),
                    }}
                    apiParams={{ discussionID: discussion.discussionID, limit, page: 1, expand: key }}
                    acceptedAnswers={{
                        data: acceptedAnswers,
                        paging: LayoutEditorPreviewData.paging(acceptedAnswers.length, acceptedAnswers.length),
                    }}
                    acceptedAnswersApiParams={{
                        discussionID: discussion.discussionID,
                        limit: acceptedAnswers.length,
                        qna: "accepted",
                        page: 1,
                    }}
                    rejectedAnswers={{
                        data: rejectedAnswers,
                        paging: LayoutEditorPreviewData.paging(rejectedAnswers.length, rejectedAnswers.length),
                    }}
                    rejectedAnswersApiParams={{
                        discussionID: discussion.discussionID,
                        limit: rejectedAnswers.length,
                        qna: "rejected",
                        page: 1,
                    }}
                />
            </QueryClientProvider>
        </Widget>
    );
}
