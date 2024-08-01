/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { ICollection, ICollectionResource } from "@library/featuredCollections/Collections.variables";
import { ICollectionsStoreState } from "@library/featuredCollections/collectionsReducer";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { IApiError, LoadStatus } from "@library/@types/api/core";

const API_URL = "/collections";

const createAction = actionCreatorFactory("@@collections");

export interface IGetCollectionListParams {
    collectionID?: ICollection["collectionID"] | Array<ICollection["collectionID"]>;
    name?: ICollection["name"];
    page?: number;
    limit?: number;
}

export interface IPutCollectionsByResourceType {
    collectionIDs: Array<ICollection["collectionID"]>;
    record: ICollectionResource;
}

export interface IPostCollectionsType {
    name: string;
    records: ICollectionResource[];
}

export default class CollectionsActions extends ReduxActions<ICollectionsStoreState & ICoreStoreState> {
    public static getCollectionsListACs = createAction.async<null, ICollection[], IApiError>("GET_COLLECTION_LIST");

    public getCollectionsList = () => {
        const { collections } = this.getState().collections;
        if (collections.status === LoadStatus.LOADING || collections.status === LoadStatus.SUCCESS) {
            return;
        }

        const thunk = bindThunkAction(CollectionsActions.getCollectionsListACs, async () => {
            const response = await this.api.get(API_URL);
            return response.data;
        })();

        return this.dispatch(thunk);
    };

    public static getCollectionsByResourceACs = createAction.async<ICollectionResource, ICollection[], IApiError>(
        "GET_COLLECTIONS_BY_RESOURCE",
    );

    public getCollectionsByResource = (params: ICollectionResource) => {
        const thunk = bindThunkAction(CollectionsActions.getCollectionsByResourceACs, async () => {
            const response = await this.api.get(`${API_URL}/by-resource`, { params });
            return response.data;
        })(params);
        return this.dispatch(thunk);
    };

    public static putCollectionsByResourceACs = createAction.async<
        IPutCollectionsByResourceType,
        ICollection[],
        IApiError
    >("PUT_COLLECTIONS_BY_RESOURCE");

    public putCollectionsByResource = (query: IPutCollectionsByResourceType) => {
        const thunk = bindThunkAction(CollectionsActions.putCollectionsByResourceACs, async () => {
            const apiUrl = `${API_URL}/by-resource`;
            // the current API endpoint for putting collections by resource
            // will have an error if a collectionID already exists and will
            // remove the current list and replace with only the new IDs so the
            // existing list needs to be removed before adding the proper list
            await this.api.put(apiUrl, { record: query.record, collectionIDs: [] });

            const response = await this.api.put(apiUrl, query);
            return response.data;
        })(query);
        return this.dispatch(thunk);
    };

    public static postCollectionsACs = createAction.async<IPostCollectionsType, ICollection, IApiError>(
        "POST_COLLECTIONS",
    );

    public postCollections = (query: IPostCollectionsType) => {
        const thunk = bindThunkAction(CollectionsActions.postCollectionsACs, async () => {
            const response = await this.api.post(API_URL, query);
            return response.data;
        })(query);
        return this.dispatch(thunk);
    };
}

export function useCollectionsActions() {
    return useReduxActions(CollectionsActions);
}
