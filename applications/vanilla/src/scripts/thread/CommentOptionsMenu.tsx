/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IApiError } from "@library/@types/api/core";
import { useToast } from "@library/features/toaster/ToastContext";
import { IPermissionOptions, PermissionMode } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useCurrentUser } from "@library/features/users/userHooks";
import DropDown, { DropDownOpenDirection, FlyoutType, IDropDownProps } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import ModalConfirm from "@library/modal/ModalConfirm";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { t } from "@vanilla/i18n";
import { Hoverable } from "@vanilla/react-utils";
import React, { useState } from "react";

interface IProps {
    comment: IComment;
    discussion: IDiscussion;
    onCommentEdit: () => void;
    isEditLoading: boolean;
    isVisible?: IDropDownProps["isVisible"];
}

export function CommentOptionsMenu(props: IProps) {
    const { discussion, comment } = props;
    const items: React.ReactNode[] = [];
    const currentUser = useCurrentUser();
    const { hasPermission } = usePermissionsContext();
    const permissionOptions: IPermissionOptions = {
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
        resourceType: "category",
        resourceID: comment.categoryID,
    };

    const toast = useToast();
    const queryClient = useQueryClient();
    const deleteMutation = useMutation({
        mutationFn: CommentsApi.delete,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["commentsList"] });
            toast.addToast({
                body: t("Comment Deleted"),
                autoDismiss: true,
            });
        },
        onError(error: IApiError) {
            toast.addToast({
                body: error.message,
                dismissible: true,
            });
        },
    });

    const isOwnComment = comment.insertUserID === currentUser?.userID;

    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

    // TODO:
    // - Edit content timeout
    // - Can't edit on closed discussions.
    if (hasPermission("comments.edit", permissionOptions) || (isOwnComment && !discussion.closed)) {
        items.push(
            <Hoverable
                duration={200}
                once
                onHover={() => {
                    // queryClient.fetchQuery({
                    //     queryFn: async () => {
                    //         return await CommentsApi.getEdit(comment.commentID);
                    //     },
                    //     queryKey: ["commentEdit", comment.commentID],
                    // });
                }}
            >
                {(hoverProps) => (
                    <DropDownItemButton isLoading={props.isEditLoading} {...hoverProps} onClick={props.onCommentEdit}>
                        <span>
                            <span>{t("Edit")}</span>
                        </span>
                    </DropDownItemButton>
                )}
            </Hoverable>,
        );
    }

    if (hasPermission("comments.delete", permissionOptions) || isOwnComment) {
        items.push(
            <DropDownItemButton
                onClick={() => {
                    setShowDeleteConfirm(true);
                }}
            >
                {t("Delete")}
                <ModalConfirm
                    title={t("Delete Comment")}
                    isVisible={showDeleteConfirm}
                    onCancel={() => {
                        setShowDeleteConfirm(false);
                    }}
                    isConfirmDisabled={deleteMutation.isLoading}
                    isConfirmLoading={deleteMutation.isLoading}
                    onConfirm={() => {
                        deleteMutation.mutate(comment.commentID);
                    }}
                >
                    {t("Are you sure you want to delete this comment?")}
                </ModalConfirm>
            </DropDownItemButton>,
        );
    }

    if (hasPermission("moderation.manage") && comment.dateUpdated !== null) {
        items.push(
            <DropDownItemLink to={`/log/filter?recordType=comment&recordID=${comment.commentID}`}>
                {t("Revision History")}
            </DropDownItemLink>,
        );
    }

    if (items.length < 1) {
        return <></>;
    }

    return (
        <>
            <DropDown
                openDirection={DropDownOpenDirection.BELOW_LEFT}
                flyoutType={FlyoutType.LIST}
                isVisible={props.isVisible}
            >
                {items.map((item, i) => {
                    return <React.Fragment key={i}>{item}</React.Fragment>;
                })}
            </DropDown>
        </>
    );
}
