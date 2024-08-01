/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useEffect } from "react";
import { IApiError, ILoadable, LoadStatus } from "@library/@types/api/core";
import { ICollection, ICollectionResource } from "@library/featuredCollections/Collections.variables";
import { useSelector } from "react-redux";
import { useCollectionsActions } from "@library/featuredCollections/CollectionsActions";
import { notEmpty, RecordID } from "@vanilla/utils";
import { ICollectionsStoreState } from "@library/featuredCollections/collectionsReducer";
import { getResourceHash } from "@library/featuredCollections/CollectionsUtils";
import { useQuery } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import { AxiosResponseHeaders } from "axios";

export interface IGetCollectionResourcesParams {
    collectionID?: number | number[] | string;
    dateAddedToCollection?: string;
    page?: number;
    limit?: number;
}

export function useCollectionList(refresh: boolean = true): ILoadable<ICollection[]> {
    const actions = useCollectionsActions();

    useEffect(() => {
        if (refresh) {
            actions.getCollectionsList();
        }
    }, [refresh]);

    const loadStatus = useSelector((state: ICollectionsStoreState) => {
        return state.collections.collections.status ?? LoadStatus.PENDING;
    });

    const collections = useSelector((state: ICollectionsStoreState) => {
        return loadStatus === LoadStatus.SUCCESS ? state.collections.collections.data!.filter(notEmpty) : [];
    });

    return {
        status: loadStatus,
        data: collections,
    };
}

export function useCollectionsByResource(resource: ICollectionResource): ILoadable<ICollection[]> {
    const actions = useCollectionsActions();
    const resourceID = getResourceHash(resource);

    const collections = useSelector((state: ICollectionsStoreState) => {
        return state.collections.collectionsByResourceHash[resourceID] ?? { status: LoadStatus.PENDING };
    });

    useEffect(() => {
        if (collections.status === LoadStatus.PENDING) {
            actions.getCollectionsByResource(resource);
        }
    }, [resource, collections, actions]);

    return collections;
}

export function usePutCollectionsByResource(record: ICollectionResource) {
    const actions = useCollectionsActions();

    async function putCollections(collectionIDs: RecordID[]) {
        try {
            await actions.putCollectionsByResource({
                collectionIDs,
                record,
            });
        } catch (error) {
            throw new Error(error.description);
        }
    }

    return putCollections;
}

export function usePostCollectionsByResource(record: ICollectionResource) {
    const actions = useCollectionsActions();

    async function postCollections(collections: string[] = []) {
        try {
            for (const name of collections) {
                if (name.length === 0) return;

                const query = {
                    name,
                    records: [record],
                };

                await actions.postCollections(query);
            }
        } catch (error) {
            throw new Error(error.description);
        }
    }

    return postCollections;
}

export function useCollectionsStatusByResource(resource: ICollectionResource) {
    const resourceID = getResourceHash(resource);
    const collections = useSelector(
        (state: ICollectionsStoreState) => state.collections.collectionsStatusByResourceHash[resourceID] ?? {},
    );
    return collections;
}

export function useCollectionContents(params: IGetCollectionResourcesParams, locale?: string) {
    const { data, isLoading, error, isRefetching } = useQuery<
        any,
        IApiError,
        { resources: ICollectionResource[]; headers: AxiosResponseHeaders }
    >({
        queryFn: async () => {
            const response = await apiv2.get(`/collections/contents/${locale}?expand=collection`, {
                params: { ...params },
            });
            return { resources: response.data, headers: response.headers };
        },
        keepPreviousData: true,
        queryKey: ["get_collection_contents", params, locale],
    });

    return {
        resources: data?.resources,
        isLoading,
        error,
        isRefetching,
        totalCount: data?.headers["x-app-page-result-count"],
        currentPage: data?.headers["x-app-page-current"],
    };
}
