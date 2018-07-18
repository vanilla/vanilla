/**
 * Entrypoint for the APIv2 calls. Prepulates an axios instance with some config settings.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { formatUrl } from "@dashboard/application";
import { isFileImage, indexArrayByKey } from "@dashboard/utility";
import axios, { AxiosResponse } from "axios";
import qs from "qs";
import { IEmbedData } from "@dashboard/embeds";

function fieldErrorTransformer(responseData) {
    console.log("Response before", responseData, responseData.errors);
    if (responseData.status >= 400 && responseData.errors && responseData.errors.length > 0) {
        console.log("Transfroming");
        responseData.errors = indexArrayByKey(responseData.errors, "field");
    }

    console.log("Response after", responseData);

    return responseData;
}

const api = axios.create({
    baseURL: formatUrl("/api/v2/"),
    headers: {
        common: {
            "X-Requested-With": "vanilla",
        },
    },
    transformResponse: [...axios.defaults.transformResponse, fieldErrorTransformer],
    paramsSerializer: params => qs.stringify(params, { indices: false }),
});

export default api;

/**
 * Upload an image using Vanilla's API v2.
 *
 * @param file - The file to upload.
 *
 * @throws If the file given is not an image. You must check yourself first.
 */
export async function uploadImage(image: File): Promise<IEmbedData> {
    if (!isFileImage(image)) {
        throw new Error(
            `Unable to upload an image of type ${image.type}. Supported formats included .gif, .jpg and .png`,
        );
    }

    const data = new FormData();
    data.append("file", image, image.name);
    data.append("type", "image");

    const result = await api.post("/media", data);
    result.data.type = "image";
    return result.data;
}
