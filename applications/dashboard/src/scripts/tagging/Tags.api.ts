/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import {
    IGetTagsRequestBody,
    IGetTagsResponseBody,
    IPostTagRequestBody,
    IPostTagResponseBody,
    IPatchTagRequestBody,
    IPatchTagResponseBody,
    IDeleteTagRequestBody,
} from "@dashboard/tagging/taggingSettings.types";
import SimplePagerModel from "@library/navigation/SimplePagerModel";

export interface ITagsApi {
    getTags(params: IGetTagsRequestBody): Promise<IGetTagsResponseBody>;
    postTag(params: IPostTagRequestBody): Promise<IPostTagResponseBody>;
    patchTag(params: IPatchTagRequestBody): Promise<IPatchTagResponseBody>;
    deleteTag(params: IDeleteTagRequestBody): Promise<void>;
}

const TAGS_API_ENDPOINT = `/tags`;

const TagsApi: ITagsApi = {
    getTags: async function (params) {
        const response = await apiv2.get<IGetTagsResponseBody["data"]>(TAGS_API_ENDPOINT, {
            params: { ...params, type: "User" },
        });
        return {
            data: response.data,
            paging: SimplePagerModel.parseHeaders(response.headers),
        };
    },
    postTag: async function (params) {
        const response = apiv2.post<IPostTagResponseBody>(TAGS_API_ENDPOINT, params);
        return (await response).data;
    },
    patchTag: async function (params) {
        const { tagID, ...rest } = params;
        const response = apiv2.patch<IPatchTagResponseBody>(`${TAGS_API_ENDPOINT}/${tagID}`, rest);
        return (await response).data;
    },
    deleteTag: async function (params) {
        const { tagID } = params;
        const response = apiv2.delete(`${TAGS_API_ENDPOINT}/${tagID}`);
        return (await response).data;
    },
};

export default TagsApi;
