/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AutomationRuleFormValues, AutomationRulePreviewQuery } from "@dashboard/automationRules/AutomationRules.types";
import {
    convertTimeIntervalToApiValues,
    mapFormValuesToApiValues,
} from "@dashboard/automationRules/AutomationRules.utils";
import { IGetUsersQueryParams } from "@dashboard/users/userManagement/UserManagement.hooks";
import { IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { AutomationRulesPreviewUsersContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewUsersContent";
import { AutomationRulesPreviewPostsContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewPostsContent";
import { dateRangeToString } from "@library/search/SearchUtils";
import { JsonSchema } from "packages/vanilla-json-schema-forms/src";
import { t } from "@vanilla/i18n";
import { AutomationRulesPreviewCollectionRecordsContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewCollectionRecordsContent";
import { IGetCollectionResourcesParams } from "@library/featuredCollections/collectionsHooks";
import {
    AutomationRulesPreviewReportedPostsContent,
    IGetReportsForAutomationRulesParams,
} from "@dashboard/automationRules/preview/AutomationRulesPreviewReportedPostsContent";
import { useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";

interface IProps {
    formValues: AutomationRuleFormValues;
    fromStatusToggle?: boolean;
    onPreviewContentLoad?: (emptyResult: boolean) => void;
    schema?: JsonSchema;
}

export function AutomationRulesPreviewContent(props: IProps) {
    const { schema } = props;
    const apiValues = mapFormValuesToApiValues(props.formValues);
    const { automationRulesCatalog } = useAutomationRules();

    let query: AutomationRulePreviewQuery = {};

    // for time based triggers
    const lookBackTimeLength = apiValues.trigger?.triggerValue?.triggerTimeLookBackLimit?.length;
    const lookBackTimeUnit = apiValues.trigger?.triggerValue?.triggerTimeLookBackLimit?.unit;
    const triggerDelayLength = apiValues.trigger?.triggerValue?.triggerTimeDelay?.length;
    const triggerDelayUnit = apiValues.trigger?.triggerValue?.triggerTimeDelay?.unit;

    const shouldConsiderOffsetTime = !apiValues.trigger?.triggerValue.applyToNewContentOnly;
    const offsetTime =
        shouldConsiderOffsetTime &&
        lookBackTimeLength &&
        lookBackTimeUnit &&
        convertTimeIntervalToApiValues(parseInt(lookBackTimeLength), lookBackTimeUnit);
    const triggerTime =
        triggerDelayLength &&
        triggerDelayUnit &&
        convertTimeIntervalToApiValues(parseInt(triggerDelayLength), triggerDelayUnit);

    const dateValueForAPI =
        triggerTime &&
        (offsetTime
            ? dateRangeToString({
                  start: offsetTime.toUTCString(),
                  end: triggerTime.toUTCString(),
              })
            : triggerTime.toUTCString().replace(/,/g, ""));

    const isStaleDiscussionOrUnansweredQuestionTrigger =
        apiValues.trigger?.triggerType === "staleDiscussionTrigger" ||
        apiValues.trigger?.triggerType === "unAnsweredQuestionTrigger";

    switch (apiValues.trigger?.triggerType) {
        case "profileFieldTrigger":
            const profileFieldApiKey = Object.keys(apiValues.trigger?.triggerValue?.profileField || {})[0];
            const profileFieldValue = apiValues.trigger?.triggerValue?.profileField?.[profileFieldApiKey];
            query = {
                sort: "name",
                ...(profileFieldValue && { profileFields: apiValues.trigger?.triggerValue?.profileField }),
            };
            break;
        case "emailDomainTrigger":
            const emailDomainValue =
                apiValues.trigger?.triggerValue?.emailDomain &&
                apiValues.trigger?.triggerValue?.emailDomain.split(",").map((domain) => domain.trim());

            query = {
                ...(!!emailDomainValue?.length && {
                    emailDomain: emailDomainValue,
                    sort: "name",
                    emailConfirmed: true,
                }),
            };
            break;
        case "timeSinceUserRegistrationTrigger":
            query = { sort: "name", dateInserted: dateValueForAPI };
            break;
        default:
            query = {
                ...(apiValues.trigger?.triggerType && { limit: 30 }),
                ...(schema?.properties?.trigger?.properties.triggerValue?.postType && {
                    type: apiValues.trigger?.triggerValue.postType,
                }),
                ...(isStaleDiscussionOrUnansweredQuestionTrigger && {
                    dateInserted: dateValueForAPI,
                    categoryID: apiValues.trigger?.triggerValue?.categoryID,
                    tagID: apiValues.trigger?.triggerValue?.tagID,
                    ...(apiValues.trigger?.triggerType === "staleDiscussionTrigger" && { hasComments: false }),
                    ...(apiValues.trigger?.triggerType === "unAnsweredQuestionTrigger" && {
                        type: "question",
                        statusID: [1, 4],
                    }),
                }),
                ...(apiValues.trigger?.triggerType === "lastActiveDiscussionTrigger" && {
                    dateLastComment: dateValueForAPI,
                }),
                ...((apiValues.trigger?.triggerType === "ideationVoteTrigger" ||
                    apiValues.trigger?.triggerType === "discussionReachesScoreTrigger") &&
                    apiValues.trigger?.triggerValue?.score && {
                        type:
                            apiValues.trigger?.triggerType === "ideationVoteTrigger"
                                ? "idea"
                                : apiValues.trigger?.triggerValue?.postType,
                        score: apiValues.trigger?.triggerValue?.score,
                    }),

                ...(apiValues.trigger?.triggerType === "staleCollectionTrigger" &&
                    apiValues.trigger?.triggerValue?.collectionID?.length > 0 && {
                        collectionID: apiValues.trigger?.triggerValue?.collectionID,
                        dateAddedToCollection: dateValueForAPI,
                    }),
                ...(apiValues.trigger?.triggerType === "reportPostTrigger" &&
                    apiValues.trigger?.triggerValue?.countReports && {
                        countReports: apiValues.trigger?.triggerValue?.countReports,
                        reportReasonID: apiValues.trigger?.triggerValue?.reportReasonID,
                        includeSubcategories: apiValues.trigger?.triggerValue?.includeSubcategories,
                        ...(apiValues.trigger?.triggerValue?.categoryID?.length > 0 && {
                            placeRecordID: apiValues.trigger?.triggerValue?.categoryID,
                            placeRecordType: "category",
                        }),
                    }),
            };
            break;
    }

    // Reset the query to empty if we did not set required fields for the trigger
    if (
        schema &&
        (!apiValues.trigger?.triggerType ||
            (schema.properties?.trigger?.properties?.triggerValue?.required ?? []).some((requiredField) => {
                return Array.isArray(apiValues.trigger?.triggerValue[requiredField])
                    ? apiValues.trigger?.triggerValue[requiredField].length === 0
                    : typeof apiValues.trigger?.triggerValue[requiredField] === "undefined";
            }))
    ) {
        query = {};
    }

    const commonProps = {
        fromStatusToggle: props.fromStatusToggle,
        onPreviewContentLoad: props.onPreviewContentLoad,
    };

    return (
        <>
            {Object.keys(query).length === 0 ? (
                t("Please set required trigger values to see the preview.")
            ) : automationRulesCatalog?.triggers?.[apiValues.trigger?.triggerType ?? ""]?.contentType === "users" ? (
                <AutomationRulesPreviewUsersContent query={query as IGetUsersQueryParams} {...commonProps} />
            ) : automationRulesCatalog?.triggers?.[apiValues.trigger?.triggerType ?? ""]?.contentType === "posts" ? (
                apiValues.trigger?.triggerType === "staleCollectionTrigger" ? (
                    <AutomationRulesPreviewCollectionRecordsContent
                        query={query as IGetCollectionResourcesParams}
                        {...commonProps}
                    />
                ) : apiValues.trigger?.triggerType === "reportPostTrigger" ? (
                    <AutomationRulesPreviewReportedPostsContent
                        query={query as IGetReportsForAutomationRulesParams}
                        {...commonProps}
                    />
                ) : (
                    <AutomationRulesPreviewPostsContent query={query as IGetDiscussionListParams} {...commonProps} />
                )
            ) : (
                <></>
            )}
        </>
    );
}
