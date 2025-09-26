/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    AutomationRulesPreviewRenderer,
    getDateValueForAPI,
} from "@dashboard/automationRules/preview/AutomationRulesPreviewRenderer";
import { AutomationRulesPreviewPostsContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewPostsContent";
import { AddEditAutomationRuleParams } from "@dashboard/automationRules/AutomationRules.types";

AutomationRulesPreviewRenderer.registerContentByTriggerType({
    unAnsweredQuestionTrigger: {
        component: AutomationRulesPreviewPostsContent,
        queryBuilder: (apiValues: AddEditAutomationRuleParams) => {
            return {
                limit: 30,
                type: ["question"],
                statusID: [1, 4],
                dateInserted: getDateValueForAPI(apiValues),
                categoryID: apiValues.trigger?.triggerValue?.categoryID,
                tagID: apiValues.trigger?.triggerValue?.tagID,
            };
        },
    },
});
