/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { formatUrl } from "@core/application";
import axios from "axios";

const api = axios.create({
    baseURL: formatUrl("/api/v2/"),
    headers: {
        common: {
            'X-Requested-With': 'vanilla',
        },
    },
});

export default api;
