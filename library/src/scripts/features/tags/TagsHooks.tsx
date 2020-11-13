/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useSelector } from "react-redux";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { ITag, ITagsStateStoreState } from "@library/features/tags/TagsReducer";
import { useEffect } from "react";
import { useTagsActions } from "@library/features/tags/TagsAction";

export function useTagSearch(search: string): ILoadable<ITag[]> {
    search = search.trim();
    const { getTags } = useTagsActions();
    const tagsByName = useSelector((state: ITagsStateStoreState) => state.tags?.tagsByName[search]);

    const { status = LoadStatus.PENDING, data = {} } = tagsByName || {};

    useEffect(() => {
        if (status && status === LoadStatus.PENDING && search) {
            getTags({ name: search });
        }
    }, [search, status, getTags]);

    return tagsByName;
}
