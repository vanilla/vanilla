/**
 * Entrypoint for the APIv2 calls. Prepulates an axios instance with some config settings.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t, getMeta, siteUrl } from "@library/utility/appUtils";
import { indexArrayByKey, notEmpty } from "@vanilla/utils";
import axios, { AxiosResponse, AxiosRequestConfig, AxiosError } from "axios";
import qs from "qs";
import { sprintf } from "sprintf-js";
import { humanFileSize } from "@library/utility/fileUtils";
import { IApiError, IFieldError } from "@library/@types/api/core";
import { IError } from "@library/errorPages/CoreErrorMessages";

function fieldErrorTransformer(responseData, headers: any) {
    if (responseData && responseData.status >= 400 && responseData.errors && responseData.errors.length > 0) {
        responseData.errors = indexArrayByKey(responseData.errors, "field");
    }

    return responseData;
}

const apiv2 = axios.create({
    baseURL: siteUrl("/api/v2/"),
    headers: {
        common: {
            "X-Requested-With": "vanilla",
        },
    },
    transformResponse: [...(axios.defaults.transformResponse as any), fieldErrorTransformer],
    paramsSerializer: (params) => qs.stringify(params),
});

/**
 * Try to extract a JSON error out of a cloudflare HTML error.
 */
export function extractJsonErrorFromCFHtmlString(body: string): IError | null {
    try {
        const parser = new DOMParser();
        const dom = parser.parseFromString(body, "text/html");
        const details = dom.querySelector(".cf-error-details");
        if (!details) {
            return null;
        }

        const title = details.querySelector("h1")?.innerText ?? t("An Error has Occured");
        const description = details.querySelector("p")?.innerText ?? "";

        const footers = dom.querySelectorAll(".footer-p");
        let rayID: string | null = null;
        for (const footer of footers) {
            if (!(footer instanceof HTMLElement)) {
                continue;
            }

            if (!footer.innerText.includes("Ray ID")) {
                continue;
            }

            rayID = footer.innerText;
        }

        return {
            message: title,
            description: [description, rayID].filter(notEmpty).join(" "),
        };
    } catch (e) {
        return null;
    }
}

/**
 * Error handler for cloudflare errors when making APIv2 requests.
 */
function cloudflareAxiosErrorHandler(error: AxiosError) {
    const data = error.response?.data || "";

    if (typeof data === "object" && data.message) {
        (error as any).description = data.message;
    }

    const contentType = error.response?.headers?.["content-type"];
    if (error.response && typeof contentType === "string" && contentType.startsWith("text/html")) {
        // we have an HTML error.

        const htmlResponse = error.response.data || "";
        const newError = extractJsonErrorFromCFHtmlString(htmlResponse);
        if (newError) {
            return Promise.reject(newError);
        }
    }

    return Promise.reject(error);
}

// Apply the cloudflare error handler.
apiv2.interceptors.response.use(undefined, cloudflareAxiosErrorHandler);

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
