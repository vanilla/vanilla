/**
 * Entrypoint for the APIv2 calls. Prepulates an axios instance with some config settings.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formatUrl, t, getMeta } from "@library/application";
import { indexArrayByKey } from "@library/utility";
import axios, { AxiosResponse, AxiosRequestConfig } from "axios";
import qs from "qs";
import { sprintf } from "sprintf-js";
import { IFieldError, LoadStatus, ILoadable } from "@library/@types/api";
import { humanFileSize } from "@library/utils/fileUtils";

function fieldErrorTransformer(responseData) {
    if (responseData.status >= 400 && responseData.errors && responseData.errors.length > 0) {
        responseData.errors = indexArrayByKey(responseData.errors, "field");
    }

    return responseData;
}

const apiv2 = axios.create({
    baseURL: formatUrl("/api/v2/"),
    headers: {
        common: {
            "X-Requested-With": "vanilla",
        },
    },
    transformResponse: [...(axios.defaults.transformResponse as any), fieldErrorTransformer],
    paramsSerializer: params => qs.stringify(params),
});

export default apiv2;

export type ProgressHandler = (progressEvent: any) => void;

export function createTrackableRequest(
    requestFunction: (progressHandler: ProgressHandler) => () => Promise<AxiosResponse>,
) {
    return (onUploadProgress: ProgressHandler) => {
        return requestFunction(onUploadProgress);
    };
}
/**
 * Upload an image using Vanilla's API v2.
 *
 * @param file - The file to upload.
 */
export async function uploadFile(file: File, requestConfig: AxiosRequestConfig = {}) {
    const allowedAttachments = getMeta("upload.allowedExtensions", []) as string[];
    const maxSize = getMeta("upload.maxSize", 0);
    const filePieces = file.name.split(".");
    const extension = filePieces[filePieces.length - 1] || "";

    if (file.size > maxSize) {
        const humanSize = humanFileSize(maxSize);
        const stringTotal: string = humanSize.amount + humanSize.unitAbbr;
        const message = sprintf(t("The uploaded file was too big (max %s)."), stringTotal);
        throw new Error(message);
    } else if (!allowedAttachments.includes(extension)) {
        const attachmentsString = allowedAttachments.join(", ");
        const message = sprintf(
            t(
                "The uploaded file did not have an allowed extension. \nOnly the following extensions are allowed. \n%s.",
            ),
            attachmentsString,
        );
        throw new Error(message);
    }

    const data = new FormData();
    data.append("file", file, file.name);

    const result = await apiv2.post("/media", data, requestConfig);
    return result.data;
}

/**
 * Extract a field specific error from an ILoadable if applicable.
 *
 * @param loadable - The loadable to extract from.
 * @param field - The field to extract.
 *
 * @returns an array of IFieldErrors if found or undefined.
 */
export function getFieldErrors(loadable: ILoadable<any>, field: string): IFieldError[] | undefined {
    if (loadable.status === LoadStatus.ERROR || loadable.status === LoadStatus.LOADING) {
        if (loadable.error && loadable.error.errors && loadable.error.errors[field]) {
            return loadable.error.errors[field];
        }
    }
}

/**
 * Extract a global error message out of an ILoadable if applicable.
 *
 * @param loadable - The loadable to extract from.
 * @param validFields - Field to check for overriding fields errors from. A global error only shows if there are no valid field errors.
 *
 * @returns A global error message or an undefined.
 */
export function getGlobalErrorMessage(loadable: ILoadable<any>, validFields: string[]): string | undefined {
    if (loadable.status === LoadStatus.ERROR || loadable.status === LoadStatus.LOADING) {
        for (const field of validFields) {
            if (getFieldErrors(loadable, field)) {
                return;
            }
        }

        if (loadable.error) {
            return loadable.error.message || t("An error has occurred, please try again.");
        }
    }
}
