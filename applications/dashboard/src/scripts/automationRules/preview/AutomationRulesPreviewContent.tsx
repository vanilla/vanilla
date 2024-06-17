/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    AutomationRuleFormValues,
    AutomationRulePreviewContentType,
} from "@dashboard/automationRules/AutomationRules.types";
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

interface IProps {
    formValues: AutomationRuleFormValues;
    fromStatusToggle?: boolean;
    onPreviewContentLoad?: (emptyResult: boolean) => void;
    schema?: JsonSchema;
}

export function AutomationRulesPreviewContent(props: IProps) {
    const { schema } = props;
    const formValues = mapFormValuesToApiValues(props.formValues);

    let query: IGetUsersQueryParams | IGetDiscussionListParams | IGetCollectionResourcesParams = {};
    let rulePreviewContentType: AutomationRulePreviewContentType = "users";

    // for time based triggers
    const maxTimeThreshold = formValues.trigger?.triggerValue?.maxTimeThreshold;
    const maxTimeUnit = formValues.trigger?.triggerValue?.maxTimeUnit;
    const triggerTimeThreshold = formValues.trigger?.triggerValue?.triggerTimeThreshold;
    const triggerTimeUnit = formValues.trigger?.triggerValue?.triggerTimeUnit;

    const offsetTime =
        maxTimeThreshold && maxTimeUnit && convertTimeIntervalToApiValues(parseInt(maxTimeThreshold), maxTimeUnit);
    const triggerTime =
        triggerTimeThreshold &&
        triggerTimeUnit &&
        convertTimeIntervalToApiValues(parseInt(triggerTimeThreshold), triggerTimeUnit);

    const dateValueForAPI =
        triggerTime &&
        (offsetTime
            ? dateRangeToString({
                  start: offsetTime.toUTCString(),
                  end: triggerTime.toUTCString(),
              })
            : triggerTime.toUTCString().replace(/,/g, ""));

    switch (formValues.trigger?.triggerType) {
        case "profileFieldTrigger":
            const profileFieldApiKey = Object.keys(formValues.trigger?.triggerValue?.profileField || {})[0];
            const profileFieldValue = formValues.trigger?.triggerValue?.profileField?.[profileFieldApiKey];
            query = {
                sort: "name",
                ...(profileFieldValue && { profileFields: formValues.trigger?.triggerValue?.profileField }),
            };
            break;
        case "emailDomainTrigger":
            const emailDomainValue =
                formValues.trigger?.triggerValue?.emailDomain &&
                formValues.trigger?.triggerValue?.emailDomain.split(",").map((domain) => domain.trim());

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
        case "staleDiscussionTrigger":
        case "staleCollectionTrigger":
        case "lastActiveDiscussionTrigger":
        case "ideationVoteTrigger":
            rulePreviewContentType = "posts";

            query = {
                limit: 30,
                type: formValues.trigger?.triggerValue.postType,
                ...(formValues.trigger?.triggerType === "staleDiscussionTrigger" && {
                    hasComments: false,
                    dateInserted: dateValueForAPI,
                }),
                ...(formValues.trigger?.triggerType === "lastActiveDiscussionTrigger" && {
                    dateLastComment: dateValueForAPI,
                }),
                ...(formValues.trigger?.triggerType === "ideationVoteTrigger" &&
                    formValues.trigger?.triggerValue?.score && {
                        type: "idea",
                        score: formValues.trigger?.triggerValue?.score,
                    }),
                ...(formValues.trigger?.triggerType === "staleCollectionTrigger" &&
                    formValues.trigger?.triggerValue?.collectionID.length && {
                        collectionID: formValues.trigger?.triggerValue?.collectionID,
                        dateAddedToCollection: dateValueForAPI,
                    }),
            };
            break;
    }

    // reset the query to empty if we did not set required fields for the trigger
    if (
        schema &&
        (!formValues.trigger?.triggerType ||
            (schema.properties?.trigger?.properties?.triggerValue?.required ?? []).some((requiredField) => {
                return Array.isArray(formValues.trigger?.triggerValue[requiredField])
                    ? formValues.trigger?.triggerValue[requiredField].length === 0
                    : !formValues.trigger?.triggerValue[requiredField];
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
            ) : rulePreviewContentType === "users" ? (
                <AutomationRulesPreviewUsersContent query={query as IGetUsersQueryParams} {...commonProps} />
            ) : rulePreviewContentType === "posts" ? (
                formValues.trigger?.triggerType === "staleCollectionTrigger" ? (
                    <AutomationRulesPreviewCollectionRecordsContent query={query as IGetCollectionResourcesParams} />
                ) : (
                    <AutomationRulesPreviewPostsContent query={query as IGetDiscussionListParams} {...commonProps} />
                )
            ) : (
                <></>
            )}
        </>
    );
}
