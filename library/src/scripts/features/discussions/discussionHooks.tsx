/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DiscussionActions, {
    IAnnounceDiscussionParams,
    IDeleteDiscussionReaction,
    IGetDiscussionByID,
    IMoveDiscussionParams,
    IPostDiscussionReaction,
    IPutDiscussionBookmarked,
    useDiscussionActions,
} from "@library/features/discussions/DiscussionActions";
import { IDiscussionsStoreState } from "@library/features/discussions/discussionsReducer";
import { useDispatch, useSelector } from "react-redux";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import React, { useCallback, useEffect, useMemo, useState } from "react";
import { IDiscussion, IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { logError, notEmpty, RecordID, stableObjectHash } from "@vanilla/utils";
import { useCurrentUserID } from "@library/features/users/userHooks";
import { hasPermission, PermissionMode } from "@library/features/users/Permission";
import { usePermissions } from "@library/features/users/userModel";
import { getMeta, t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { useDiscussionCheckBoxContext } from "@library/features/discussions/DiscussionCheckboxContext";
import { useToast } from "@library/features/toaster/ToastContext";
import ErrorMessages from "@library/forms/ErrorMessages";

export function useDiscussion(discussionID: IGetDiscussionByID["discussionID"]): ILoadable<IDiscussion> {
    const actions = useDiscussionActions();

    const existingResult = useSelector((state: IDiscussionsStoreState) => {
        return {
            status: state.discussions.discussionsByID[discussionID]
                ? LoadStatus.SUCCESS
                : state.discussions.fullRecordStatusesByID[discussionID]?.status ?? LoadStatus.PENDING,
            data: state.discussions.discussionsByID[discussionID],
        };
    });

    const { status } = existingResult;

    useEffect(() => {
        if (LoadStatus.PENDING.includes(status)) {
            actions.getDiscussionByID({ discussionID });
        }
    }, [status, actions, discussionID]);

    return existingResult;
}

export function useToggleDiscussionBookmarked(discussionID: IPutDiscussionBookmarked["discussionID"]) {
    const { putDiscussionBookmarked } = useDiscussionActions();
    const { addToast } = useToast();

    const error =
        useSelector((state: IDiscussionsStoreState) => state.discussions.bookmarkStatusesByID[discussionID]?.error) ??
        null;

    const isBookmarked = useSelector(
        (state: IDiscussionsStoreState) => state.discussions.discussionsByID[discussionID].bookmarked,
    );

    useEffect(() => {
        if (error) {
            addToast({
                dismissible: true,
                body: (
                    <ErrorMessages
                        errors={[error ?? { message: t("There was a problem bookmarking this discussion.") }]}
                    />
                ),
            });
        }
    }, [error]);

    async function toggleDiscussionBookmarked(bookmarked: IPutDiscussionBookmarked["bookmarked"]) {
        return await putDiscussionBookmarked({
            discussionID,
            bookmarked,
        });
    }

    return { toggleDiscussionBookmarked, isBookmarked };
}

export function useCurrentDiscussionReaction(discussionID: IDiscussion["discussionID"]) {
    return useSelector(function (state: IDiscussionsStoreState) {
        return state.discussions.discussionsByID[discussionID]?.reactions?.find(({ hasReacted }) => hasReacted);
    });
}

export function useReactToDiscussion(discussionID: IPostDiscussionReaction["discussionID"]) {
    const { postDiscussionReaction } = useDiscussionActions();

    const currentReaction = useCurrentDiscussionReaction(discussionID);

    async function reactToDiscussion(reaction: IPostDiscussionReaction["reaction"]) {
        return await postDiscussionReaction({
            discussionID,
            reaction,
            currentReaction,
        });
    }

    return reactToDiscussion;
}

export function useRemoveDiscussionReaction(discussionID: IDeleteDiscussionReaction["discussionID"]) {
    const { deleteDiscussionReaction } = useDiscussionActions();

    const currentReaction = useCurrentDiscussionReaction(discussionID)!;

    async function removeDiscussionReaction() {
        return await deleteDiscussionReaction({
            discussionID,
            currentReaction,
        });
    }

    return removeDiscussionReaction;
}

export function useDiscussionList(
    apiParams: IGetDiscussionListParams,
    prehydratedItems?: IDiscussion[],
): ILoadable<IDiscussion[]> {
    const dispatch = useDispatch();
    const actions = useDiscussionActions();
    const paramHash = stableObjectHash(apiParams);

    useEffect(() => {
        if (prehydratedItems) {
            dispatch(
                DiscussionActions.getDiscussionListACs.done({
                    params: apiParams,
                    result: prehydratedItems,
                }),
            );
        } else {
            actions.getDiscussionList(apiParams);
        }
    }, [prehydratedItems, paramHash, dispatch, actions]);

    const loadStatus = useSelector(
        (state: IDiscussionsStoreState) =>
            state.discussions.discussionIDsByParamHash[paramHash]?.status ?? LoadStatus.PENDING,
    );

    const discussions = useSelector((state: IDiscussionsStoreState) => {
        return loadStatus === LoadStatus.SUCCESS
            ? state.discussions.discussionIDsByParamHash[paramHash]
                  .data!.map((discussionID) => state.discussions.discussionsByID[discussionID])
                  .filter(notEmpty)
            : [];
    });

    return {
        status: loadStatus,
        data: discussions,
    };
}

export function useUserCanEditDiscussion(discussion: IDiscussion) {
    usePermissions();

    const currentUserID = useCurrentUserID();
    const currentUserIsDiscussionAuthor = discussion.insertUserID === currentUserID;

    const now = new Date();
    const cutoff =
        getMeta("ui.editContentTimeout", -1) > -1
            ? new Date(new Date(discussion.dateInserted).getTime() + getMeta("ui.editContentTimeout") * 1000)
            : null;

    return (
        hasPermission("discussions.manage", {
            mode: PermissionMode.RESOURCE_IF_JUNCTION,
            resourceType: "category",
            resourceID: discussion.categoryID,
        }) ||
        (currentUserIsDiscussionAuthor && !discussion.closed && (cutoff === null || now < cutoff))
    );
}

function usePatchStatus(discussionID: number, patchID: string): LoadStatus {
    return useSelector((state: IDiscussionsStoreState) => {
        return state.discussions.patchStatusByPatchID[`${discussionID}-${patchID}`]?.status ?? LoadStatus.PENDING;
    });
}

export function useDiscussionPatch(discussionID: number, patchID: string | null = null) {
    const ownID = useUniqueID("discussionPatch");
    const actualPatchID = patchID ?? ownID;
    const isLoading = usePatchStatus(discussionID, actualPatchID) === LoadStatus.LOADING;

    const actions = useDiscussionActions();

    const patchDiscussion = useCallback(
        (query: Omit<Parameters<typeof actions.patchDiscussion>[0], "discussionID" | "patchStatusID">) => {
            return actions.patchDiscussion({
                discussionID,
                patchStatusID: actualPatchID,
                ...query,
            });
        },
        [actualPatchID, actions, discussionID],
    );

    return {
        isLoading,
        patchDiscussion: patchDiscussion,
    };
}

function useDiscussionPutTypeStatus(discussionID: number): LoadStatus {
    return useSelector((state: IDiscussionsStoreState) => {
        return state.discussions.changeTypeByID[discussionID]?.status ?? LoadStatus.PENDING;
    });
}

export function useDiscussionPutType(discussionID: number) {
    const isLoading = useDiscussionPutTypeStatus(discussionID) === LoadStatus.LOADING;
    const actions = useDiscussionActions();

    const putDiscussionType = useCallback(
        (query: Omit<Parameters<typeof actions.putDiscussionType>[0], "discussionID">) => {
            return actions.putDiscussionType({
                discussionID,
                ...query,
            });
        },
        [actions, discussionID],
    );

    return {
        isLoading,
        putDiscussionType: putDiscussionType,
    };
}

export function usePutDiscussionTags(discussionID: number) {
    const actions = useDiscussionActions();

    async function putDiscussionTags(tagIDs: number[]) {
        try {
            await actions.putDiscussionTags({
                discussionID,
                tagIDs,
            });
        } catch (error) {
            throw new Error(error.description); //fixme: what we really want is an object that we can pass wholesale to formik's setError() function
        }
    }

    return putDiscussionTags;
}

/**
 * This hooks will return a selection of the already loaded discussions
 */
export function useDiscussionByIDs(discussionIDs: RecordID[]): Record<RecordID, IDiscussion> | null {
    const { getDiscussionByIDs } = useDiscussionActions();

    // This state will handle the specific discussions requested
    const [discussions, setDiscussions] = useState<Record<RecordID, IDiscussion> | null>(null);

    const discussionLoadStatus = useSelector(
        (state: IDiscussionsStoreState) => state.discussions.fullRecordStatusesByID ?? {},
    );

    // Discussion list could have already loaded, check for data here first
    const loadedDiscussions = useSelector((state: IDiscussionsStoreState) => state.discussions.discussionsByID);

    // We using the status field to determine if any additional requests should be made
    const discussionStatusByID = useMemo(() => {
        return Object.fromEntries(discussionIDs.map((ID) => [ID, discussionLoadStatus[ID]?.status ?? null]));
    }, [discussionLoadStatus]);

    // Maintain a list of selected IDs which we do not have data for
    const missingDiscussions = useMemo(() => {
        if (Object.keys(loadedDiscussions).length > 0 && discussionIDs.length > 0) {
            return (
                discussionIDs
                    // First filter any discussions not already in the store
                    .filter((ID) => !loadedDiscussions[ID])
                    // Next filter any discussions which have already been requested
                    .filter(
                        (ID) =>
                            ![LoadStatus.LOADING, LoadStatus.ERROR, LoadStatus.SUCCESS].includes(
                                discussionStatusByID[ID],
                            ),
                    )
            );
        }
        return [];
    }, [loadedDiscussions, discussionIDs, discussionStatusByID]);

    useEffect(() => {
        if (discussionIDs.length !== (discussions ? Object.keys(discussions).length : 0)) {
            setDiscussions(() =>
                Object.fromEntries(
                    discussionIDs
                        .map((ID) => {
                            return loadedDiscussions[ID] && [ID, loadedDiscussions[ID]];
                        })
                        .filter(notEmpty),
                ),
            );
        }
    }, [discussionIDs, loadedDiscussions]);

    useEffect(() => {
        // If there is are any missing discussions, fetch those specific discussions
        missingDiscussions.length > 0 && getDiscussionByIDs({ discussionIDs: missingDiscussions });
    }, [missingDiscussions]);

    return discussions;
}

/**
 * This hook is used to display the correct status for the bulk delete form
 */
export function useBulkDelete(discussionIDs: RecordID | RecordID[]) {
    const { bulkDeleteDiscussion } = useDiscussionActions();
    const {
        addCheckedDiscussionsByIDs,
        removeCheckedDiscussionsByIDs,
        addPendingDiscussionByIDs,
        removePendingDiscussionByIDs,
    } = useDiscussionCheckBoxContext();
    // Use this state to maintain the statues requested
    const [statusByID, setStatusByID] = useState<Record<RecordID, LoadStatus> | null>(null);
    const deleteStatuses = useSelector((state: IDiscussionsStoreState) => state.discussions.deleteStatusesByID);

    // Tracks if deletion is in progress (sync)
    const isDeletePending = useMemo<boolean>(() => {
        if (statusByID) {
            return Object.values(statusByID).some((status) => status === LoadStatus.LOADING);
        }
        return false;
    }, [statusByID]);

    const filterStatusByID = (
        statusByID: Record<RecordID, LoadStatus> | null,
        statusCondition: LoadStatus,
    ): number[] | null => {
        if (statusByID) {
            const result = Object.keys(statusByID)
                .filter((ID) => statusByID[ID] === statusCondition)
                .map((value) => parseInt(value));
            return result.length > 0 ? result : null;
        }
        return null;
    };

    // Returns IDs where deletion has failed
    const deletionFailedIDs = useMemo(() => filterStatusByID(statusByID, LoadStatus.ERROR), [statusByID]);
    // Returns IDs where deletion has succeeded
    const deletionSuccessIDs = useMemo(() => filterStatusByID(statusByID, LoadStatus.SUCCESS), [statusByID]);

    useEffect(() => {
        const requestedIDs = Array.isArray(discussionIDs) ? discussionIDs : [discussionIDs];

        setStatusByID(() => {
            return Object.fromEntries(requestedIDs.map((ID) => [ID, deleteStatuses[ID]?.status ?? null]));
        });
    }, [deleteStatuses, discussionIDs]);

    // Reselect failed IDs if any deletions failed
    useEffect(() => {
        if (deletionFailedIDs && deletionFailedIDs.length > 0) {
            addCheckedDiscussionsByIDs(deletionFailedIDs);
            removePendingDiscussionByIDs(deletionFailedIDs);
        }
    }, [deletionFailedIDs]);

    // Remove successful IDs from pending list
    useEffect(() => {
        if (deletionSuccessIDs && deletionSuccessIDs.length > 0) {
            removePendingDiscussionByIDs(deletionSuccessIDs);
        }
    }, [deletionSuccessIDs]);

    // Execute the delete request and manage the discussion selection
    const deleteSelectedIDs = () => {
        // Fire off the request to delete
        bulkDeleteDiscussion({ discussionIDs: discussionIDs as number[] });
        // Add these IDs to the pending list
        addPendingDiscussionByIDs(discussionIDs);
        // Remove them from the selection
        removeCheckedDiscussionsByIDs(discussionIDs);
    };

    return { isDeletePending, deletionFailedIDs, deletionSuccessIDs, deleteSelectedIDs };
}

// TODO: This hook has way too much repetition as the bulk delete. FIX IT!
/**
 * This hook is used to power the bulk move form
 */
export function useBulkDiscussionMove(
    discussionIDs: RecordID | RecordID[],
    categoryID: RecordID | undefined,
    addRedirects: boolean,
) {
    const { bulkMoveDiscussions, getCategoryByID } = useDiscussionActions();
    const {
        addCheckedDiscussionsByIDs,
        removeCheckedDiscussionsByIDs,
        addPendingDiscussionByIDs,
        removePendingDiscussionByIDs,
    } = useDiscussionCheckBoxContext();

    // This hook ensures we always dealing with arrays of IDs
    const discussionIDsList = useMemo<RecordID[]>(() => {
        return Array.isArray(discussionIDs) ? discussionIDs : [discussionIDs];
    }, [discussionIDs]);

    const patchStatuses = useSelector((state: IDiscussionsStoreState) => state.discussions.patchStatusByPatchID);

    const filterStatusByID = (
        statusByID: Record<string, ILoadable> | null,
        statusCondition: LoadStatus,
    ): RecordID[] | null => {
        // debugger;
        if (statusByID) {
            const result = Object.keys(statusByID)
                .filter((ID) => {
                    const actualID = ID.replace("-move", "");
                    return discussionIDsList.includes(Number(actualID));
                })
                .filter((ID) => statusByID[ID].status === statusCondition)
                // Remove the appended identifier and return as a number
                .map((ID) => Number(ID.replace("-move", "")));
            return result.length > 0 ? result : null;
        }
        return null;
    };

    // Track ID by Status
    const pendingIDs = useMemo(() => filterStatusByID(patchStatuses, LoadStatus.LOADING), [patchStatuses]);
    const failedIDs = useMemo(() => filterStatusByID(patchStatuses, LoadStatus.ERROR), [patchStatuses]);
    const successIDs = useMemo(() => filterStatusByID(patchStatuses, LoadStatus.SUCCESS), [patchStatuses]);

    const isPending = useMemo(() => pendingIDs && pendingIDs.length > 0, [pendingIDs]);
    const isSuccess = useMemo(() => (successIDs && successIDs.length > 0) ?? false, [successIDs]);

    const failedDiscussions = useDiscussionByIDs((failedIDs as number[]) ?? []);

    // Reselect failed IDs if any moves failed
    useEffect(() => {
        if (failedIDs && failedIDs.length > 0) {
            addCheckedDiscussionsByIDs(failedIDs);
            removePendingDiscussionByIDs(failedIDs);
        }
    }, [failedIDs]);

    // Remove successful IDs from pending list
    useEffect(() => {
        if (successIDs && successIDs.length > 0) {
            removePendingDiscussionByIDs(successIDs);
        }
    }, [successIDs]);

    const category = useCategoryByID(categoryID);

    // Execute the move request and manage the discussion selection
    const moveSelectedDiscussions = () => {
        // Fire off the request to delete
        if (categoryID && category) {
            bulkMoveDiscussions({ discussionIDs: discussionIDsList, categoryID, addRedirects, category });
        }
        // Add these IDs to the pending list
        addPendingDiscussionByIDs(discussionIDs);
        // Remove them from the selection
        removeCheckedDiscussionsByIDs(discussionIDs);
    };
    return { isSuccess, isPending, failedDiscussions, moveSelectedDiscussions };
}

function useCategoryByID(categoryID: RecordID | undefined) {
    const { getCategoryByID } = useDiscussionActions();
    const loadedCategories = useSelector((state: IDiscussionsStoreState) => state.discussions.categoriesByID);

    const result = useMemo(() => {
        return (categoryID && loadedCategories[categoryID]) ?? null;
    }, [loadedCategories, categoryID]);

    useEffect(() => {
        if (!result) {
            categoryID && getCategoryByID({ categoryID });
        }
    }, [categoryID, getCategoryByID, result]);

    return result;
}
