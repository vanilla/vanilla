/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    AddEditAutomationRuleParams,
    AutomationRuleFormValues,
    AutomationRulesPreviewContent,
} from "@dashboard/automationRules/AutomationRules.types";
import {
    convertTimeIntervalToApiValues,
    mapFormValuesToApiValues,
} from "@dashboard/automationRules/AutomationRules.utils";
import { AutomationRulesPreviewUsersContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewUsersContent";
import { AutomationRulesPreviewPostsContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewPostsContent";
import { dateRangeToString } from "@library/search/SearchUtils";
import { JsonSchema } from "packages/vanilla-json-schema-forms/src";
import { t } from "@vanilla/i18n";
import { AutomationRulesPreviewCollectionRecordsContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewCollectionRecordsContent";
import { AutomationRulesPreviewReportedPostsContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewReportedPostsContent";

interface IProps {
    formValues: AutomationRuleFormValues;
    fromStatusToggle?: boolean;
    onPreviewContentLoad?: (emptyResult: boolean) => void;
    schema?: JsonSchema;
}

export function AutomationRulesPreviewRenderer(props: IProps) {
    const { schema } = props;
    const apiValues = mapFormValuesToApiValues(props.formValues);

    const missingRequiredFields =
        schema &&
        (!apiValues.trigger?.triggerType ||
            (schema.properties?.trigger?.properties?.triggerValue?.required ?? []).some((requiredField) => {
                return Array.isArray(apiValues.trigger?.triggerValue[requiredField])
                    ? apiValues.trigger?.triggerValue[requiredField].length === 0
                    : typeof apiValues.trigger?.triggerValue[requiredField] === "undefined" ||
                          apiValues.trigger?.triggerValue[requiredField] === "";
            }));

    const commonProps = {
        fromStatusToggle: props.fromStatusToggle,
        onPreviewContentLoad: props.onPreviewContentLoad,
    };

    const matchingContentByTriggerType = AutomationRulesPreviewRenderer.contentByTriggerType.find(
        (contentByTriggerType) => contentByTriggerType[apiValues.trigger?.triggerType ?? ""],
    );
    const previewQuery =
        matchingContentByTriggerType &&
        matchingContentByTriggerType[apiValues.trigger?.triggerType ?? ""].queryBuilder(apiValues);
    const PreviewContentComponent =
        matchingContentByTriggerType && matchingContentByTriggerType[apiValues.trigger?.triggerType ?? ""].component;

    return (
        <>
            {missingRequiredFields ? (
                t("Please set required trigger values to see the preview.")
            ) : PreviewContentComponent ? (
                <PreviewContentComponent {...commonProps} query={previewQuery ?? {}} />
            ) : (
                <></>
            )}
        </>
    );
}

AutomationRulesPreviewRenderer.contentByTriggerType = [
    {
        profileFieldTrigger: {
            component: AutomationRulesPreviewUsersContent,
            queryBuilder: (apiValues: AddEditAutomationRuleParams) => {
                const profileFieldApiKey = Object.keys(apiValues.trigger?.triggerValue?.profileField || {})[0];
                const profileFieldValue = apiValues.trigger?.triggerValue?.profileField?.[profileFieldApiKey];
                return {
                    sort: "name",
                    ...(profileFieldValue && { profileFields: apiValues.trigger?.triggerValue?.profileField }),
                };
            },
        },
    },
    {
        emailDomainTrigger: {
            component: AutomationRulesPreviewUsersContent,
            queryBuilder: (apiValues: AddEditAutomationRuleParams) => {
                const emailDomainValue =
                    apiValues.trigger?.triggerValue?.emailDomain &&
                    apiValues.trigger?.triggerValue?.emailDomain.split(",").map((domain) => domain.trim());
                return {
                    ...(!!emailDomainValue?.length && {
                        emailDomain: emailDomainValue,
                        sort: "name",
                        emailConfirmed: true,
                    }),
                };
            },
        },
    },
    {
        timeSinceUserRegistrationTrigger: {
            component: AutomationRulesPreviewUsersContent,
            queryBuilder: (apiValues: AddEditAutomationRuleParams) => {
                return { sort: "name", dateInserted: getDateValueForAPI(apiValues) };
            },
        },
    },
    {
        staleDiscussionTrigger: {
            component: AutomationRulesPreviewPostsContent,
            queryBuilder: (apiValues: AddEditAutomationRuleParams) => {
                return {
                    limit: 30,
                    type: apiValues.trigger?.triggerValue.postType,
                    dateInserted: getDateValueForAPI(apiValues),
                    categoryID: apiValues.trigger?.triggerValue?.categoryID,
                    tagID: apiValues.trigger?.triggerValue?.tagID,
                    hasComments: false,
                };
            },
        },
    },
    {
        lastActiveDiscussionTrigger: {
            component: AutomationRulesPreviewPostsContent,
            queryBuilder: (apiValues: AddEditAutomationRuleParams) => {
                return {
                    limit: 30,
                    type: apiValues.trigger?.triggerValue.postType,
                    dateLastComment: getDateValueForAPI(apiValues),
                };
            },
        },
    },
    {
        discussionReachesScoreTrigger: {
            component: AutomationRulesPreviewPostsContent,
            queryBuilder: (apiValues: AddEditAutomationRuleParams) => {
                return {
                    limit: 30,
                    type: apiValues.trigger?.triggerValue.postType,
                    score: apiValues.trigger?.triggerValue.score,
                };
            },
        },
    },
    {
        staleCollectionTrigger: {
            component: AutomationRulesPreviewCollectionRecordsContent,
            queryBuilder: (apiValues: AddEditAutomationRuleParams) => {
                return {
                    limit: 30,
                    collectionID: apiValues.trigger?.triggerValue?.collectionID,
                    dateAddedToCollection: getDateValueForAPI(apiValues),
                };
            },
        },
    },
    {
        reportPostTrigger: {
            component: AutomationRulesPreviewReportedPostsContent,
            queryBuilder: (apiValues: AddEditAutomationRuleParams) => {
                return {
                    limit: 30,
                    countReports: apiValues.trigger?.triggerValue?.countReports,
                    reportReasonID: apiValues.trigger?.triggerValue?.reportReasonID,
                    includeSubcategories: apiValues.trigger?.triggerValue?.includeSubcategories,
                    ...(apiValues.trigger?.triggerValue?.categoryID?.length > 0 && {
                        placeRecordID: apiValues.trigger?.triggerValue?.categoryID,
                        placeRecordType: "category",
                    }),
                };
            },
        },
    },
] as AutomationRulesPreviewContent[];

AutomationRulesPreviewRenderer.registerContentByTriggerType = (previewContent: AutomationRulesPreviewContent) => {
    AutomationRulesPreviewRenderer.contentByTriggerType.push(previewContent);
};

export function getDateValueForAPI(apiValues: AddEditAutomationRuleParams) {
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
    return dateValueForAPI;
}
