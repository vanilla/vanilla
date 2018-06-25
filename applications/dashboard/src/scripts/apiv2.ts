/**
 * Entrypoint for the APIv2 calls. Prepulates an axios instance with some config settings.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { formatUrl } from "@dashboard/application";
import { isFileImage } from "@dashboard/utility";
import axios from "axios";
import qs from "qs";
import { IEmbedData } from "@dashboard/embeds";

const api = axios.create({
    baseURL: formatUrl("/api/v2/"),
    headers: {
        common: {
            "X-Requested-With": "vanilla",
        },
    },
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

export interface IMentionUser {
    userID: number;
    name: string;
    photoUrl: string;
    dateLastActive: string | null;
}

export interface IUser extends IMentionUser {
    email: string;
    emailConfirmed: boolean;
    showEmail: boolean;
    bypassSpam: boolean;
    banned: number;
    dateInserted: string;
    dateUpdated: string | null;
    roles: [
        {
            roleID: number;
            name: string;
        }
    ];
    hidden: boolean;
    rankID?: number | null;
    rank?: {
        rankID: number;
        name: string;
        userTitle: string;
    };
}
