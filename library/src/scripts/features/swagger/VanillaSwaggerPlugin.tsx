/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { VanillaSwaggerDeepLink } from "@library/features/swagger/VanillaSwaggerDeepLink";
import { VanillaSwaggerLayout } from "@library/features/swagger/VanillaSwaggerLayout";

export function VanillaSwaggerPlugin() {
    // Create the plugin that provides our layout component
    return {
        components: {
            VanillaSwaggerLayout: VanillaSwaggerLayout,
            DeepLink: VanillaSwaggerDeepLink,
        },
    };
}
