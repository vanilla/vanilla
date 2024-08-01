/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { onReady } from "@library/utility/appUtils";

// Kludge needed for on of these entries.
(window as any).onVanillaReady = onReady;
