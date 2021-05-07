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
import { useCallback, useEffect } from "react";
import { IDiscussion, IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { stableObjectHash } from "@vanilla/utils";
import { useCurrentUserID } from "@library/features/users/userHooks";
import { hasPermission, PermissionMode } from "@library/features/users/Permission";
import { usePermissions } from "@library/features/users/userModel";
import { getMeta } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";

export function useDiscussion(discussionID: IGetDiscussionByID["discussionID"]): ILoadable<IDiscussion> {
    const actions = useDiscussionActions();

    const existingResult = useSelector((state: IDiscussionsStoreState) => {
        return {
            status:
                (!!state.discussions.discussionsByID[discussionID] && LoadStatus.SUCCESS) ??
                state.discussions.fullRecordStatusesByID[discussionID].status ??
                LoadStatus.PENDING,
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

    async function toggleDiscussionBookmarked(bookmarked: IPutDiscussionBookmarked["bookmarked"]) {
        return await putDiscussionBookmarked({
            discussionID,
            bookmarked,
        });
    }

    return toggleDiscussionBookmarked;
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
    }, [prehydratedItems, apiParams, paramHash, dispatch, actions]);

    const loadStatus = useSelector(
        (state: IDiscussionsStoreState) =>
            state.discussions.discussionIDsByParamHash[paramHash]?.status ?? LoadStatus.PENDING,
    );

    const discussions = useSelector((state: IDiscussionsStoreState) => {
        return loadStatus === LoadStatus.SUCCESS
            ? state.discussions.discussionIDsByParamHash[paramHash].data!.map(
                  (discussionID) => state.discussions.discussionsByID[discussionID],
              )
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
