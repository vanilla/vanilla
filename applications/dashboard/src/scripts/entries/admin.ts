/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import "@dashboard/legacy";
import { initAllUserContent } from "@library/content";
import { onContent } from "@library/utility/appUtils";

onContent(() => initAllUserContent());
