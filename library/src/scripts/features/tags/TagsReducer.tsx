/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { TagsAction } from "@library/features/tags/TagsAction";
import { useSelector } from "react-redux";

export interface ITag {
    id: number;
    name: string;
    urlCode: string;
}

export interface ITagState {
    tagsByName: Record<string, ILoadable<ITag[]>>;
}

export interface ITagsStateStoreState {
    tags: ITagState;
}

export const INITIAL_TAGS_STATE: ITagState = {
    tagsByName: {},
};

export const tagsReducer = produce(
    reducerWithInitialState<ITagState>(INITIAL_TAGS_STATE)
        .case(TagsAction.getTagsACs.started, (nextState, action) => {
            nextState.tagsByName[action.name] = { status: LoadStatus.LOADING };
            return nextState;
        })
        .case(TagsAction.getTagsACs.done, (nextState, payload) => {
            nextState.tagsByName[payload.params.name] = {
                status: LoadStatus.SUCCESS,
                data: payload.result,
            };
            return nextState;
        })
        .case(TagsAction.getTagsACs.failed, (nextState, action) => {
            nextState.tagsByName[action.params.name] = { status: LoadStatus.ERROR };
            return nextState;
        }),
);

export function useTagsState() {
    return useSelector((state: ITagsStateStoreState) => {
        return state.tags;
    });
}
