/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { IGetUsersQueryParams } from "@dashboard/users/userManagement/UserManagement.hooks";
import { IUserFragment } from "@library/@types/api/users";
import { IGetCategoryListParams } from "@library/categoriesWidget/CategoryList.hooks";
import { IGetCollectionResourcesParams } from "@library/featuredCollections/collectionsHooks";
import { IGetTagsParams } from "@library/features/tags/TagsHooks";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { IGetReportsForAutomationRulesParams } from "@dashboard/automationRules/preview/AutomationRulesPreviewReportedPostsContent";

export type AutomationRuleTriggerType =
    | "discussionReachesScoreTrigger"
    | "emailDomainTrigger"
    | "ideationVoteTrigger"
    | "lastActiveDiscussionTrigger"
    | "postSentimentTrigger"
    | "profileFieldTrigger"
    | "reportPostTrigger"
    | "staleDiscussionTrigger"
    | "staleCollectionTrigger"
    | "timeSinceUserRegistrationTrigger"
    | "unAnsweredQuestionTrigger";
export type AutomationRuleActionType =
    | "addDiscussionToCollectionAction"
    | "addRemoveRoleAction"
    | "addTagAction"
    | "bumpDiscussionAction"
    | "categoryFollowAction"
    | "changeIdeationStatusAction"
    | "closeDiscussionAction"
    | "createEscalationAction"
    | "escalateGithubIssueAction"
    | "escalateToZendeskAction"
    | "escalateSalesforceCaseAction"
    | "escalateSalesforceLeadAction"
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
    contentType: "users" | "posts";
    schema?: JsonSchema<any>;
}

export interface IAutomationRuleAction {
    actionType: AutomationRuleActionType;
    name: string;
    actionTriggers: AutomationRuleTriggerType[];
    contentType: "users" | "posts";
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
    additionalSettings?: {
        triggerValue?: Record<string, any>;
        actionValue?: Record<string, any>;
    };
};

export type AddEditAutomationRuleParams = AutomationRuleFormValues & {
    name?: IAutomationRule["name"];
    automationRuleID?: IAutomationRule["automationRuleID"];
};

export type AutomationRulesAdditionalDataQuery = {
    categoriesQuery?: IGetCategoryListParams;
    tagsQuery?: IGetTagsParams;
    usersQuery?: IGetUsersQueryParams;
};

export interface IAutomationRulesHistoryFilter
    extends Omit<IGetAutomationRuleDispatchesParams, "limit" | "page" | "dateUpdated" | "dateFinished"> {
    dateUpdated?: { start?: string; end?: string };
    dateFinished?: { start?: string; end?: string };
}

export type AutomationRulePreviewQuery =
    | IGetUsersQueryParams
    | IGetDiscussionListParams
    | IGetCollectionResourcesParams
    | IGetReportsForAutomationRulesParams
    | CommentsApi.IndexParams;

export type AutomationRulesPreviewContent = Record<
    string,
    {
        component: React.ComponentType<{
            query: AutomationRulePreviewQuery;
            fromStatusToggle?: boolean;
            onPreviewContentLoad?: (emptyResult: boolean) => void;
        }>;
        queryBuilder: (apiValues: AddEditAutomationRuleParams) => AutomationRulePreviewQuery;
    }
>;
