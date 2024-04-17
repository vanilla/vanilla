/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { CustomIntegrationContext } from "@library/features/discussions/integrations/Integrations.types";

/**
 * Additional context values for customizing integrations from a plugin.
 */
const customIntegrationContext: Record<string, () => Promise<CustomIntegrationContext>> = {};
export function registerCustomIntegrationContext(name: string, hook: () => Promise<CustomIntegrationContext>) {
    customIntegrationContext[name] = hook;
}

export function lookupCustomIntegrationsContext(type: string) {
    return customIntegrationContext[type] ?? null;
}
