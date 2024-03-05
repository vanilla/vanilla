/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { Widget } from "@library/layout/Widget";
import React from "react";
import TabbedCommentListAsset from "@QnA/asset/TabbedCommentListAsset";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { QnAStatus } from "@dashboard/@types/api/comment";

type IProps = Partial<Omit<React.ComponentProps<typeof TabbedCommentListAsset>, "comments" | "discussion">>;

export default function TabbedCommentListAssetPreview(props: IProps) {
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

    const limit = props.apiParams?.limit ?? 30;
    const otherComments = LayoutEditorPreviewData.comments(limit - acceptedAnswers.length - rejectedAnswers.length);
    const allComments = [...acceptedAnswers, ...rejectedAnswers, ...otherComments];

    return (
        <Widget>
            <TabbedCommentListAsset
                discussion={discussion}
                comments={{
                    data: allComments,
                    paging: LayoutEditorPreviewData.paging(limit, allComments.length),
                }}
                apiParams={{ discussionID: discussion.discussionID, limit, page: 1 }}
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
        </Widget>
    );
}
