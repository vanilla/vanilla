/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { FilterBlock } from "@dashboard/moderation/components/FilterBlock";
import { TagScopeService } from "@dashboard/tagging/TagScopeService";
import { useTagsRequestContext } from "@dashboard/tagging/TagsRequest.context";
import { t } from "@vanilla/i18n";
import { RecordID } from "@vanilla/utils";
import { ScopeType } from "@dashboard/tagging/taggingSettings.types";

export default function TagsFilters() {
    const { requestBody, updateRequestBody } = useTagsRequestContext();

    return (
        <>
            <h4>{t("Filter Tags")}</h4>

            <FilterBlock
                apiName={"scopeType"}
                label={"Scope"}
                staticOptions={[
                    {
                        name: t("Global"),
                        value: ScopeType.GLOBAL,
                    },
                    {
                        name: t("Scoped"),
                        value: ScopeType.SCOPED,
                    },
                ]}
                initialFilters={requestBody.scopeType ?? []}
                onFilterChange={(value) => {
                    if (value.scopeType) {
                        updateRequestBody({
                            scopeType: value.scopeType as typeof requestBody.scopeType,
                        });
                    }
                }}
            />

            {Object.entries(TagScopeService.scopes).map(([apiName, scope]) => {
                const id = scope.id;
                const label = scope.singular;
                const filterLookupApi = scope.filterLookupApi;
                const initialFilters = (requestBody["scope"]?.[apiName] ?? []).map((id: RecordID) => `${id}`);

                return (
                    <FilterBlock
                        key={id}
                        apiName={apiName}
                        label={label}
                        initialFilters={initialFilters}
                        dynamicOptionApi={filterLookupApi}
                        onFilterChange={(value) => {
                            if (Array.isArray(value[apiName])) {
                                updateRequestBody({
                                    scope: {
                                        ...requestBody.scope,
                                        [apiName]: value[apiName],
                                    },
                                });
                            }
                        }}
                    />
                );
            })}
        </>
    );
}
