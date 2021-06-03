/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError, Loadable, LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";
import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { IReactionsStoreState, ReactionsDispatch } from "@Reactions/state/ReactionsReducer";
import { IGetUserReactionsParams, getUserReactions } from "@Reactions/state/ReactionsActions";
import { IReaction } from "@Reactions/types/Reaction";

export function useUserReactions(
    apiParams: IGetUserReactionsParams,
    prehydratedItems?: IReaction[],
): Loadable<IReaction[]> {
    const dispatch = useDispatch<ReactionsDispatch>();

    const paramHash = stableObjectHash(apiParams);

    const status = useSelector((state: IReactionsStoreState) => {
        return state.reactions.reactionIDsByParamHash[paramHash]?.status ?? LoadStatus.PENDING;
    });

    useEffect(() => {
        if (prehydratedItems) {
            dispatch(getUserReactions.fulfilled(prehydratedItems, paramHash.toString(), apiParams));
        } else {
            if (![LoadStatus.SUCCESS, LoadStatus.ERROR].includes(status)) {
                dispatch(getUserReactions(apiParams));
            }
        }
    }, [prehydratedItems, apiParams, status, dispatch, paramHash]);

    const data = useSelector((state: IReactionsStoreState) => {
        return status === LoadStatus.SUCCESS
            ? state.reactions.reactionIDsByUserID[apiParams.userID]!.map(
                  (tagID) => state.reactions.reactionsByID[tagID],
              )
            : [];
    });

    const error = useSelector((state: IReactionsStoreState) => {
        const paramHash = stableObjectHash({ userID: apiParams.userID });
        return status === LoadStatus.ERROR ? state.reactions.reactionIDsByParamHash[paramHash] : undefined;
    });

    const pendingData = {
        status,
    } as {
        status: LoadStatus.PENDING | LoadStatus.LOADING;
        error?: undefined;
        data?: undefined;
    };
    const successData = {
        status,
        data: data,
        error: undefined,
    } as {
        status: LoadStatus.SUCCESS;
        error?: undefined;
        data: IReaction[];
    };
    const errorData = {
        status,
        data: error?.data,
        error: error?.error,
    } as {
        status: LoadStatus.ERROR;
        error: IApiError;
        data?: undefined;
    };
    const successRequest = status == LoadStatus.PENDING || status == LoadStatus.SUCCESS;
    return status === LoadStatus.ERROR ? errorData : status === LoadStatus.SUCCESS ? successData : pendingData;
}
