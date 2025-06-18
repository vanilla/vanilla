/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { STORY_DATE_ENDS, STORY_DATE_STARTS } from "@library/storybook/storyData";
import {
    IAutomationRule,
    IAutomationRuleAction,
    IAutomationRuleDispatch,
    IAutomationRuleTrigger,
    IAutomationRulesCatalog,
} from "@dashboard/automationRules/AutomationRules.types";
import { CategoryDisplayAs } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { CreatableFieldFormType, CreatableFieldVisibility } from "@dashboard/userProfiles/types/UserProfiles.types";

export const mockRecipesList: IAutomationRule[] = [
    {
        automationRuleID: 1,
        name: "Test Automation Rule 1",
        automationRuleRevisionID: 1,
        dateInserted: STORY_DATE_STARTS,
        insertUserID: 2,
        updateUserID: 2,
        dateUpdated: STORY_DATE_STARTS,
        dateLastRun: STORY_DATE_ENDS,
        status: "active",
        recentDispatch: {
            dispatchStatus: "success",
        },
        trigger: {
            triggerType: "staleDiscussionTrigger",
            triggerName: "trigger1Name",
            triggerValue: {},
        },
        action: {
            actionType: "addTagAction",
            actionName: "action1Name",
            actionValue: {},
        },
    },
    {
        automationRuleID: 2,
        name: "Test Automation Rule 2",
        automationRuleRevisionID: 2,
        dateInserted: STORY_DATE_STARTS,
        insertUserID: 2,
        updateUserID: 2,
        dateUpdated: STORY_DATE_STARTS,
        dateLastRun: STORY_DATE_ENDS,
        status: "inactive",
        recentDispatch: {
            dispatchStatus: "queued",
        },
        trigger: {
            triggerType: "staleDiscussionTrigger",
            triggerName: "trigger2Name",
            triggerValue: {
                applyToNewContentOnly: false,
                triggerTimeDelay: { length: 4, unit: "hour" },
                triggerTimeLookBackLimit: { length: 2, unit: "day" },
                postType: ["discussion"],
            },
        },
        action: {
            actionType: "closeDiscussionAction",
            actionName: "action2Name",
            actionValue: {},
        },
    },
    {
        automationRuleID: 3,
        name: "Test Automation Rule 3",
        automationRuleRevisionID: 3,
        dateInserted: STORY_DATE_STARTS,
        insertUserID: 2,
        updateUserID: 2,
        dateUpdated: STORY_DATE_STARTS,
        dateLastRun: STORY_DATE_ENDS,
        status: "active",
        recentDispatch: {
            dispatchStatus: "queued",
        },
        trigger: {
            triggerType: "profileFieldTrigger",
            triggerName: "trigger3Name",
            triggerValue: {
                profileField: {
                    test_text_profileField: "test_text",
                },
            },
        },
        action: {
            actionType: "addRemoveRoleAction",
            actionName: "action3Name",
            actionValue: {
                addRoleID: 2,
                removeRoleID: 3,
            },
        },
    },
    {
        automationRuleID: 4,
        name: "Test Automation Rule 4",
        automationRuleRevisionID: 4,
        dateInserted: STORY_DATE_STARTS,
        insertUserID: 2,
        updateUserID: 2,
        dateUpdated: STORY_DATE_STARTS,
        dateLastRun: STORY_DATE_ENDS,
        status: "inactive",
        recentDispatch: {
            dispatchStatus: "queued",
        },
        trigger: {
            triggerType: "emailDomainTrigger",
            triggerName: "trigger4Name",
            triggerValue: {
                emailDomain: "example.com, test.com",
            },
        },
        action: {
            actionType: "categoryFollowAction",
            actionName: "action4Name",
            actionValue: {
                categoryID: [1, 2],
            },
        },
    },
    {
        automationRuleID: 5,
        name: "Test Automation Rule 5",
        automationRuleRevisionID: 5,
        dateInserted: STORY_DATE_STARTS,
        insertUserID: 2,
        updateUserID: 2,
        dateUpdated: STORY_DATE_STARTS,
        dateLastRun: STORY_DATE_ENDS,
        status: "inactive",
        recentDispatch: {
            dispatchStatus: "queued",
        },
        trigger: {
            triggerType: "staleDiscussionTrigger",
            triggerName: "trigger5Name",
            triggerValue: {
                triggerTimeDelay: { length: 1, unit: "day" },
                postType: ["discussion", "question"],
            },
        },
        action: {
            actionType: "moveToCategoryAction",
            actionName: "action5Name",
            actionValue: {
                categoryID: [1, 2],
            },
        },
    },
    {
        automationRuleID: 6,
        name: "Test Escalation Rule",
        automationRuleRevisionID: 6,
        dateInserted: STORY_DATE_STARTS,
        insertUserID: 2,
        updateUserID: 2,
        dateUpdated: STORY_DATE_STARTS,
        dateLastRun: STORY_DATE_ENDS,
        status: "active",
        recentDispatch: {
            dispatchStatus: "success",
        },
        trigger: {
            triggerType: "staleDiscussionTrigger",
            triggerName: "triggerName",
            triggerValue: {
                triggerTimeDelay: { length: 1, unit: "day" },
                postType: ["discussion", "question"],
            },
        },
        action: {
            actionType: "createEscalationAction",
            actionName: "action6Name",
            actionValue: {},
        },
    },
];

export const mockDispatches: IAutomationRuleDispatch[] = [
    {
        automationRuleDispatchUUID: `some_uuid_${mockRecipesList[0].automationRuleID}`,
        dateDispatched: mockRecipesList[0].dateLastRun,
        dateFinished: mockRecipesList[0].dateLastRun,
        dispatchStatus: "success",
        automationRule: {
            ...mockRecipesList[0],
            insertUser: { userID: 2, name: "test_user", dateLastActive: mockRecipesList[0].dateInserted, photoUrl: "" },
            updateUser: {
                userID: 2,
                name: "test_user",
                dateLastActive: mockRecipesList[0].dateInserted,
                photoUrl: "#",
            },
        },
        trigger: mockRecipesList[0].trigger,
        action: mockRecipesList[0].action,
        dispatchType: "triggered",
        dispatchUser: { userID: 2, name: "test_user", dateLastActive: mockRecipesList[0].dateInserted, photoUrl: "" },
        affectedRows: {
            user: 1,
        },
    },
    {
        automationRuleDispatchUUID: `some_uuid_${mockRecipesList[1].automationRuleID}`,
        dateDispatched: mockRecipesList[1].dateLastRun,
        dateFinished: mockRecipesList[1].dateLastRun,
        dispatchStatus: "queued",
        automationRule: {
            ...mockRecipesList[1],
            insertUser: { userID: 2, name: "test_user", dateLastActive: mockRecipesList[0].dateInserted, photoUrl: "" },
            updateUser: {
                userID: 2,
                name: "test_user",
                dateLastActive: mockRecipesList[0].dateInserted,
                photoUrl: "#",
            },
        },
        trigger: mockRecipesList[1].trigger,
        action: mockRecipesList[1].action,
        dispatchType: "manual",
        dispatchUser: { userID: 2, name: "test_user", dateLastActive: mockRecipesList[0].dateInserted, photoUrl: "" },
        affectedRows: {
            user: 1,
        },
    },
];

const triggerDelaySchemaProperties = {
    triggerTimeDelay: {
        type: "object",
        required: true,
        "x-control": {
            description: "Set the duration after which the rule will trigger.  Whole numbers only.",
            label: "Trigger Delay",
            inputType: "timeDuration",
            placeholder: "",
            tooltip:
                "Set the duration something needs to exist and meet the rule criteria for prior to the the rule triggering and acting upon it",
            supportedUnits: ["hour", "day", "week", "year"],
        },
        properties: {
            length: {
                type: "string",
            },
            unit: {
                type: "string",
            },
        },
    },
};

const additionalSettingsSchemaProperties = {
    additionalSettings: {
        applyToNewContentOnly: {
            type: "boolean",
            default: false,
            "x-control": {
                description:
                    "When enabled, this rule will only be applied to new content that meets the trigger criteria.",
                label: "Apply to new content only",
                inputType: "checkBox",
            },
        },
        triggerTimeLookBackLimit: {
            type: "object",
            "x-control": {
                description: "Do not apply the rule to content that is older than this.",
                label: "Look-back Limit",
                inputType: "timeDuration",
                placeholder: "",
                tooltip: "",
                supportedUnits: ["hour", "day", "week", "year"],
                conditions: [
                    {
                        field: "additionalSettings.triggerValue.applyToNewContentOnly",
                        type: "boolean",
                        const: false,
                    },
                ],
            },
            properties: {
                length: {
                    type: "string",
                },
                unit: {
                    type: "string",
                },
            },
        },
    },
};

export const mockAutomationRulesCatalog: IAutomationRulesCatalog = {
    triggers: {
        emailDomainTrigger: {
            triggerType: "emailDomainTrigger",
            name: "Email Domain Trigger Name",
            triggerActions: ["addRemoveRoleAction", "categoryFollowAction", "inviteToGroupAction"],
            contentType: "users",
            schema: {
                type: "object",
                properties: {
                    emailDomain: {
                        type: "string",
                        required: true,
                        "x-control": {
                            description: "Enter one or more comma-separated email domains",
                            label: "Email Domain",
                            inputType: "textBox",
                            placeholder: "",
                            type: "string",
                            tooltip: "",
                        },
                    },
                },
                required: ["emailDomain"],
            },
        } as IAutomationRuleTrigger,
        profileFieldTrigger: {
            triggerType: "profileFieldTrigger",
            name: "ProfileField Trigger Name",
            triggerActions: ["addRemoveRoleAction", "inviteToGroupAction"],
            contentType: "users",
            schema: {
                type: "object",
                properties: {
                    profileField: {
                        type: "string",
                        required: true,
                        "x-control": {
                            description: "Select a profile field",
                            label: "Profile Field",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                api: {
                                    searchUrl: "/api/v2/profile-fields?enabled=true",
                                    singleUrl: "",
                                    valueKey: "apiName",
                                    labelKey: "label",
                                    extraLabelKey: null,
                                },
                            },
                            multiple: false,
                            tooltip: "",
                        },
                    },
                },
                required: ["profileField"],
            },
        } as IAutomationRuleTrigger,
        staleDiscussionTrigger: {
            triggerType: "staleDiscussionTrigger",
            name: "A certain amount of time has passed since a post has been created but has not received any comments",
            triggerActions: [
                "addDiscussionToCollectionAction",
                "addTagAction",
                "bumpDiscussionAction",
                "closeDiscussionAction",
                "moveToCategoryAction",
                "removeDiscussionFromCollectionAction",
                "createEscalationAction",
            ],
            contentType: "posts",
            schema: {
                type: "object",
                properties: {
                    ...triggerDelaySchemaProperties,
                    postType: {
                        type: "array",
                        items: {
                            type: "string",
                        },
                        required: true,
                        default: ["discussion", "question"],
                        enum: ["discussion", "question"],
                        "x-control": {
                            description: "Select a post type.",
                            label: "Post Type",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                staticOptions: {
                                    discussion: "Discussion",
                                    question: "Question",
                                },
                            },
                            multiple: true,
                            tooltip: "",
                        },
                    },
                    ...additionalSettingsSchemaProperties,
                },
                required: ["triggerTimeDelay", "postType"],
            },
        },
        lastActiveDiscussionTrigger: {
            triggerType: "lastActiveDiscussionTrigger",
            name: "A certain amount of time has passed since a post has been active.",
            triggerActions: [
                "addDiscussionToCollectionAction",
                "addTagAction",
                "bumpDiscussionAction",
                "closeDiscussionAction",
                "moveToCategoryAction",
                "removeDiscussionFromCollectionAction",
            ],
            contentType: "posts",
            schema: {
                type: "object",
                properties: {
                    ...triggerDelaySchemaProperties,
                    postType: {
                        type: "array",
                        items: {
                            type: "string",
                        },
                        default: ["discussion", "question"],
                        enum: ["discussion", "question"],
                        required: true,
                        "x-control": {
                            description: "Select a post type.",
                            label: "Post Type",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                staticOptions: {
                                    discussion: "Discussion",
                                    question: "Question",
                                },
                            },
                            multiple: true,
                            tooltip: "",
                        },
                    },
                    ...additionalSettingsSchemaProperties,
                },
                required: ["triggerTimeDelay", "postType"],
            },
        },
        staleCollectionTrigger: {
            triggerType: "staleCollectionTrigger",
            name: "A certain amount of time has passed since a post added to a collection",
            triggerActions: ["removeDiscussionFromTriggerCollectionAction"],
            contentType: "posts",
            schema: {
                type: "object",
                properties: triggerDelaySchemaProperties,
                required: ["triggerTimeDelay"],
            },
        },
        timeSinceUserRegistrationTrigger: {
            triggerType: "timeSinceUserRegistrationTrigger",
            name: "A certain amount of time has passed since a user registered",
            triggerActions: ["addRemoveRoleAction", "inviteToGroupAction"],
            contentType: "users",
            schema: {
                type: "object",
                properties: triggerDelaySchemaProperties,
                required: ["triggerTimeDelay"],
            },
        },
        timeSinceLastActiveTrigger: {
            triggerType: "timeSinceLastActiveTrigger",
            name: "A certain amount of time has passed since a user was last active",
            triggerActions: ["addRemoveRoleAction"],
            contentType: "users",
            schema: {
                type: "object",
                properties: triggerDelaySchemaProperties,
                required: ["triggerTimeDelay"],
            },
        },
        ideationVoteTrigger: {
            triggerType: "ideationVoteTrigger",
            name: "An idea receives a certain number of votes",
            triggerActions: ["changeIdeationStatusAction"],
            contentType: "posts",
            schema: {
                type: "object",
                properties: {
                    score: {
                        type: "integer",
                        required: true,
                        "x-control": {
                            description:
                                "Enter the number of votes that a idea should receive to trigger this automation rule. Whole numbers only.",
                            label: "Number of votes",
                            inputType: "textBox",
                            placeholder: "",
                            type: "number",
                            tooltip: "",
                        },
                    },
                },
                required: ["score"],
            },
        },
        discussionReachesScoreTrigger: {
            triggerType: "discussionReachesScoreTrigger",
            name: "A discussion reaches a certain amount of points",
            triggerActions: ["addDiscussionToCollectionAction", "bumpDiscussionAction"],
            contentType: "posts",
            schema: {
                type: "object",
                properties: {
                    points: {
                        type: "integer",
                        "x-control": {
                            description:
                                "Enter the number of points a discussion should receive to trigger this automation rule. Whole numbers only.",
                            label: "Number of points",
                            inputType: "textBox",
                            placeholder: "",
                            type: "number",
                            tooltip: "",
                        },
                    },
                    postType: {
                        type: "array",
                        items: {
                            type: "string",
                        },
                        default: ["discussion", "question"],
                        enum: ["discussion", "question"],
                        "x-control": {
                            description: "Select a post type.",
                            label: "Post Type",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                staticOptions: {
                                    discussion: "Discussion",
                                    question: "Question",
                                },
                            },
                            multiple: true,
                            tooltip: "",
                        },
                    },
                },
                required: ["points"],
            },
        },
        reportPostTrigger: {
            triggerType: "reportPostTrigger",
            name: "Post Received reports",
            triggerActions: [
                "createEscalationAction",
                "escalateGithubIssueAction",
                "escalateToZendeskAction",
                "escalateToJiraAction",
            ],
            contentType: "posts",
            schema: { properties: {} },
        },
        unAnsweredQuestionTrigger: {
            triggerType: "unAnsweredQuestionTrigger",
            name: "Time since question had no answers",
            triggerActions: [
                "addDiscussionToCollectionAction",
                "escalateToZendeskAction",
                "createEscalationAction",
                "escalateToJiraAction",
            ],
            contentType: "posts",
            schema: { properties: {} },
        },
        postSentimentTrigger: {
            triggerType: "postSentimentTrigger",
            name: "Sentiment of the post",
            triggerActions: [],
            contentType: "posts",
            schema: { properties: {} },
        },
    },
    actions: {
        categoryFollowAction: {
            actionType: "categoryFollowAction",
            name: "Category Follow Action Name",
            actionTriggers: ["emailDomainTrigger", "profileFieldTrigger"],
            contentType: "users",
            schema: {
                type: "object",
                properties: {
                    categoryID: {
                        type: "array",
                        items: {
                            type: "integer",
                        },
                        "x-control": {
                            description: "Select one or more categories to follow",
                            label: "Category to Follow",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                api: {
                                    searchUrl: "/api/v2/categories",
                                    singleUrl: "/api/v2/categories/%s",
                                    valueKey: "categoryID",
                                    labelKey: "name",
                                    extraLabelKey: null,
                                },
                            },
                            multiple: true,
                            tooltip: "",
                        },
                    },
                },
                required: ["categoryID"],
            },
        } as IAutomationRuleAction,
        moveToCategoryAction: {
            actionType: "moveToCategoryAction",
            name: "Move to a specific category",
            actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
            contentType: "posts",
            schema: {
                type: "object",
                properties: {
                    categoryID: {
                        type: "array",
                        items: {
                            type: "integer",
                        },
                        "x-control": {
                            description: "Select a category",
                            label: "Category to move to",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                api: {
                                    searchUrl: "/api/v2/categories/search?query=%s&limit=30",
                                    singleUrl: "/api/v2/categories/%s",
                                    valueKey: "categoryID",
                                    labelKey: "name",
                                },
                            },
                            multiple: false,
                            tooltip: "",
                        },
                    },
                },
                required: ["categoryID"],
            },
        },
        closeDiscussionAction: {
            actionType: "closeDiscussionAction",
            name: "Close the discussion",
            actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
            contentType: "posts",
        },
        bumpDiscussionAction: {
            actionType: "bumpDiscussionAction",
            name: "Bump the discussion",
            actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
            contentType: "posts",
        },
        addTagAction: {
            actionType: "addTagAction",
            name: "Add a tag",
            actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
            contentType: "posts",
            schema: {
                type: "object",
                properties: {
                    tagID: {
                        type: "array",
                        items: {
                            type: "integer",
                        },
                        "x-control": {
                            description: "Select one or more tags",
                            label: "Tags to add",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                api: {
                                    searchUrl: "/api/v2/tags?type=User&limit=30&query=%s",
                                    singleUrl: "/api/v2/tags/%s",
                                    valueKey: "tagID",
                                    labelKey: "name",
                                },
                            },
                            multiple: true,
                            tooltip: "",
                        },
                    },
                },
                required: ["tagID"],
            },
        },
        addDiscussionToCollectionAction: {
            actionType: "addDiscussionToCollectionAction",
            name: "Add Discussion To Collection",
            actionTriggers: ["lastActiveDiscussionTrigger", "staleDiscussionTrigger"],
            contentType: "posts",
            schema: {
                type: "object",
                properties: {
                    collectionID: {
                        type: "array",
                        items: {
                            type: "integer",
                        },
                        "x-control": {
                            description: "Select one or more collections.",
                            label: "Collection to add to",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                api: {
                                    searchUrl: "/api/v2/collections",
                                    singleUrl: "/api/v2/collections/%s",
                                    valueKey: "collectionID",
                                    labelKey: "name",
                                },
                            },
                            multiple: true,
                            tooltip: "",
                        },
                    },
                },
                required: ["collectionID"],
            },
        },
        removeDiscussionFromCollectionAction: {
            actionType: "removeDiscussionFromCollectionAction",
            name: "Remove from collection",
            actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
            contentType: "posts",
            schema: {
                type: "object",
                properties: {
                    collectionID: {
                        type: "array",
                        items: {
                            type: "integer",
                        },
                        "x-control": {
                            description: "Select one or more collections.",
                            label: "Collection to remove from",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                api: {
                                    searchUrl: "/api/v2/collections",
                                    singleUrl: "/api/v2/collections/%s",
                                    valueKey: "collectionID",
                                    labelKey: "name",
                                },
                            },
                            multiple: true,
                            tooltip: "",
                        },
                    },
                },
                required: ["collectionID"],
            },
        },
        removeDiscussionFromTriggerCollectionAction: {
            actionType: "removeDiscussionFromTriggerCollectionAction",
            name: "Remove from trigger collection",
            actionTriggers: ["staleCollectionTrigger"],
            contentType: "posts",
        },
        addRemoveRoleAction: {
            actionType: "addRemoveRoleAction",
            name: "Role Action Name",
            actionTriggers: ["profileFieldTrigger", "timeSinceLastActiveTrigger"],
            contentType: "users",
            schema: {
                type: "object",
                properties: {
                    addRoleID: {
                        type: "string",
                        "x-control": {
                            description: "Select a role to be assigned",
                            label: "Assign Role",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                api: {
                                    searchUrl: "/api/v2/roles",
                                    singleUrl: "/api/v2/roles/%s",
                                    valueKey: "roleID",
                                    labelKey: "name",
                                    extraLabelKey: null,
                                },
                            },
                            multiple: false,
                            tooltip: "",
                        },
                    },
                    removeRoleID: {
                        type: "string",
                        "x-control": {
                            description: "Select a role to be removed",
                            label: "Remove Role (optional)",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                api: {
                                    searchUrl: "/api/v2/roles",
                                    singleUrl: "/api/v2/roles/%s",
                                    valueKey: "roleID",
                                    labelKey: "name",
                                    extraLabelKey: null,
                                },
                            },
                            multiple: false,
                            tooltip: "",
                        },
                    },
                },
                required: ["addRoleID"],
            },
        } as IAutomationRuleAction,
        inviteToGroupAction: {
            actionType: "inviteToGroupAction",
            name: "Invite to group",
            actionTriggers: ["profileFieldTrigger"],
            contentType: "users",
            schema: {
                type: "object",
                properties: {
                    groupIDs: {
                        type: "string",
                        "x-control": {
                            description: "",
                            label: "Group ID",
                            inputType: "dropDown",
                            placeholder: "",
                            choices: {
                                api: {
                                    searchUrl: "/api/v2/groups",
                                    singleUrl: "/api/v2/groups/%s",
                                    valueKey: "groupID",
                                    labelKey: "name",
                                    extraLabelKey: null,
                                },
                            },
                            multiple: true,
                            tooltip: "",
                        },
                    },
                },
                required: ["groupID"],
            },
        } as IAutomationRuleAction,
        changeIdeationStatusAction: {
            actionType: "changeIdeationStatusAction",
            name: "Change the ideation status",
            actionTriggers: ["ideationVoteTrigger"],
            contentType: "posts",
        },
        createEscalationAction: {
            actionType: "createEscalationAction",
            name: "Create Escalation",
            actionTriggers: ["reportPostTrigger"],
            contentType: "posts",
        },
        escalateToZendeskAction: {
            actionType: "escalateToZendeskAction",
            name: "Escalate to Zendesk",
            actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
            contentType: "posts",
        },
        escalateGithubIssueAction: {
            actionType: "escalateGithubIssueAction",
            name: "Escalate a post to Github",
            actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
            contentType: "posts",
        },
        escalateSalesforceCaseAction: {
            actionType: "escalateSalesforceCaseAction",
            name: "Create a case in Salesforce",
            actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
            contentType: "posts",
        },
        escalateSalesforceLeadAction: {
            actionType: "escalateSalesforceLeadAction",
            name: "Create a lead in Salesforce",
            actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
            contentType: "posts",
        },
        escalateToJiraAction: {
            actionType: "escalateToJiraAction",
            name: "Escalate to Jira",
            actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
            contentType: "posts",
            dynamicSchemaParams: ["someTestActionValue"],
            schema: {
                type: "object",
                properties: {
                    someTestActionValue: {
                        type: "string",
                        description: "Some description.",
                        required: true,
                        "x-control": {
                            inputType: "dropDown",
                            label: "Some Test ActionValue",
                            choices: {
                                staticOptions: {
                                    option1: "Option1",
                                    option2: "Option2",
                                },
                            },
                        },
                    },
                },
            },
        },
    },
};

export const mockActionwithDynamicSchema = {
    actionType: "escalateToJiraAction",
    name: "Escalate to Jira",
    actionTriggers: ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
    contentType: "posts",
    dynamicSchemaParams: ["someTestActionValue"],
    schema: {
        type: "object",
        properties: {
            someTestActionValue: {
                type: "string",
                description: "Some description.",
                required: true,
                "x-control": {
                    inputType: "dropDown",
                    label: "Some Test ActionValue",
                    choices: {
                        staticOptions: {
                            option1: "Option1",
                            option2: "Option2",
                        },
                    },
                },
            },
        },
    },
    dynamicSchema: {
        type: "object",
        properties: {
            customField: {
                type: "string",
                default: "dynamicSchema_defaultValue",
                required: true,
                disabled: false,
                "x-control": {
                    description: "",
                    label: "Custom field from dynamic schema",
                    inputType: "textBox",
                    type: "string",
                },
            },
        },
        required: ["customField"],
    },
};

export const mockCategoriesData = [
    {
        categoryID: 1,
        name: "Mock Category 1",
        url: "/mock-category",
        description: "mock category description",
        parentCategoryID: null,
        customPermissions: false,
        isArchived: false,
        urlcode: "/",
        displayAs: CategoryDisplayAs.DEFAULT,
        countCategories: 1,
        countDiscussions: 10,
        countComments: 10,
        countAllDiscussions: 10,
        countAllComments: 10,
        followed: false,
        depth: 1,
        children: [],
        dateInserted: new Date("2023-06-16").toUTCString(),
    },
    {
        categoryID: 2,
        name: "Mock Category 2",
        url: "/mock-category-2",
        description: "mock category 2 description",
        parentCategoryID: null,
        customPermissions: false,
        isArchived: false,
        urlcode: "/",
        displayAs: CategoryDisplayAs.DEFAULT,
        countCategories: 1,
        countDiscussions: 10,
        countComments: 10,
        countAllDiscussions: 10,
        countAllComments: 10,
        followed: false,
        depth: 1,
        children: [],
        dateInserted: new Date("2023-06-16").toUTCString(),
    },
];

export const mockProfileField = ProfileFieldsFixtures.mockProfileField(CreatableFieldFormType.TEXT, {
    apiName: "test_text_profileField",
    displayOptions: {
        search: false,
    },
    enabled: true,
    visibility: CreatableFieldVisibility.PUBLIC,
});
