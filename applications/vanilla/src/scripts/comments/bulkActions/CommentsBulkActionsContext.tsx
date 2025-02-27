/*
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useContext, useEffect, useMemo, useState } from "react";
import { useSessionStorage } from "@vanilla/react-utils";
import { useToast } from "@library/features/toaster/ToastContext";
import { BulkActionsManager } from "@library/bulkActions/BulkActionsManager";
import { CommentDeleteMethod, IComment } from "@dashboard/@types/api/comment";
import { CommentsBulkActionsToast } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsBulkActionsToast";
import { BulkAction, IAdditionalBulkAction } from "@library/bulkActions/BulkActions.types";
import { bulkActionsClasses } from "@library/bulkActions/BulkActions.classes";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { useCurrentUser } from "@library/features/users/userHooks";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { PermissionMode } from "@library/features/users/Permission";
import { getMeta } from "@library/utility/appUtils";
import { CommentsSelectAll } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsSelectAll";
import { useNestedCommentContext } from "@vanilla/addon-vanilla/comments/NestedCommentContext";
import { css } from "@emotion/css";
import { useCommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";

/**
 * Context provider to manage bulk actions, checkboxes for discussion thread comments
 *
 */
interface ICommentsBulkActionsContext {
    /** Record of all checked comments */
    checkedCommentIDs: Array<IComment["commentID"]>;
    /** Comments Discussion ID */
    discussionID?: IDiscussion["discussionID"];
    /** Add comments to checked list */
    addCheckedCommentsByIDs(commentID: IComment["commentID"] | Array<IComment["commentID"]>): void;
    /** Remove comments from checked list */
    removeCheckedCommentsByIDs(commentID: IComment["commentID"] | Array<IComment["commentID"]>): void;
    /** Optional post types */
    optionalPostTypes: Array<{ value: string; label: string }>;
    /** Permission check */
    canUseAdminCheckboxes: boolean;
    /** Handle bulk action success */
    handleMutateSuccess: (deleteMethod?: CommentDeleteMethod) => Promise<void>;
}

const CommentsBulkActionsContext = React.createContext<ICommentsBulkActionsContext>({
    checkedCommentIDs: [],
    discussionID: undefined,
    addCheckedCommentsByIDs: () => {},
    removeCheckedCommentsByIDs: () => {},
    optionalPostTypes: [],
    canUseAdminCheckboxes: false,
    handleMutateSuccess: async () => {},
});

export function useCommentsBulkActionsContext() {
    return useContext(CommentsBulkActionsContext);
}

/** Hold optional post types (e.g.question/idea/poll) */
CommentsBulkActionsProvider.postTypes = [];

/**
 * Register optional post types
 *
 * @param postType Value and label.
 */
CommentsBulkActionsProvider.registerPostType = (postType: { value: string; label: string }) => {
    CommentsBulkActionsProvider.postTypes.push(postType);
};

/** Hold external bulk actions (e.g. executed by plugins) */
CommentsBulkActionsProvider.additionalBulkActions = [];

/**
 * Register external bulk actions
 *
 * @param bulkAction
 */
CommentsBulkActionsProvider.registerBulkAction = (bulkAction: IAdditionalBulkAction) => {
    CommentsBulkActionsProvider.additionalBulkActions.push(bulkAction);
};

/**
 * This component is responsible for managing comments selection and bulk action toast
 */
export function CommentsBulkActionsProvider(props: {
    children: React.ReactNode;
    setSelectAllCommentsCheckbox: (component: React.ReactNode) => void;
    onBulkMutateSuccess?: () => Promise<void>;
    selectableCommentIDs?: Array<IComment["commentID"]>;
}) {
    const { children, setSelectAllCommentsCheckbox } = props;
    const commentParent = useCommentThreadParentContext();
    const { addToast, removeToast, updateToast } = useToast();
    const currentUser = useCurrentUser();

    const { selectableCommentIDs, updateCommentList } = useNestedCommentContext();

    const recordPath = `${commentParent.recordType}/${commentParent.recordID}`;

    const [checkedCommentIDs, setCheckedCommentIDs] = useSessionStorage<Array<IComment["commentID"]>>(
        `${currentUser?.userID}_checkedCommentIDs_${recordPath}`,
        [],
    );

    // notify parents to show select all checkbox in comments top bar
    const { hasPermission } = usePermissionsContext();
    const canUseAdminCheckboxes =
        commentParent.recordType === "discussion" && // Right now the various endpoints only work with discussions.
        getMeta("ui.useAdminCheckboxes", false) &&
        (hasPermission(["comments.edit", "comments.delete"], {
            resourceType: "category",
            resourceID: commentParent.categoryID,
            mode: PermissionMode.RESOURCE_IF_JUNCTION,
        }) ||
            hasPermission("community.moderate"));
    useEffect(() => {
        if (canUseAdminCheckboxes) {
            const selectAllProps = {
                selectableCommentIDs: props.selectableCommentIDs ?? selectableCommentIDs,
                checkedCommentIDs,
                addCheckedCommentsByIDs,
                removeCheckedCommentsByIDs,
                className: css({ "&&": { marginBottom: 6 } }),
            };
            setSelectAllCommentsCheckbox(<CommentsSelectAll {...selectAllProps} />);
        }
    }, [canUseAdminCheckboxes, selectableCommentIDs, checkedCommentIDs]);

    const [toastIDsByKey, setToastIDsByKey] = useState<Record<string, any>>({});
    const [bulkAction, setBulkAction] = useState<BulkAction | null>(null);

    const addCheckedCommentsByIDs = (commentIDs: IComment["commentID"] | Array<IComment["commentID"]>) => {
        setCheckedCommentIDs([
            ...new Set([...checkedCommentIDs, ...(Array.isArray(commentIDs) ? commentIDs : [commentIDs])]),
        ]);
    };

    const removeCheckedCommentsByIDs = (commentIDs: IComment["commentID"] | Array<IComment["commentID"]>) => {
        setCheckedCommentIDs((prevState) =>
            prevState.filter((id) => !(Array.isArray(commentIDs) ? commentIDs : [commentIDs]).includes(id)),
        );
    };

    const removeAllCommentIDs = () => {
        setCheckedCommentIDs([]);
    };

    const toastBody = useMemo(() => {
        if (checkedCommentIDs.length > 0) {
            return (
                <CommentsBulkActionsToast
                    selectedIDs={checkedCommentIDs}
                    handleSelectionClear={removeAllCommentIDs}
                    handleBulkDelete={() => setBulkAction(BulkAction.DELETE)}
                    handleBulkSplit={() => setBulkAction(BulkAction.SPLIT)}
                    categoryID={commentParent.categoryID}
                    additionalBulkActions={CommentsBulkActionsProvider.additionalBulkActions}
                    setAction={setBulkAction}
                />
            );
        } else {
            return null;
        }
    }, [checkedCommentIDs]);

    // Manage the bulk actions toast
    useEffect(() => {
        const bulkActionsToastID = toastIDsByKey[`action_${recordPath}`];
        if (toastBody && !bulkActionsToastID) {
            setToastIDsByKey({
                [`action_${recordPath}`]: addToast({
                    persistent: true,
                    body: toastBody,
                    className: bulkActionsClasses().bulkActionsToast,
                }),
            });
        } else {
            updateToast(bulkActionsToastID, { body: toastBody });
        }

        if (!toastBody && bulkActionsToastID) {
            removeToast(bulkActionsToastID);
            setToastIDsByKey((prevState) => ({ ...prevState, [`action_${recordPath}`]: null }));
        }
    }, [toastBody, recordPath]);

    // if we are out from comments thread, remove the toast
    useEffect(() => {
        return () => {
            removeToast(toastIDsByKey[`action_${recordPath}`]);
        };
    }, [toastIDsByKey]);

    const handleMutateSuccess = async (deleteMethod?: CommentDeleteMethod) => {
        // give bit of time after delete
        await new Promise((resolve) => setTimeout(resolve, 100));
        props.onBulkMutateSuccess
            ? await props.onBulkMutateSuccess()
            : await updateCommentList(checkedCommentIDs, { bulkAction, deleteMethod });
        removeCheckedCommentsByIDs(checkedCommentIDs);
    };

    return (
        <CommentsBulkActionsContext.Provider
            value={{
                checkedCommentIDs,
                addCheckedCommentsByIDs,
                removeCheckedCommentsByIDs,
                optionalPostTypes: CommentsBulkActionsProvider.postTypes,
                canUseAdminCheckboxes,
                handleMutateSuccess,
            }}
        >
            <BulkActionsManager
                action={bulkAction}
                setAction={setBulkAction}
                recordType="comment"
                additionalBulkActions={CommentsBulkActionsProvider.additionalBulkActions}
            />
            {children}
        </CommentsBulkActionsContext.Provider>
    );
}
