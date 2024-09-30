/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady } from "@library/utility/appUtils";
import { registerQnaSearchTypes } from "../registerQnaSearchTypes";
import {
    AutomationRulesPreviewRenderer,
    getDateValueForAPI,
} from "@dashboard/automationRules/preview/AutomationRulesPreviewRenderer";
import { AutomationRulesPreviewPostsContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewPostsContent";
import { AddEditAutomationRuleParams } from "@dashboard/automationRules/AutomationRules.types";

onReady(() => {
    registerQnaSearchTypes();
});

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
