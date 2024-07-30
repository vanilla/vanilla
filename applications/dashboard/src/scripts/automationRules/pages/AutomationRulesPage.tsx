/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AutomationRulesProvider } from "@dashboard/automationRules/AutomationRules.context";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import { AutomationRulesList } from "@dashboard/automationRules/AutomationRulesList";

export default function AutomationRulesPage() {
    return (
        <AutomationRulesProvider>
            <ErrorPageBoundary>
                <AutomationRulesList />
            </ErrorPageBoundary>
        </AutomationRulesProvider>
    );
}
