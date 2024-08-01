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
import AutomationRulesSummaryQuery from "./AutomationRulesSummaryQuery";
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
    const { automationRulesCatalog, rolesByID, profileFields, tags, collections, categories, ideaStatusesByID, users } =
        useAutomationRules();

    const reportReasonsFromCatalog = (
        automationRulesCatalog?.triggers?.reportPostTrigger?.schema?.properties?.reportReasonID?.[
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
        triggers: {
            emailDomainTrigger: t("A user registers or logs in with email domain:"),
            profileFieldTrigger: t("A user registers or updates a profile field:"),
            staleDiscussionTrigger: t("A post has not received any comments"),
            staleCollectionTrigger: t("A post has been added to a collection"),
            lastActiveDiscussionTrigger: t("A post has not had any activity"),
            timeSinceUserRegistrationTrigger: t("A user has been registered"),
            ideationVoteTrigger: t("An idea has received"),
            postSentimentTrigger: t("A post has been created with a specific a sentiment"),
            reportPostTrigger: t("A post has received"),
        },
        actions: {
            addRemoveRoleAction: t("Assign role:"),
            categoryFollowAction: t("Follow categories:"),
            closeDiscussionAction: t("Close the post"),
            bumpDiscussionAction: t("Bump the post"),
            moveToCategoryAction: t("Move to category:"),
            addTagAction: t("Add tags:"),
            addToCollectionAction: t("Add to collection:"),
            removeDiscussionFromCollectionAction: t("Remove from collection:"),
            removeDiscussionFromTriggerCollectionAction: t("Remove from collection:"),
            changeIdeationStatusAction: t("Change the status of the idea to"),
            createEscalationAction: t("Escalate it"),
            escalateGithubIssueAction: t("Escalate to GitHub"),
            escalateToZendeskAction: t("Escalate to Zendesk"),
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

    const hasTriggerCollectionValue =
        formValues.trigger?.triggerType === "staleCollectionTrigger" &&
        !!formValues.trigger?.triggerValue?.collectionID?.length;

    const triggerCollectionValue = hasTriggerCollectionValue && formValues.trigger?.triggerValue?.collectionID;

    const ideaScoreTriggerValue =
        formValues.trigger?.triggerType === "ideationVoteTrigger" && formValues.trigger?.triggerValue?.score;

    const countReportsTriggerValue =
        formValues.trigger?.triggerType === "reportPostTrigger" && formValues.trigger?.triggerValue?.countReports;
    const reportReasonTriggerValue =
        countReportsTriggerValue &&
        reportReasonsFromCatalog &&
        formValues.trigger?.triggerValue?.reportReasonID?.length > 0 &&
        formValues.trigger?.triggerValue?.reportReasonID;
    const reportCategoryTriggerValue =
        countReportsTriggerValue &&
        formValues.trigger?.triggerValue?.categoryID?.length > 0 &&
        formValues.trigger?.triggerValue?.categoryID;

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
        (["addToCollectionAction", "removeDiscussionFromCollectionAction"].includes(
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

    // time duration values
    const isTimeDurationTriggerType = isTimeBasedTrigger(formValues.trigger?.triggerType, automationRulesCatalog);
    const triggerDelayValue =
        isTimeDurationTriggerType && !Number.isNaN(formValues.trigger?.triggerValue?.triggerTimeDelay?.length)
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
                        <span className={classes.rightGap(4)}>{`${t(valueSeparator)} `}</span>
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
                        {ideaScoreTriggerValue && (
                            <>
                                {valueAsTokenItem(ideaScoreTriggerValue)}
                                {ideaScoreTriggerValue == 1 ? ` ${t("upvote")}` : ` ${t("upvotes")}`}
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
                        {reportCategoryTriggerValue && categories && (
                            <>
                                {!reportReasonTriggerValue && " "}
                                {`${t("in")} `}
                                {valueAsTokenItem(reportCategoryTriggerValue, "or", categories, "categoryID")}
                                {t("category")}
                            </>
                        )}
                        {hasTriggerCollectionValue && (
                            <>{valueAsTokenItem(triggerCollectionValue, "or", collections, "collectionID")}</>
                        )}
                        {triggerDelayValue && (
                            <>
                                {`${t("for")}: `}
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
                                    {["addToCollectionAction", "removeDiscussionFromCollectionAction"].includes(
                                        formValues.action?.actionType ?? "",
                                    ) && valueAsTokenItem(collectionActionValue, "and", collections, "collectionID")}
                                </>
                            )}
                            {hasCategoryActionValues &&
                                categories &&
                                valueAsTokenItem(categoryActionValue, "and", categories, "categoryID")}
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
        (categories && (categoryActionValue || reportCategoryTriggerValue)) ||
        (tags && tagActionValue) ||
        (users && assignedModeratorActionValue);

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
                    categoryValue={
                        categoryActionValue
                            ? categoryActionValue
                            : reportCategoryTriggerValue
                            ? reportCategoryTriggerValue
                            : false
                    }
                    tags={tags}
                    tagValue={tagActionValue}
                    users={users}
                    userValue={assignedModeratorActionValue}
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
