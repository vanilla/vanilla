/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { VanillaSwaggerDeepLink } from "@openapi-embed/embed/swagger/VanillaSwaggerDeepLink";
import { VanillaSwaggerLayout } from "@openapi-embed/embed/swagger/VanillaSwaggerLayout";

export function VanillaSwaggerPlugin() {
    // Create the plugin that provides our layout component
    return {
        components: {
            VanillaSwaggerLayout: VanillaSwaggerLayout,
            DeepLink: VanillaSwaggerDeepLink,
        },
    };
}
