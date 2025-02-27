/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IReaction } from "@library/reactions/Reaction";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";
import {
    useReactionsSelector,
    useReactionsDispatch,
    getLoadStatusByParamHash,
    getReactionsByUserID,
} from "@library/reactions/ReactionsReducer";
import { useEffect } from "react";
import { IGetUserReactionsParams, getUserReactions } from "@library/reactions/ReactionsActions";

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
                void dispatch(getUserReactions(apiParams));
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
