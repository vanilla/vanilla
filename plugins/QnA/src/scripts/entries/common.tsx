/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady } from "@library/utility/appUtils";
import { registerQnaSearchTypes } from "../registerQnaSearchTypes";

onReady(() => {
    registerQnaSearchTypes();
});
