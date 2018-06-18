/**
 * Entrypoint for the APIv2 calls. Prepulates an axios instance with some config settings.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { formatUrl } from "@dashboard/application";
import axios from "axios";
import qs from "qs";

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
