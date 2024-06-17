/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@library/@types/api/users";
import { IGetCategoryListParams } from "@library/categoriesWidget/CategoryList.hooks";
import { IGetTagsParams } from "@library/features/tags/TagsHooks";
import { JsonSchema } from "@vanilla/json-schema-forms";

export type AutomationRuleTriggerType =
    | "emailDomainTrigger"
    | "ideationVoteTrigger"
    | "profileFieldTrigger"
    | "staleDiscussionTrigger"
    | "staleCollectionTrigger"
    | "lastActiveDiscussionTrigger"
    | "timeSinceUserRegistrationTrigger";
export type AutomationRuleActionType =
    | "addRemoveRoleAction"
    | "addTagAction"
    | "addToCollectionAction"
    | "bumpDiscussionAction"
    | "categoryFollowAction"
    | "changeIdeationStatusAction"
    | "closeDiscussionAction"
    | "moveToCategoryAction"
    | "removeDiscussionFromCollectionAction"
    | "removeDiscussionFromTriggerCollectionAction";

export type AutomationRuleStatusType = "active" | "inactive" | "deleted";
export type AutomationRuleDispatchStatusType = "success" | "queued" | "running" | "failed" | "warning";
export type AutomationRuleDispatchType = "manual" | "initial" | "triggered";

export interface IAutomationRuleTrigger {
    triggerType: AutomationRuleTriggerType;
    name: string;
    triggerActions: AutomationRuleActionType[];
    schema?: JsonSchema<any>;
}

export interface IAutomationRuleAction {
    actionType: AutomationRuleActionType;
    name: string;
    actionTriggers: AutomationRuleTriggerType[];
    schema?: JsonSchema<any>;
}

export interface IAutomationRulesCatalog {
    triggers: Record<AutomationRuleTriggerType, IAutomationRuleTrigger>;
    actions: Record<AutomationRuleActionType, IAutomationRuleAction>;
}

export interface IAutomationRule {
    automationRuleID: number;
    automationRuleRevisionID: number;
    name: string;
    dateInserted: string;
    insertUserID: number;
    updateUserID: number;
    dateUpdated: string;
    dateLastRun: string;
    status: AutomationRuleStatusType;
    recentDispatch: Pick<IAutomationRuleDispatch, "dispatchStatus">;
    trigger: {
        triggerType: AutomationRuleTriggerType;
        triggerName: string;
        triggerValue: Record<string, any>;
    };
    action: {
        actionType: AutomationRuleActionType;
        actionName: string;
        actionValue: Record<string, any>;
    };
}

export interface IAutomationRuleDispatch {
    automationRuleDispatchUUID: string;
    insertUser: IUserFragment;
    updateUser: IUserFragment;
    dispatchUser: IUserFragment;
    dateDispatched: string;
    dateFinished: string;
    dispatchType: AutomationRuleDispatchType;
    dispatchStatus: AutomationRuleDispatchStatusType;
    automationRule: IAutomationRule;
    trigger: IAutomationRule["trigger"];
    action: IAutomationRule["action"];
    affectedRows: {
        post?: number;
        user?: number;
    };
}

export interface IGetAutomationRuleDispatchesParams {
    automationRuleID?: IAutomationRule["automationRuleID"];
    automationRuleDispatchUUID?: IAutomationRuleDispatch["automationRuleDispatchUUID"];
    limit?: number;
    page?: number;
    actionType?: AutomationRuleActionType;
    dateUpdated?: string;
    dateFinished?: string;
    dispatchStatus?: AutomationRuleDispatchStatusType[];
}

export interface IAutomationRulesFilterValues {
    trigger?: AutomationRuleTriggerType;
    action?: AutomationRuleActionType;
    status?: AutomationRuleStatusType;
}

export type AutomationRuleFormValues = {
    trigger?: {
        triggerType: AutomationRuleTriggerType | "";
        triggerValue: Record<string, any>;
    };
    action?: {
        actionType: AutomationRuleActionType | "";
        actionValue: Record<string, any>;
    };
};

export type AddEditAutomationRuleParams = AutomationRuleFormValues & {
    name?: IAutomationRule["name"];
    automationRuleID?: IAutomationRule["automationRuleID"];
};

export type AutomationRulePreviewContentType = "users" | "posts";

export type AutomationRulesAdditionalDataQuery = {
    categoriesQuery?: IGetCategoryListParams;
    tagsQuery?: IGetTagsParams;
};

export interface IAutomationRulesHistoryFilter
    extends Omit<IGetAutomationRuleDispatchesParams, "limit" | "page" | "dateUpdated" | "dateFinished"> {
    dateUpdated?: { start?: string; end?: string };
    dateFinished?: { start?: string; end?: string };
}
