/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { CommentEditor } from "@vanilla/addon-vanilla/thread/CommentEditor";
import { CommentOptionsMenu } from "@vanilla/addon-vanilla/thread/CommentOptionsMenu";
import CommentsApi from "@vanilla/addon-vanilla/thread/CommentsApi";
import { ReportCountMeta } from "@vanilla/addon-vanilla/thread/ReportCountMeta";
import { ThreadItem } from "@vanilla/addon-vanilla/thread/ThreadItem";
import { ThreadItemContextProvider } from "@vanilla/addon-vanilla/thread/ThreadItemContext";
import React, { useState } from "react";
import { DiscussionAttachment } from "./DiscussionAttachmentsAsset";
import { useDiscussionThreadContext } from "./DiscussionThreadContext";

interface IProps {
    comment: IComment;
    discussion: IDiscussion;
    /** called after a successful PATCH or DELETE.*/
    onMutateSuccess?: () => Promise<void>;
    actions?: React.ComponentProps<typeof ThreadItem>["actions"];
    boxOptions?: Partial<IBoxOptions>;
    userPhotoLocation?: "header" | "left";
}

export function CommentThreadItem(props: IProps) {
    const { comment, onMutateSuccess, actions } = props;
    const [isEditing, setIsEditing] = useState(false);
    const { discussion } = useDiscussionThreadContext();

    const editCommentQuery = useQuery({
        enabled: isEditing,
        queryFn: async () => {
            return await CommentsApi.getEdit(comment.commentID);
        },
        queryKey: ["commentEdit", comment.commentID],
    });

    const queryClient = useQueryClient();

    async function invalidateEditCommentQuery() {
        await queryClient.invalidateQueries(["commentEdit", comment.commentID]);
    }

    return (
        <ThreadItemContextProvider
            recordType={"comment"}
            recordID={comment.commentID}
            recordUrl={comment.url}
            timestamp={comment.dateInserted}
            name={comment.name}
            attributes={comment.attributes}
            authorID={comment.insertUserID}
        >
            <ThreadItem
                boxOptions={props.boxOptions ?? {}}
                content={comment.body}
                actions={actions}
                editor={
                    isEditing &&
                    !!editCommentQuery.data && (
                        <CommentEditor
                            commentEdit={editCommentQuery.data}
                            comment={props.comment}
                            onSuccess={async () => {
                                !!onMutateSuccess && (await onMutateSuccess());
                                await invalidateEditCommentQuery();
                                setIsEditing(false);
                            }}
                            onClose={() => {
                                setIsEditing(false);
                            }}
                        />
                    )
                }
                user={comment.insertUser}
                key={comment.commentID}
                userPhotoLocation={props.userPhotoLocation ?? "header"}
                reactions={comment.reactions}
                attachmentsContent={
                    (comment.attachments ?? []).length > 0 ? (
                        <>
                            {comment.attachments?.map((attachment) => (
                                <ReadableIntegrationContextProvider
                                    key={attachment.attachmentID}
                                    attachmentType={attachment.attachmentType}
                                >
                                    <DiscussionAttachment key={attachment.attachmentID} attachment={attachment} />
                                </ReadableIntegrationContextProvider>
                            ))}
                        </>
                    ) : null
                }
                options={
                    <>
                        <ReportCountMeta
                            countReports={comment.countReports}
                            recordID={comment.commentID}
                            recordType="comment"
                        />
                        <CommentOptionsMenu
                            discussion={props.discussion}
                            comment={comment}
                            onCommentEdit={() => {
                                setIsEditing(true);
                            }}
                            onMutateSuccess={onMutateSuccess}
                            isEditLoading={isEditing && editCommentQuery.isLoading}
                        />
                    </>
                }
                suggestionContent={
                    comment.suggestion && {
                        suggestion: {
                            ...comment.suggestion,
                            summary: comment.body,
                        },
                        discussion: discussion!,
                        commentID: comment.commentID,
                        onMutateSuccess,
                    }
                }
            />
        </ThreadItemContextProvider>
    );
}
