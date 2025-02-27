/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
import { ReportRecordOption } from "@library/features/discussions/ReportRecordOption";
import { useUserCanStillEditDiscussionOrComment } from "@library/features/discussions/discussionHooks";
import { IntegrationButtonAndModal } from "@library/features/discussions/integrations/Integrations";
import {
    WriteableIntegrationContextProvider,
    useWriteableAttachmentIntegrations,
} from "@library/features/discussions/integrations/Integrations.context";
import { IPermissionOptions, PermissionChecker, PermissionMode } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useCurrentUser } from "@library/features/users/userHooks";
import DropDown, { DropDownOpenDirection, FlyoutType, IDropDownProps } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ToolTip } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { Hoverable, useMobile } from "@vanilla/react-utils";
import React, { useEffect, useState } from "react";
import { stableObjectHash } from "@vanilla/utils";
import PostReactionsModal from "@library/postReactions/PostReactionsModal";
import { IFlagOption } from "@library/postReactions/LegacyFlagDropdown";
import { DeleteCommentModal } from "@vanilla/addon-vanilla/comments/DeleteCommentModal";
import type { ICommentParent } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";

interface ICommentOptionItem {
    shouldRender: (comment: IComment, permissionChecker: PermissionChecker) => boolean;
    component: React.ComponentType<IFlagOption & { comment: IComment; onSuccess?: () => Promise<void> }>;
    sort?: number;
    isFlagOption?: boolean;
}

const additionalCommentOptions: ICommentOptionItem[] = [];

export function addCommentOption(option: ICommentOptionItem) {
    additionalCommentOptions.push(option);
}

interface IProps {
    comment: IComment;
    commentParent: ICommentParent;
    onCommentEdit: () => void;
    onMutateSuccess?: () => Promise<void>;
    isEditLoading: boolean;
    isVisible?: IDropDownProps["isVisible"];
    isInternal?: boolean;
    isTrollContentVisible?: boolean;
    toggleTrollContent?: (newVisibilityState: boolean) => void;
}

const reportButtonAlignment = css({
    "&:not(:last-child)": {
        marginInlineEnd: -8,
    },
});

export function CommentOptionsMenu(props: IProps) {
    const { commentParent, comment, onMutateSuccess, isInternal, isTrollContentVisible, toggleTrollContent } = props;
    const [ownVisible, setOwnVisible] = useState(false);
    const items: React.ReactNode[] = [];
    const currentUser = useCurrentUser();
    const { hasPermission } = usePermissionsContext();
    const permissionOptions: IPermissionOptions = {
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
        resourceType: "category",
        resourceID: comment.categoryID,
    };

    const isMobile = useMobile();

    useEffect(() => {
        if (props.isVisible && props.isVisible !== ownVisible) {
            setOwnVisible(props.isVisible);
        }
    }, [props.isVisible]);

    const isTroll = !!comment?.isTroll;

    const canReport = !isInternal && hasPermission("flag.add") && getMeta("featureFlags.escalations.Enabled", false);

    const isOwnComment = comment.insertUserID === currentUser?.userID;

    const { canStillEdit, humanizedRemainingTime } = useUserCanStillEditDiscussionOrComment({
        ...comment,
        closed: commentParent.closed ?? false,
    });
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
                    <DropDownItemButton
                        isLoading={props.isEditLoading}
                        {...hoverProps}
                        onClick={() => {
                            props.onCommentEdit();
                            setOwnVisible(false);
                        }}
                    >
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
    const canManageReactions = hasPermission("community.moderate");

    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

    if (userCanDeleteComment) {
        items.push(
            <DropDownItemButton
                onClick={() => {
                    setShowDeleteConfirm(true);
                    setOwnVisible(false);
                }}
            >
                {t("Delete")}
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
    const [logVisible, setLogVisible] = useState(false);
    if (canManageReactions && isMobile) {
        items.push(
            <>
                <DropDownItemButton
                    onClick={() => {
                        setLogVisible(true);
                        setOwnVisible(false);
                    }}
                >
                    {t("Reaction Log")}
                </DropDownItemButton>
            </>,
        );
    }

    if (isTroll && hasPermission("community.moderate")) {
        items.push(
            <DropDownItemButton
                onClick={() => {
                    toggleTrollContent?.(!isTrollContentVisible);
                }}
            >
                {isTrollContentVisible ? t("Hide troll content") : t("Show troll content")}
            </DropDownItemButton>,
        );
    }

    const additionalItemsToRender = additionalCommentOptions
        .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
        .filter(({ shouldRender, isFlagOption }) => shouldRender(comment, hasPermission) && !isFlagOption)
        .map((option, index) => (
            <option.component
                key={index}
                comment={comment}
                onSuccess={async () => {
                    await onMutateSuccess?.();
                }}
            />
        ));

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
                    <IntegrationButtonAndModal
                        onSuccess={async () => {
                            await onMutateSuccess?.();
                        }}
                    />
                </WriteableIntegrationContextProvider>,
            );
        });

    if (integrationItems.length > 0 && !isInternal) {
        items.push(<DropDownItemSeparator />);
        items.push(...integrationItems);
    }

    if (items.length > 0 && canReport) {
        const additionalFlagItemsToRender = additionalCommentOptions
            .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
            .filter(({ shouldRender, isFlagOption }) => shouldRender(comment, hasPermission) && isFlagOption)
            .map((option, index) => (
                <option.component
                    key={index}
                    comment={comment}
                    onSuccess={async () => {
                        await onMutateSuccess?.();
                    }}
                />
            ));
        items.push(<DropDownItemSeparator />);
        items.push(
            <ReportRecordOption
                recordName={comment.name}
                recordType={"comment"}
                recordID={comment.commentID}
                onSuccess={async () => {
                    await onMutateSuccess?.();
                }}
                placeRecordType="category"
                placeRecordID={comment.categoryID}
            />,
        );
        items.push(...additionalFlagItemsToRender);
    }

    const commentOptionsMenuClasses = css({
        display: "flex",
        alignItems: "center",
        alignSelf: "start",
        gap: 0,

        "@media (max-width: 807px)": {
            gap: 16,
        },
    });

    return (
        <span className={commentOptionsMenuClasses}>
            {canReport ? (
                <ReportRecordOption
                    recordName={comment.name}
                    recordType={"comment"}
                    recordID={comment.commentID}
                    onSuccess={onMutateSuccess}
                    placeRecordType="category"
                    placeRecordID={comment.categoryID}
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
                    key={stableObjectHash({ ...comment })}
                    isVisible={ownVisible}
                    onVisibilityChange={(newVisibility) => setOwnVisible(newVisibility)}
                >
                    {items.map((item, i) => {
                        return <React.Fragment key={i}>{item}</React.Fragment>;
                    })}
                </DropDown>
            ) : null}
            <DeleteCommentModal
                isVisible={showDeleteConfirm}
                commentID={comment.commentID}
                onCancel={() => {
                    setShowDeleteConfirm(false);
                    setOwnVisible(true);
                }}
                onMutateSuccess={async () => {
                    !!onMutateSuccess && (await onMutateSuccess());
                }}
            />
            {logVisible && (
                <PostReactionsModal
                    visibility={logVisible}
                    onVisibilityChange={() => {
                        setLogVisible(false);
                        setOwnVisible(true);
                    }}
                />
            )}
        </span>
    );
}
