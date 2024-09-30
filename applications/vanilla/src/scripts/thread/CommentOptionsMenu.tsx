/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
import { IApiError } from "@library/@types/api/core";
import { ReportRecordOption } from "@library/features/discussions/ReportRecordOption";
import { useUserCanStillEditDiscussionOrComment } from "@library/features/discussions/discussionHooks";
import { IntegrationButtonAndModal } from "@library/features/discussions/integrations/Integrations";
import {
    WriteableIntegrationContextProvider,
    useWriteableAttachmentIntegrations,
} from "@library/features/discussions/integrations/Integrations.context";
import { useToast } from "@library/features/toaster/ToastContext";
import { IPermission, IPermissionOptions, PermissionChecker, PermissionMode } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useCurrentUser } from "@library/features/users/userHooks";
import DropDown, { DropDownOpenDirection, FlyoutType, IDropDownProps } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ModalConfirm from "@library/modal/ModalConfirm";
import { ToolTip } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
import { useMutation } from "@tanstack/react-query";
import CommentsApi from "@vanilla/addon-vanilla/thread/CommentsApi";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { Hoverable } from "@vanilla/react-utils";
import React, { useState } from "react";

interface ICommentOptionItem {
    shouldRender: (comment: IComment, permissionChecker: PermissionChecker) => boolean;
    component: React.ComponentType<{ comment: IComment; onSuccess?: () => Promise<void> }>;
    sort?: number;
}

const additionalCommentOptions: ICommentOptionItem[] = [];

export function addCommentOption(option: ICommentOptionItem) {
    additionalCommentOptions.push(option);
}

interface IProps {
    comment: IComment;
    discussion: IDiscussion;
    onCommentEdit: () => void;
    onMutateSuccess?: () => Promise<void>;
    isEditLoading: boolean;
    isVisible?: IDropDownProps["isVisible"];
    isInternal?: boolean;
}

const reportButtonAlignment = css({
    "&:not(:last-child)": {
        marginInlineEnd: -8,
    },
});

export function CommentOptionsMenu(props: IProps) {
    const { discussion, comment, onMutateSuccess, isInternal } = props;
    const items: React.ReactNode[] = [];
    const currentUser = useCurrentUser();
    const { hasPermission } = usePermissionsContext();
    const permissionOptions: IPermissionOptions = {
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
        resourceType: "category",
        resourceID: comment.categoryID,
    };

    const canReport = !isInternal && hasPermission("flag.add") && getMeta("featureFlags.escalations.Enabled", false);

    const toast = useToast();
    const deleteMutation = useMutation({
        mutationFn: CommentsApi.delete,
        onSuccess: () => {
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

    const { canStillEdit, humanizedRemainingTime } = useUserCanStillEditDiscussionOrComment(discussion, comment);
    const writeableIntegrations = useWriteableAttachmentIntegrations();

    if (canStillEdit) {
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
                        <span>{humanizedRemainingTime}</span>
                    </DropDownItemButton>
                )}
            </Hoverable>,
        );
    }

    const userCanDeleteComment =
        hasPermission("comments.delete", permissionOptions) ||
        (isOwnComment && canStillEdit && getMeta("ui.allowSelfDelete", false));

    const userCanAccessRevisionHistory = hasPermission("community.moderate") && comment.dateUpdated !== null;

    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

    if (userCanDeleteComment) {
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
                    onConfirm={async () => {
                        await deleteMutation.mutateAsync(comment.commentID);
                        !!onMutateSuccess && (await onMutateSuccess());
                    }}
                >
                    {t("Are you sure you want to delete this comment?")}
                </ModalConfirm>
            </DropDownItemButton>,
        );
    }
    if (userCanAccessRevisionHistory) {
        items.push(
            <DropDownItemLink to={`/log/filter?recordType=comment&recordID=${comment.commentID}`}>
                {t("Revision History")}
            </DropDownItemLink>,
        );
    }

    const additionalItemsToRender = additionalCommentOptions
        .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
        .filter(({ shouldRender }) => shouldRender(comment, hasPermission))
        .map((option, index) => <option.component key={index} comment={comment} onSuccess={onMutateSuccess} />);

    if (additionalItemsToRender.length > 0) {
        items.push(<DropDownItemSeparator />);
        items.push(...additionalItemsToRender);
    }

    let integrationItems: React.ReactNode[] = [];

    writeableIntegrations
        .filter(({ recordTypes }) => recordTypes.includes("comment"))
        .filter(({ writeableContentScope }) => (writeableContentScope === "own" ? isOwnComment : true))
        .forEach(({ attachmentType }) => {
            integrationItems.push(
                <WriteableIntegrationContextProvider
                    recordType="comment"
                    attachmentType={attachmentType}
                    recordID={comment.commentID}
                >
                    <IntegrationButtonAndModal onSuccess={onMutateSuccess} />
                </WriteableIntegrationContextProvider>,
            );
        });

    if (integrationItems.length > 0 && !isInternal) {
        items.push(<DropDownItemSeparator />);
        items.push(...integrationItems);
    }

    if (items.length > 0 && canReport) {
        items.push(<DropDownItemSeparator />);
        items.push(
            <ReportRecordOption
                discussionName={discussion.name}
                recordType={"comment"}
                recordID={comment.commentID}
                onSuccess={onMutateSuccess}
                placeRecordType="category"
                placeRecordID={discussion.categoryID}
            />,
        );
    }

    return (
        <>
            {canReport ? (
                <ReportRecordOption
                    discussionName={discussion.name}
                    recordType={"comment"}
                    recordID={comment.commentID}
                    onSuccess={onMutateSuccess}
                    placeRecordType="category"
                    placeRecordID={discussion.categoryID}
                    customTrigger={(props) => {
                        return (
                            <ToolTip label={t("Report content")}>
                                <Button
                                    buttonType={ButtonTypes.ICON}
                                    onClick={props.onClick}
                                    className={reportButtonAlignment}
                                >
                                    <Icon icon="post-flag" />
                                </Button>
                            </ToolTip>
                        );
                    }}
                />
            ) : null}
            {items.length > 0 ? (
                <DropDown
                    name={t("Comment Options")}
                    buttonContents={<Icon icon="navigation-circle-ellipsis" />}
                    openDirection={DropDownOpenDirection.BELOW_LEFT}
                    flyoutType={FlyoutType.LIST}
                    isVisible={props.isVisible}
                >
                    {items.map((item, i) => {
                        return <React.Fragment key={i}>{item}</React.Fragment>;
                    })}
                </DropDown>
            ) : null}
        </>
    );
}
