/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";

const Api = {
    get: apiv2.get.bind(apiv2) as typeof apiv2.get,
    post: apiv2.post.bind(apiv2) as typeof apiv2.post,
    put: apiv2.put.bind(apiv2) as typeof apiv2.put,
    delete: apiv2.delete.bind(apiv2) as typeof apiv2.delete,
    patch: apiv2.patch.bind(apiv2) as typeof apiv2.patch,
};

export default Api;
