/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { AutomationRuleFormValues } from "@dashboard/automationRules/AutomationRules.types";
import { useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { TokenItem } from "@library/metas/TokenItem";
import { ProfileFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";
import Message from "@library/messages/Message";
import { Icon } from "@vanilla/icons";
import { isTimeBasedTrigger } from "@dashboard/automationRules/AutomationRules.utils";
import AutomationRulesSummaryQuery from "@dashboard/automationRules/AutomationRulesSummaryQuery";
import Translate from "@library/content/Translate";
import { IDropdownControl } from "packages/vanilla-json-schema-forms/src/types";

interface IAutomationRulesSummaryProps {
    formValues: AutomationRuleFormValues;
    isLoading?: boolean;
    isEditing?: boolean;
    isRuleRunning?: boolean;
}

export default function AutomationRulesSummary(props: IAutomationRulesSummaryProps) {
    const { formValues, isEditing, isRuleRunning } = props;
    const classes = automationRulesClasses();
    const {
        automationRulesCatalog,
        rolesByID,
        profileFields,
        tags,
        collections,
        categories,
        ideaStatusesByID,
        users,
        optionalDataSources,
    } = useAutomationRules();

    const reportReasonsFromCatalog = (
        automationRulesCatalog?.triggers?.reportPostTrigger?.schema?.properties?.reportReasonID?.[
            "x-control"
        ] as IDropdownControl
    )?.choices?.staticOptions as Record<string, string>;

    const sentimentsFromCatalog = (
        automationRulesCatalog?.triggers?.postSentimentTrigger?.schema?.properties?.sentiment?.[
            "x-control"
        ] as IDropdownControl
    )?.choices?.staticOptions as Record<string, string>;

    const isLoading = isEditing
        ? props.isLoading ||
          !rolesByID ||
          !profileFields ||
          !categories ||
          !tags ||
          !collections ||
          !users ||
          (Object.keys(automationRulesCatalog?.triggers ?? {}).includes("ideationVoteTrigger") && !ideaStatusesByID)
        : false;

    const summaryMessages = {
        // Add new triggers, alphabetically.
        triggers: {
            discussionReachesScoreTrigger: t("Post has received at least"),
            emailDomainTrigger: t("A user registers or logs in with email domain:"),
            ideationVoteTrigger: t("An idea has received"),
            lastActiveDiscussionTrigger: t("A post has not had any activity"),
            postSentimentTrigger: t("A post"),
            profileFieldTrigger: t("A user registers or updates a profile field:"),
            reportPostTrigger: t("A post has received"),
            staleDiscussionTrigger: t("A post"),
            staleCollectionTrigger: t("A post has been added to a collection"),
            timeSinceUserRegistrationTrigger: t("A user has been registered"),
            unAnsweredQuestionTrigger: t("A question"),
        },
        // Add new actions, alphabetically.
        actions: {
            addDiscussionToCollectionAction: t("Add to collection:"),
            addRemoveRoleAction: t("Assign role:"),
            addTagAction: t("Add tags:"),
            bumpDiscussionAction: t("Bump the post"),
            categoryFollowAction: t("Follow categories:"),
            changeIdeationStatusAction: t("Change the status of the idea to"),
            closeDiscussionAction: t("Close the post"),
            createEscalationAction: t("Escalate it"),
            escalateGithubIssueAction: t("Escalate to GitHub"),
            escalateToZendeskAction: t("Escalate to Zendesk"),
            escalateSalesforceCaseAction: t("Escalate a case to Salesforce"),
            escalateSalesforceLeadAction: t("Escalate a lead to Salesforce"),
            moveToCategoryAction: t("Move to category:"),
            removeDiscussionFromCollectionAction: t("Remove from collection:"),
            removeDiscussionFromTriggerCollectionAction: t("Remove from collection:"),
            inviteToGroupAction: t("Invite to group:"),
        },
    };

    // trigger values
    const isProfileFieldTrigger = formValues.trigger?.triggerType === "profileFieldTrigger";
    const profileFieldValue = formValues.trigger?.triggerValue?.[formValues.trigger?.triggerValue?.profileField];
    const hasProfileFieldValue = profileFieldValue !== undefined;

    const isProfileFieldValueCheckbox =
        hasProfileFieldValue &&
        profileFields?.find((field) => field.apiName === formValues.trigger?.triggerValue?.profileField)?.formType ===
            ProfileFieldFormType.CHECKBOX;

    const isEmailDomainTrigger = formValues.trigger?.triggerType === "emailDomainTrigger";
    const isIdeaVoteTrigger = formValues.trigger?.triggerType === "ideationVoteTrigger";
    const isUnansweredQuestionTrigger = formValues.trigger?.triggerType === "unAnsweredQuestionTrigger";
    const isStaleDiscussionTrigger = formValues.trigger?.triggerType === "staleDiscussionTrigger";
    const isPostSentimentTrigger = formValues.trigger?.triggerType === "postSentimentTrigger";

    const hasCollectionTriggerValue =
        formValues.trigger?.triggerType === "staleCollectionTrigger" &&
        !!formValues.trigger?.triggerValue?.collectionID?.length;

    const collectionTriggerValue = hasCollectionTriggerValue && formValues.trigger?.triggerValue?.collectionID;

    const discussionPointsTriggerValue =
        (formValues.trigger?.triggerType === "discussionReachesScoreTrigger" ||
            formValues.trigger?.triggerType === "ideationVoteTrigger") &&
        formValues.trigger?.triggerValue.score;

    const countReportsTriggerValue =
        formValues.trigger?.triggerType === "reportPostTrigger" && formValues.trigger?.triggerValue?.countReports;
    const reportReasonTriggerValue =
        countReportsTriggerValue &&
        reportReasonsFromCatalog &&
        formValues.trigger?.triggerValue?.reportReasonID?.length > 0 &&
        formValues.trigger?.triggerValue?.reportReasonID;

    const categoryTriggerValue =
        (isStaleDiscussionTrigger ||
            countReportsTriggerValue ||
            isUnansweredQuestionTrigger ||
            isPostSentimentTrigger) &&
        formValues.trigger?.triggerValue?.categoryID?.length > 0 &&
        formValues.trigger?.triggerValue?.categoryID;

    const tagTriggerValue =
        (isStaleDiscussionTrigger || isUnansweredQuestionTrigger) &&
        formValues.trigger?.triggerValue?.tagID?.length > 0 &&
        formValues.trigger?.triggerValue?.tagID;

    const roleTriggerValue =
        isPostSentimentTrigger &&
        formValues.trigger?.triggerValue?.roleID?.length > 0 &&
        formValues.trigger?.triggerValue?.roleID;

    const sentimentTriggerValue =
        isPostSentimentTrigger &&
        sentimentsFromCatalog &&
        formValues.trigger?.triggerValue?.sentiment?.length > 0 &&
        formValues.trigger?.triggerValue?.sentiment;

    // action values
    const hasRoleActionValues = formValues.action?.actionType === "addRemoveRoleAction" && rolesByID;

    const hasCategoryActionValues = Boolean(
        (formValues.action?.actionType === "categoryFollowAction" &&
            !!formValues.action.actionValue?.categoryID?.length) ||
            (formValues.action?.actionType === "moveToCategoryAction" && formValues.action.actionValue?.categoryID),
    );
    const categoryActionValue = hasCategoryActionValues && formValues.action?.actionValue?.categoryID;

    const hasTagOrCollectionActionValue =
        (formValues.action?.actionType === "addTagAction" && !!formValues.action?.actionValue?.tagID?.length) ||
        (["addDiscussionToCollectionAction", "removeDiscussionFromCollectionAction"].includes(
            formValues.action?.actionType ?? "",
        ) &&
            !!formValues.action?.actionValue?.collectionID?.length);

    const tagActionValue = hasTagOrCollectionActionValue && formValues.action?.actionValue?.tagID;
    const collectionActionValue = hasTagOrCollectionActionValue && formValues.action?.actionValue?.collectionID;
    const ideaStatusActionValue =
        formValues.action?.actionType === "changeIdeationStatusAction" && formValues.action?.actionValue?.statusID;
    const assignedModeratorActionValue =
        formValues.action?.actionType === "createEscalationAction" &&
        formValues.action?.actionValue?.assignedModeratorID;

    // groups
    const groupsData = optionalDataSources?.["groups"]?.data;
    const groupActionValue =
        formValues.action?.actionType === "inviteToGroupAction" &&
        formValues.action?.actionValue?.groupID?.length > 0 &&
        groupsData &&
        formValues.action?.actionValue?.groupID;

    // time duration values
    const isTimeDurationTriggerType = isTimeBasedTrigger(formValues.trigger?.triggerType, automationRulesCatalog);
    const triggerDelayValue =
        isTimeDurationTriggerType &&
        !Number.isNaN(formValues.trigger?.triggerValue?.triggerTimeDelay?.length) &&
        formValues.trigger?.triggerValue?.triggerTimeDelay?.length > 0
            ? formValues.trigger?.triggerValue?.triggerTimeDelay?.length
            : false;
    const triggerDelayUnit = formValues.trigger?.triggerValue?.triggerTimeDelay?.unit;
    const lookBackLimitValue =
        isTimeDurationTriggerType &&
        !Number.isNaN(formValues.additionalSettings?.triggerValue?.triggerTimeLookBackLimit?.length)
            ? formValues.additionalSettings?.triggerValue?.triggerTimeLookBackLimit?.length
            : false;
    const lookBackLimitUnit = formValues.additionalSettings?.triggerValue?.triggerTimeLookBackLimit?.unit;

    const valueAsTokenItem = (
        value: string | number | string[] | number[],
        valueSeparator: "or" | "and" = "and",
        dataToLookUpForName?: any,
        needleToLookUp?: string,
    ) => {
        return Array.isArray(value) ? (
            value.map((val, index) => (
                <span key={index}>
                    {val !== "" && (
                        <TokenItem className={classes.summaryValue}>
                            {dataToLookUpForName
                                ? Array.isArray(dataToLookUpForName)
                                    ? dataToLookUpForName?.find((entry) => entry[needleToLookUp ?? ""] == val)?.name
                                    : dataToLookUpForName[val]
                                : val}
                        </TokenItem>
                    )}
                    {value.length > 1 && index !== value.length - 1 && (
                        <span className={classes.leftGap(4)}>{`${t(valueSeparator)} `}</span>
                    )}
                </span>
            ))
        ) : (
            <TokenItem>
                {dataToLookUpForName
                    ? dataToLookUpForName?.find((entry) => entry[needleToLookUp ?? ""] === value)?.name
                    : value}
            </TokenItem>
        );
    };

    const summarySectionContent = (
        <>
            <div>
                {formValues.trigger?.triggerType && (
                    <div>
                        <div className={classes.summaryTitle}>{`${t("Trigger")}: `}</div>
                        <span>{`${summaryMessages.triggers[formValues.trigger?.triggerType]} `}</span>
                        {isProfileFieldTrigger && (
                            <>
                                {formValues.trigger?.triggerValue?.profileField &&
                                    valueAsTokenItem(
                                        profileFields?.find(
                                            (field) => field.apiName === formValues.trigger?.triggerValue?.profileField,
                                        )?.label ?? "",
                                    )}
                                {(Array.isArray(profileFieldValue)
                                    ? !!profileFieldValue.length
                                    : hasProfileFieldValue) && (
                                    <>
                                        <span>{` ${t("with")} `}</span>
                                        {Array.isArray(profileFieldValue)
                                            ? valueAsTokenItem(profileFieldValue, "or")
                                            : valueAsTokenItem(
                                                  isProfileFieldValueCheckbox
                                                      ? profileFieldValue
                                                          ? t("Yes")
                                                          : t("No")
                                                      : profileFieldValue,
                                              )}
                                    </>
                                )}
                            </>
                        )}
                        {isEmailDomainTrigger &&
                            formValues.trigger?.triggerValue?.emailDomain &&
                            valueAsTokenItem(formValues.trigger?.triggerValue?.emailDomain.split(","), "or")}
                        {discussionPointsTriggerValue && (
                            <>
                                {valueAsTokenItem(discussionPointsTriggerValue)}
                                {discussionPointsTriggerValue == 1
                                    ? `${isIdeaVoteTrigger ? t("upvote") : t("point")}`
                                    : ` ${isIdeaVoteTrigger ? t("upvotes") : t("points")}`}
                            </>
                        )}
                        {countReportsTriggerValue && (
                            <>
                                {valueAsTokenItem(countReportsTriggerValue)}
                                {countReportsTriggerValue == 1 ? ` ${t("report")}` : ` ${t("reports")}`}
                            </>
                        )}
                        {reportReasonTriggerValue && (
                            <>
                                {` ${t("with reason")} `}
                                {valueAsTokenItem(reportReasonTriggerValue, "or", reportReasonsFromCatalog)}
                            </>
                        )}
                        {sentimentTriggerValue && (
                            <>
                                {` ${t("with")} `}
                                {valueAsTokenItem(sentimentTriggerValue, "or", sentimentsFromCatalog)}
                                {` ${t(" sentiment")} `}
                            </>
                        )}
                        {roleTriggerValue && (
                            <>
                                {` ${t("created by")} `}
                                {valueAsTokenItem(roleTriggerValue, "or", Object.values(rolesByID ?? {}), "roleID")}
                            </>
                        )}
                        {categoryTriggerValue && categories && (
                            <>
                                {` ${t("in")} `}
                                {valueAsTokenItem(categoryTriggerValue, "or", categories, "categoryID")}
                                {` ${t("category")} `}
                            </>
                        )}
                        {tagTriggerValue && (
                            <>
                                {` ${t("with tag")} `}
                                {valueAsTokenItem(tagTriggerValue, "or", tags, "tagID")}
                            </>
                        )}
                        {hasCollectionTriggerValue && (
                            <>{valueAsTokenItem(collectionTriggerValue, "or", collections, "collectionID")}</>
                        )}
                        {isStaleDiscussionTrigger && <>{` ${t("has not received any comments")} `}</>}
                        {isUnansweredQuestionTrigger && <>{` ${t("has been unanswered")} `}</>}
                        {triggerDelayValue && (
                            <>
                                {` ${t("for")}: `}
                                <span className={classes.rightGap(4)}>{valueAsTokenItem(triggerDelayValue)}</span>
                                {valueAsTokenItem(
                                    triggerDelayValue > 1 ? t(`${triggerDelayUnit}s`) : t(triggerDelayUnit),
                                )}
                            </>
                        )}
                        {lookBackLimitValue && !formValues.additionalSettings?.triggerValue?.applyToNewContentOnly && (
                            <div>
                                <Translate
                                    source="Up to <0 /> ago."
                                    c0={
                                        <span>
                                            {lookBackLimitValue}{" "}
                                            {lookBackLimitValue > 1 ? t(`${lookBackLimitUnit}s`) : t(lookBackLimitUnit)}
                                        </span>
                                    }
                                />
                            </div>
                        )}
                    </div>
                )}
            </div>
            {formValues.trigger?.triggerType && formValues.action?.actionType && (
                <div className={classes.verticalGap}></div>
            )}
            <div>
                {formValues.action?.actionType && (
                    <div>
                        <div className={classes.summaryTitle}>{`${t("Action")}: `}</div>
                        <span>{`${summaryMessages.actions[formValues.action?.actionType]} `}</span>
                        <span>
                            {hasRoleActionValues &&
                                formValues.action?.actionValue?.addRoleID &&
                                valueAsTokenItem(rolesByID[formValues.action?.actionValue?.addRoleID]?.name)}
                            {hasRoleActionValues && formValues.action?.actionValue?.removeRoleID && (
                                <>
                                    <span className={classes.normalFontWeight}>{` ${t("and remove role")}: `}</span>
                                    {valueAsTokenItem(rolesByID[formValues.action?.actionValue?.removeRoleID]?.name)}
                                </>
                            )}
                            {hasTagOrCollectionActionValue && (
                                <>
                                    {formValues.action?.actionType === "addTagAction" &&
                                        valueAsTokenItem(tagActionValue, "and", tags, "tagID")}
                                    {[
                                        "addDiscussionToCollectionAction",
                                        "removeDiscussionFromCollectionAction",
                                    ].includes(formValues.action?.actionType ?? "") &&
                                        valueAsTokenItem(collectionActionValue, "and", collections, "collectionID")}
                                </>
                            )}
                            {hasCategoryActionValues &&
                                categories &&
                                valueAsTokenItem(categoryActionValue, "and", categories, "categoryID")}
                            {groupActionValue &&
                                valueAsTokenItem(
                                    groupActionValue,
                                    "and",
                                    optionalDataSources["groups"]?.data,
                                    "groupID",
                                )}
                            {ideaStatusActionValue &&
                                ideaStatusesByID &&
                                valueAsTokenItem(ideaStatusesByID[ideaStatusActionValue])}

                            {formValues.action?.actionType === "createEscalationAction" && (
                                <>
                                    {assignedModeratorActionValue && (
                                        <>
                                            {`${t("and assign to")} `}
                                            {valueAsTokenItem(
                                                assignedModeratorActionValue,
                                                undefined,
                                                users,
                                                "userID",
                                            )}{" "}
                                        </>
                                    )}
                                    {!formValues.action.actionValue.recordIsLive && t("and remove from community")}
                                </>
                            )}
                        </span>
                    </div>
                )}
            </div>
            {!isLoading && formValues && !formValues.trigger?.triggerType && !formValues.action?.actionType && (
                <div>{t("Set trigger variable and action variable to generate a rule summary.")}</div>
            )}
        </>
    );

    const shouldCheckAdditionalQuery =
        (categories && (categoryActionValue || categoryTriggerValue)) ||
        (tags && tagActionValue) ||
        groupActionValue ||
        (users && assignedModeratorActionValue);

    const dataToLookupFromOptionalSource = groupActionValue
        ? { currentValue: groupActionValue, queryKey: "groupID", sourceToLookup: "groups" }
        : undefined;

    return isLoading ? (
        <>
            <LoadingRectangle height={20} width={350} className={classes.verticalGap} />
            <LoadingRectangle height={20} width={350} className={classes.verticalGap} />
        </>
    ) : (
        <>
            {shouldCheckAdditionalQuery && (
                <AutomationRulesSummaryQuery
                    categories={categories}
                    categoryValue={categoryActionValue || categoryTriggerValue || false}
                    tags={tags}
                    tagValue={tagActionValue || tagTriggerValue || false}
                    users={users}
                    userValue={assignedModeratorActionValue}
                    optionalDataSources={optionalDataSources}
                    dataToLookupFromOptionalSource={dataToLookupFromOptionalSource}
                />
            )}
            {isEditing && isRuleRunning && (
                <Message
                    type="warning"
                    icon={<Icon icon="notification-alert" />}
                    stringContents={t("Rule may not be edited while it is running")}
                    className={classes.bottomGap()}
                />
            )}
            {summarySectionContent}
        </>
    );
}
