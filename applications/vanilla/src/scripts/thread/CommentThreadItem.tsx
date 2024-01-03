/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import DateTime from "@library/content/DateTime";
import { MetaLink } from "@library/metas/Metas";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { CommentEditor } from "@vanilla/addon-vanilla/thread/CommentEditor";
import { CommentOptionsMenu } from "@vanilla/addon-vanilla/thread/CommentOptionsMenu";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { ThreadItem } from "@vanilla/addon-vanilla/thread/ThreadItem";
import React, { useState } from "react";

interface IProps {
    comment: IComment;
    discussion: IDiscussion;
    /** called after a successful PATCH or DELETE.*/
    onMutateSuccess?: () => Promise<void>;
}

export function CommentThreadItem(props: IProps) {
    const { comment, onMutateSuccess } = props;
    const [isEditing, setIsEditing] = useState(false);
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

    const permalink = (
        <MetaLink to={comment.url}>
            <DateTime timestamp={comment.dateInserted} />
        </MetaLink>
    );

    return (
        <ThreadItem
            boxOptions={{}}
            content={comment.body}
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
            contentMeta={permalink}
            key={comment.commentID}
            userPhotoLocation={"header"}
            recordType={"comment"}
            recordID={comment.commentID}
            options={
                <CommentOptionsMenu
                    discussion={props.discussion}
                    comment={comment}
                    onCommentEdit={() => {
                        setIsEditing(true);
                    }}
                    onDeleteSuccess={onMutateSuccess}
                    isEditLoading={isEditing && editCommentQuery.isLoading}
                />
            }
        />
    );
}
