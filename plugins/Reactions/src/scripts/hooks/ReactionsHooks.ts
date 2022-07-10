/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IReaction } from "@Reactions/types/Reaction";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";
import {
    useReactionsSelector,
    useReactionsDispatch,
    getLoadStatusByParamHash,
    getReactionsByUserID,
} from "@Reactions/state/ReactionsReducer";
import { useEffect } from "react";
import { IGetUserReactionsParams, getUserReactions } from "@Reactions/state/ReactionsActions";

export function useUserReactions(
    apiParams: IGetUserReactionsParams,
    prehydratedItems?: IReaction[],
    onLoad?: (data: IReaction[]) => void,
): ILoadable<IReaction[]> {
    const paramHash = stableObjectHash(apiParams);
    const dispatch = useReactionsDispatch();

    const status = useReactionsSelector(
        ({ reactions }) => getLoadStatusByParamHash(reactions, paramHash) ?? LoadStatus.PENDING,
    );

    useEffect(() => {
        if (prehydratedItems) {
            dispatch(getUserReactions.fulfilled(prehydratedItems, paramHash.toString(), apiParams));
        } else {
            if (![LoadStatus.SUCCESS, LoadStatus.ERROR].includes(status)) {
                dispatch(getUserReactions(apiParams));
            }
        }
    }, [prehydratedItems, dispatch, status, paramHash]);

    const data = useReactionsSelector(({ reactions }) =>
        status === LoadStatus.SUCCESS ? getReactionsByUserID(reactions, apiParams.userID)! : [],
    );

    useEffect(() => {
        if (data) {
            onLoad?.(data);
        }
    }, [data, paramHash, onLoad]);

    return {
        status,
        data,
    };
}
