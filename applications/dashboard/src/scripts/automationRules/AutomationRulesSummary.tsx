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

interface IAutomationRulesSummaryProps {
    formValues: AutomationRuleFormValues;
    isLoading?: boolean;
    isEditing?: boolean;
    isRuleRunning?: boolean;
}

export default function AutomationRulesSummary(props: IAutomationRulesSummaryProps) {
    const { formValues, isEditing, isRuleRunning } = props;
    const classes = automationRulesClasses();
    const { automationRulesCatalog, rolesByID, profileFields, tags, collections, categories, ideaStatusesByID } =
        useAutomationRules();

    const isLoading = isEditing
        ? props.isLoading ||
          !rolesByID ||
          !profileFields ||
          !categories ||
          !tags ||
          !collections ||
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
        },
    };

    const isProfileFieldTrigger = formValues.trigger?.triggerType === "profileFieldTrigger";
    const profileFieldValue = formValues.trigger?.triggerValue?.[formValues.trigger?.triggerValue?.profileField];
    const hasProfileFieldValue = profileFieldValue !== undefined;

    const isProfileFieldValueCheckbox =
        hasProfileFieldValue &&
        profileFields?.find((field) => field.apiName === formValues.trigger?.triggerValue?.profileField)?.formType ===
            ProfileFieldFormType.CHECKBOX;

    const hasRoleValues = formValues.action?.actionType === "addRemoveRoleAction" && rolesByID;

    const hasCategoryValues = Boolean(
        (formValues.action?.actionType === "categoryFollowAction" &&
            !!formValues.action.actionValue?.categoryID?.length) ||
            (formValues.action?.actionType === "moveToCategoryAction" && formValues.action.actionValue?.categoryID),
    );
    const categoryValue = hasCategoryValues && formValues.action?.actionValue?.categoryID;

    const hasTagOrCollectionValue =
        (formValues.action?.actionType === "addTagAction" && !!formValues.action?.actionValue?.tagID?.length) ||
        (["addToCollectionAction", "removeDiscussionFromCollectionAction"].includes(
            formValues.action?.actionType ?? "",
        ) &&
            !!formValues.action?.actionValue?.collectionID?.length);

    const tagValue = hasTagOrCollectionValue && formValues.action?.actionValue?.tagID;
    const collectionValue = hasTagOrCollectionValue && formValues.action?.actionValue?.collectionID;

    const hasTriggerCollectionValue =
        formValues.trigger?.triggerType === "staleCollectionTrigger" &&
        !!formValues.trigger?.triggerValue?.collectionID?.length;

    const triggerCollectionValue = hasTriggerCollectionValue && formValues.trigger?.triggerValue?.collectionID;

    const ideaScoreTriggerValue =
        formValues.trigger?.triggerType === "ideationVoteTrigger" && formValues.trigger?.triggerValue?.score;

    const ideaStatusActionValue =
        formValues.action?.actionType === "changeIdeationStatusAction" && formValues.action?.actionValue?.statusID;

    const isEmailDomainTrigger = formValues.trigger?.triggerType === "emailDomainTrigger";

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
                                ? dataToLookUpForName?.find((entry) => entry[needleToLookUp ?? ""] === val)?.name
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
                            {hasRoleValues &&
                                formValues.action?.actionValue?.addRoleID &&
                                valueAsTokenItem(rolesByID[formValues.action?.actionValue?.addRoleID]?.name)}
                            {hasRoleValues && formValues.action?.actionValue?.removeRoleID && (
                                <>
                                    <span className={classes.normalFontWeight}>{` ${t("and remove role")}: `}</span>
                                    {valueAsTokenItem(rolesByID[formValues.action?.actionValue?.removeRoleID]?.name)}
                                </>
                            )}
                            {hasTagOrCollectionValue && (
                                <>
                                    {formValues.action?.actionType === "addTagAction" &&
                                        valueAsTokenItem(tagValue, "and", tags, "tagID")}
                                    {["addToCollectionAction", "removeDiscussionFromCollectionAction"].includes(
                                        formValues.action?.actionType ?? "",
                                    ) && valueAsTokenItem(collectionValue, "and", collections, "collectionID")}
                                </>
                            )}
                            {hasCategoryValues &&
                                categories &&
                                valueAsTokenItem(categoryValue, "and", categories, "categoryID")}
                            {ideaStatusActionValue &&
                                ideaStatusesByID &&
                                valueAsTokenItem(ideaStatusesByID[ideaStatusActionValue])}
                        </span>
                    </div>
                )}
            </div>
            {!isLoading && formValues && !formValues.trigger?.triggerType && !formValues.action?.actionType && (
                <div>{t("Set trigger variable and action variable to generate a rule summary.")}</div>
            )}
        </>
    );

    const shouldCheckAdditionalQuery = (categories && categoryValue) || (tags && tagValue);

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
                    tags={tags}
                    categoryValue={categoryValue}
                    tagValue={tagValue}
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
