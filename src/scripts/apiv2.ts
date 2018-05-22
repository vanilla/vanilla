/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { formatUrl } from "@core/application";
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
