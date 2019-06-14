/**
 * Entrypoint for the APIv2 calls. Prepulates an axios instance with some config settings.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formatUrl, t, getMeta } from "@library/utility/appUtils";
import { indexArrayByKey } from "@vanilla/utils";
import axios, { AxiosResponse, AxiosRequestConfig } from "axios";
import qs from "qs";
import { sprintf } from "sprintf-js";
import { humanFileSize } from "@library/utility/fileUtils";
import { IApiError, IFieldError } from "@library/@types/api/core";

function fieldErrorTransformer(responseData) {
    if (responseData && responseData.status >= 400 && responseData.errors && responseData.errors.length > 0) {
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
    let allowedExtensions = getMeta("upload.allowedExtensions", []) as string[];
    allowedExtensions = allowedExtensions.map((ext: string) => ext.toLowerCase());
    const maxSize = getMeta("upload.maxSize", 0);
    const filePieces = file.name.split(".");
    const extension = filePieces[filePieces.length - 1] || "";

    if (file.size > maxSize) {
        const humanSize = humanFileSize(maxSize);
        const stringTotal: string = humanSize.amount + humanSize.unitAbbr;
        const message = sprintf(t("The uploaded file was too big (max %s)."), stringTotal);
        throw new Error(message);
    } else if (!allowedExtensions.includes(extension.toLowerCase())) {
        const attachmentsString = allowedExtensions.join(", ");
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
 * @param apiError - The error to extract from.
 * @param field - The field to extract.
 *
 * @returns an array of IFieldErrors if found or undefined.
 */
export function getFieldErrors(apiError: IApiError | undefined, field: string): IFieldError[] | undefined {
    if (!apiError) {
        return;
    }

    const serverError = apiError.response.data;
    if (serverError && serverError.errors && serverError.errors[field]) {
        return serverError.errors[field];
    }
}

/**
 * Extract a global error message out of an ILoadable if applicable.
 *
 * @param apiError - The error to extract from.
 * @param validFields - Field to check for overriding fields errors from. A global error only shows if there are no valid field errors.
 *
 * @returns A global error message or an undefined.
 */
export function getGlobalErrorMessage(apiError: IApiError | undefined, validFields: string[] = []): string | undefined {
    if (!apiError) {
        return;
    }
    for (const field of validFields) {
        if (getFieldErrors(apiError, field)) {
            return;
        }
    }

    const serverError = apiError.response && apiError.response.data;
    if (serverError && serverError.message) {
        return serverError.message;
    }

    return t("Something went wrong while contacting the server.");
}
