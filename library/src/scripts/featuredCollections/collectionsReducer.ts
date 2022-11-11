/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { RecordID } from "@vanilla/utils";
import { ICollection } from "@library/featuredCollections/Collections.variables";
import CollectionsActions from "@library/featuredCollections/CollectionsActions";
import sortBy from "lodash/sortBy";
import { getResourceHash } from "@library/featuredCollections/CollectionsUtils";

export interface ICollectionsStoreState {
    collections: ICollectionsState;
}
interface ICollectionsState {
    collections: ILoadable<ICollection[]>;
    collectionsByResourceHash: Record<RecordID, ILoadable<ICollection[]>>;
    collectionsStatusByResourceHash: Record<string, Record<RecordID, ILoadable<RecordID>>>;
    putCollectionsByResourceHash: Record<string, ILoadable<ICollection[]>>;
}

export const INITIAL_COLLECTIONS_STATE: ICollectionsState = {
    collections: { status: LoadStatus.PENDING },
    collectionsByResourceHash: {},
    collectionsStatusByResourceHash: {},
    putCollectionsByResourceHash: {},
};

export const collectionsReducer = produce(
    reducerWithInitialState(INITIAL_COLLECTIONS_STATE)
        .case(CollectionsActions.getCollectionsListACs.started, (state) => {
            state.collections = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(CollectionsActions.getCollectionsListACs.done, (state, payload) => {
            state.collections = {
                status: LoadStatus.SUCCESS,
                data: payload.result ?? [],
            };
            return state;
        })
        .case(CollectionsActions.getCollectionsListACs.failed, (state, payload) => {
            state.collections = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(CollectionsActions.getCollectionsByResourceACs.started, (state, params) => {
            const { recordID, recordType } = params;
            const resourceID = getResourceHash({ recordID, recordType });
            state.collectionsByResourceHash[resourceID] = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(CollectionsActions.getCollectionsByResourceACs.done, (state, payload) => {
            const { recordID, recordType } = payload.params;
            const resourceID = getResourceHash({ recordID, recordType });
            state.collectionsByResourceHash[resourceID] = {
                status: LoadStatus.SUCCESS,
                data: payload.result,
            };
            return state;
        })
        .case(CollectionsActions.getCollectionsByResourceACs.failed, (state, payload) => {
            const { recordID, recordType } = payload.params;
            const resourceID = getResourceHash({ recordID, recordType });
            state.collectionsByResourceHash[resourceID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(CollectionsActions.putCollectionsByResourceACs.started, (state, params) => {
            const { record } = params;
            const resourceID = getResourceHash(record);
            state.putCollectionsByResourceHash[resourceID] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(CollectionsActions.putCollectionsByResourceACs.done, (state, payload) => {
            const { record } = payload.params;
            const resourceID = getResourceHash(record);
            state.putCollectionsByResourceHash[resourceID] = {
                status: LoadStatus.SUCCESS,
                data: payload.result,
            };

            if (!state.collectionsByResourceHash[resourceID]) {
                state.collectionsByResourceHash[resourceID] = { status: LoadStatus.SUCCESS, data: [] };
            }

            state.collectionsByResourceHash[resourceID].data = payload.result;

            return state;
        })
        .case(CollectionsActions.putCollectionsByResourceACs.failed, (state, payload) => {
            const { record } = payload.params;
            const tmpID = getResourceHash(record);
            state.putCollectionsByResourceHash[tmpID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(CollectionsActions.postCollectionsACs.started, (state, params) => {
            const { name, records } = params;
            const resourceID = getResourceHash(records[0]);
            if (!state.collectionsStatusByResourceHash[resourceID]) {
                state.collectionsStatusByResourceHash[resourceID] = {};
            }
            state.collectionsStatusByResourceHash[resourceID][name] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(CollectionsActions.postCollectionsACs.done, (state, payload) => {
            const { name, records } = payload.params;
            const resourceID = getResourceHash(records[0]);
            state.collectionsStatusByResourceHash[resourceID][name] = {
                status: LoadStatus.SUCCESS,
                data: payload.result.collectionID,
            };

            if (!state.collectionsByResourceHash[resourceID]) {
                state.collectionsByResourceHash[resourceID] = { status: LoadStatus.SUCCESS, data: [] };
            }
            state.collectionsByResourceHash[resourceID].data?.push(payload.result);

            if (state.collections.status === LoadStatus.PENDING) {
                state.collections = {
                    status: LoadStatus.SUCCESS,
                    data: [],
                };
            }

            state.collections.data?.push(payload.result);

            return state;
        })
        .case(CollectionsActions.postCollectionsACs.failed, (state, payload) => {
            const { name, records } = payload.params;
            const resourceID = getResourceHash(records[0]);
            state.collectionsStatusByResourceHash[resourceID][name] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        }),
);
