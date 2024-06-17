/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useEffect, useMemo } from "react";
import { t } from "@vanilla/i18n";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import {
    AutomationRuleFormValues,
    AutomationRulesAdditionalDataQuery,
} from "@dashboard/automationRules/AutomationRules.types";
import { useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { TokenItem } from "@library/metas/TokenItem";
import { ProfileFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";
import Message from "@library/messages/Message";
import { Icon } from "@vanilla/icons";

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
        setAdditionalDataQuery,
    } = useAutomationRules();

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
            emailDomainTrigger: t("A user logs in or registers with email domain:"),
            profileFieldTrigger: t("A user registers or updates a profile field:"),
            staleDiscussionTrigger: t("Time since last comment:"),
            staleCollectionTrigger: t("A post was added to collection:"),
            lastActiveDiscussionTrigger: t("A post was inactive since:"),
            timeSinceUserRegistrationTrigger: t("A user has been registered for:"),
            ideationVoteTrigger: t("An idea with"),
        },
        actions: {
            addRemoveRoleAction: t("Assign role:"),
            categoryFollowAction: t("Follow categories:"),
            closeDiscussionAction: t("Close the discussion"),
            bumpDiscussionAction: t("Bump the discussion"),
            moveToCategoryAction: t("Move to category:"),
            addTagAction: t("Add tag:"),
            addToCollectionAction: t("Add to collection:"),
            removeDiscussionFromCollectionAction: t("Remove from collection:"),
            removeDiscussionFromTriggerCollectionAction: t("Remove from the collection"),
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

    const isTimeDurationTriggerType = [
        "staleDiscussionTrigger",
        "staleCollectionTrigger",
        "lastActiveDiscussionTrigger",
        "timeSinceUserRegistrationTrigger",
    ].includes(formValues.trigger?.triggerType ?? "");
    const durationValue =
        isTimeDurationTriggerType &&
        formValues.trigger?.triggerValue?.triggerTimeThreshold?.toString().match(/^\d+$/) &&
        formValues.trigger?.triggerValue?.triggerTimeThreshold;
    const intervalValue = formValues.trigger?.triggerValue?.triggerTimeUnit;

    // lets check if we should fetch new data
    const additionalDataQuery = useMemo(() => {
        const query: AutomationRulesAdditionalDataQuery = {};
        if (categoryValue && categories) {
            const newCategoriesToFetch = Array.isArray(categoryValue)
                ? categoryValue.filter(
                      (categoryID) => !categories.find((category) => category.categoryID === categoryID),
                  )
                : !categories.find((category) => category.categoryID === categoryValue)
                ? [categoryValue]
                : [];
            query["categoriesQuery"] = { categoryID: newCategoriesToFetch };
        }
        if (tags && hasTagOrCollectionValue && formValues.action?.actionValue?.tagID) {
            query["tagsQuery"] = {
                tagID: formValues.action?.actionValue?.tagID.filter(
                    (tagID) => !tags.find((tag) => tag.tagID === tagID),
                ),
            };
        }
        return query;
    }, [categories, categoryValue, tags, formValues.action?.actionValue?.tagID]);

    useEffect(() => {
        if (
            (additionalDataQuery.categoriesQuery?.categoryID &&
                additionalDataQuery.categoriesQuery?.categoryID?.length > 0) ||
            (additionalDataQuery.tagsQuery?.tagID && additionalDataQuery.tagsQuery?.tagID?.length > 0)
        ) {
            setAdditionalDataQuery?.(additionalDataQuery);
        }
    }, [additionalDataQuery]);

    const summarySectionContent = (
        <>
            <div>
                {formValues.trigger?.triggerType && (
                    <div>
                        <div className={classes.summaryTitle}>{`${t("Trigger")}: `}</div>
                        <span>{`${summaryMessages.triggers[formValues.trigger?.triggerType]} `}</span>
                        {isProfileFieldTrigger && (
                            <>
                                {formValues.trigger?.triggerValue?.profileField && (
                                    <TokenItem className={classes.bold}>
                                        {
                                            profileFields?.find(
                                                (field) =>
                                                    field.apiName === formValues.trigger?.triggerValue?.profileField,
                                            )?.label
                                        }
                                    </TokenItem>
                                )}
                                {(Array.isArray(profileFieldValue)
                                    ? !!profileFieldValue.length
                                    : hasProfileFieldValue) && (
                                    <>
                                        <span>{` ${t("with")} `}</span>
                                        {Array.isArray(profileFieldValue) ? (
                                            profileFieldValue.map((value, index) => (
                                                <TokenItem key={index} className={classes.summaryValue}>
                                                    {value}
                                                </TokenItem>
                                            ))
                                        ) : (
                                            <TokenItem className={classes.summaryValue}>
                                                {isProfileFieldValueCheckbox
                                                    ? profileFieldValue
                                                        ? t("Yes")
                                                        : t("No")
                                                    : profileFieldValue}
                                            </TokenItem>
                                        )}
                                    </>
                                )}
                            </>
                        )}
                        {isEmailDomainTrigger &&
                            formValues.trigger?.triggerValue?.emailDomain &&
                            formValues.trigger?.triggerValue?.emailDomain.split(",").map((emailDomain, index) => (
                                <TokenItem className={classes.summaryValue} key={index}>
                                    {emailDomain.trim()}
                                </TokenItem>
                            ))}
                        {hasTriggerCollectionValue && (
                            <>
                                {triggerCollectionValue.map((collectionID, index) => (
                                    <TokenItem className={classes.summaryValue} key={index}>
                                        {
                                            collections?.find((collection) => collection.collectionID === collectionID)
                                                ?.name
                                        }
                                    </TokenItem>
                                ))}
                                {`${t("since")} `}
                            </>
                        )}
                        {ideaScoreTriggerValue && (
                            <>
                                <TokenItem className={classes.summaryValue}>{ideaScoreTriggerValue}</TokenItem>
                                {ideaScoreTriggerValue === 1 ? `${t("vote")}` : `${t("votes")}`}
                            </>
                        )}
                        {isTimeDurationTriggerType && (
                            <>
                                {durationValue && (
                                    <TokenItem className={classes.summaryValue}>{durationValue}</TokenItem>
                                )}
                                {intervalValue && (
                                    <TokenItem className={classes.bold}>
                                        {durationValue && parseInt(durationValue) > 1
                                            ? `${intervalValue}s`
                                            : intervalValue}
                                    </TokenItem>
                                )}
                            </>
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
                        <span className={classes.bold}>
                            {hasRoleValues && formValues.action?.actionValue?.addRoleID && (
                                <TokenItem className={classes.summaryValue}>
                                    {rolesByID[formValues.action?.actionValue?.addRoleID]?.name}
                                </TokenItem>
                            )}
                            {hasRoleValues && formValues.action?.actionValue?.removeRoleID && (
                                <>
                                    <span className={classes.normalFontWeight}>{`${t("and remove role")}: `}</span>
                                    <TokenItem className={classes.summaryValue}>
                                        {rolesByID[formValues.action?.actionValue?.removeRoleID]?.name}
                                    </TokenItem>
                                </>
                            )}
                            {hasTagOrCollectionValue && (
                                <>
                                    {formValues.action?.actionType === "addTagAction" &&
                                        tagValue.map((tagID, index) => (
                                            <TokenItem className={classes.summaryValue} key={index}>
                                                {tags?.find((tag) => tag.tagID === tagID)?.name}
                                            </TokenItem>
                                        ))}
                                    {["addToCollectionAction", "removeDiscussionFromCollectionAction"].includes(
                                        formValues.action?.actionType ?? "",
                                    ) &&
                                        collectionValue.map((collectionID, index) => (
                                            <TokenItem className={classes.summaryValue} key={index}>
                                                {
                                                    collections?.find(
                                                        (collection) => collection.collectionID === collectionID,
                                                    )?.name
                                                }
                                            </TokenItem>
                                        ))}
                                </>
                            )}
                            {hasCategoryValues && categories && (
                                <>
                                    {Array.isArray(categoryValue) ? (
                                        (categoryValue ?? []).map((categoryID, index) => {
                                            const categoryName = categories.find(
                                                (category) => category.categoryID === categoryID,
                                            )?.name;
                                            if (categoryName) {
                                                return (
                                                    <TokenItem className={classes.summaryValue} key={index}>
                                                        {categoryName}
                                                    </TokenItem>
                                                );
                                            }
                                        })
                                    ) : (
                                        <TokenItem className={classes.bold}>
                                            {
                                                categories?.find((category) => category.categoryID === categoryValue)
                                                    ?.name
                                            }
                                        </TokenItem>
                                    )}
                                </>
                            )}
                            {ideaStatusActionValue && ideaStatusesByID && (
                                <TokenItem className={classes.summaryValue}>
                                    {ideaStatusesByID[ideaStatusActionValue]}
                                </TokenItem>
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

    return isLoading ? (
        <>
            <LoadingRectangle height={20} width={350} className={classes.verticalGap} />
            <LoadingRectangle height={20} width={350} className={classes.verticalGap} />
        </>
    ) : (
        <>
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
