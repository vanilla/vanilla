/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import DashboardListPageClasses from "@dashboard/components/DashboardListPage.classes";
import DashboardSearchBar from "@dashboard/components/search/DashboardSearchBar";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import AddTag from "@dashboard/tagging/features/AddTag";
import TagsFilters from "@dashboard/tagging/components/TagsFilters";
import TagsTable from "@dashboard/tagging/components/TagsTable";
import { IGetTagsRequestBody, ITagItem } from "@dashboard/tagging/taggingSettings.types";
import { TagScopeService } from "@dashboard/tagging/TagScopeService";
import {
    TagsRequestContext,
    TagsRequestContextProvider,
    useTagsRequestContext,
} from "@dashboard/tagging/TagsRequest.context";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import Heading from "@library/layout/Heading";
import SmartLink from "@library/routing/links/SmartLink";
import QueryString from "@library/routing/QueryString";
import { getMeta, t } from "@library/utility/appUtils";
import * as qs from "qs-esm";
import { useLocation } from "react-router-dom";
import { useEffect, useState } from "react";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardLabelType";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ToolTip } from "@library/toolTip/ToolTip";
import { useConfigMutation } from "@library/config/configHooks";

export function TaggingSettingsImpl(props: { scopeEnabled: boolean }) {
    const { scopeEnabled } = props;

    const { headerContainer, searchAndActionsContainer, searchAndCountContainer, pagerContainer, pager } =
        DashboardListPageClasses.useAsHook();

    const {
        requestBody,
        updateRequestBody,
        tagsQuery: { isLoading, data },
        invalidate: invalidateTagsQuery,
    } = useTagsRequestContext();

    useEffect(() => {
        if (!scopeEnabled) {
            updateRequestBody({ scopeType: undefined, scope: undefined });
        }
    }, [scopeEnabled]);

    return (
        <>
            <div className={headerContainer}>
                <div className={searchAndActionsContainer}>
                    <div className={searchAndCountContainer}>
                        <DashboardSearchBar
                            placeholder={t("Search for all or part of a tag")}
                            initialValue={requestBody.query ?? ""}
                            updateQuery={(query) => updateRequestBody({ query })}
                        />
                    </div>

                    <div className={pagerContainer}>
                        <NumberedPager
                            {...{
                                className: pager,
                                showNextButton: false,
                                totalResults: data?.paging?.total,
                                currentPage: data?.paging?.currentPage,
                                pageLimit: data?.paging?.limit,
                                hasMorePages: data?.paging?.total ? data?.paging?.total >= 10000 : false,
                            }}
                            onChange={(page: number) => updateRequestBody({ page })}
                        />
                    </div>
                </div>
            </div>

            <div className={dashboardClasses().extendRow}>
                <TagsTable
                    key={scopeEnabled ? "scoped" : "global"}
                    scopeEnabled={scopeEnabled}
                    isLoading={isLoading}
                    tags={data?.data ?? ([] as ITagItem[])}
                    sort={requestBody.sort}
                    onSortChange={(sort) => updateRequestBody({ sort })}
                    onMutateSuccess={invalidateTagsQuery}
                />
            </div>

            <>
                <DashboardHelpAsset>
                    <Heading>{t("About Tagging")}</Heading>
                    <p>{t("Tags are keywords that users can assign to discussions to help group related content.")}</p>
                    <p>
                        {t(
                            "When scoped tagging is enabled, you can assign tags to specific categories or subcommunities. This helps ensure that users only see tags relevant to the part of the community they’re in — making it easier to discover, filter, and engage with content that matters most to them.",
                        )}
                    </p>

                    <SmartLink to="https://success.vanillaforums.com/kb/articles/FIXME-GET-LINK">
                        {t("Read More")}
                    </SmartLink>
                    {scopeEnabled && <TagsFilters />}
                </DashboardHelpAsset>
            </>
        </>
    );
}

export default function TaggingSettings() {
    const [taggingEnabled, setTaggingEnabled] = useState<boolean>(getMeta("tagging.enabled", false));
    const [scopedTaggingEnabled, setScopedTaggingEnabled] = useState<boolean>(
        getMeta("tagging.scopedTaggingEnabled", false),
    );

    const { mutateAsync: patchConfig } = useConfigMutation(150);

    const location = useLocation();
    const { search: queryString } = location;
    const initialQueryParams: Partial<IGetTagsRequestBody> = qs.parse(queryString, { ignoreQueryPrefix: true });
    const { page: _pageString, sort, limit, query, scopeType, scope } = initialQueryParams;

    const page = parseInt(`${_pageString ?? "1"}`);

    const initialRequestBody = { page, sort, limit, query, ...(scopedTaggingEnabled ? { scopeType, scope } : {}) };

    if (scopedTaggingEnabled) {
        // these scopes are dynamically added. this is to white-list them in the query string.
        Object.keys(TagScopeService.scopes).forEach((apiName) => {
            const initialFilters = (initialQueryParams[apiName] ?? []).map((id: string | number) => `${id}`);
            initialRequestBody[apiName] = initialFilters;
        });
    }

    return (
        <ErrorPageBoundary>
            <DashboardHeaderBlock
                title={t("Tagging")}
                actionButtons={
                    taggingEnabled ? (
                        <AddTag disabled={!taggingEnabled} scopeEnabled={scopedTaggingEnabled} />
                    ) : undefined
                }
            />

            <DashboardFormGroup
                label={t("Enable Tagging")}
                description={t(
                    "Tagging allows users to add a tag to discussions they start in order to make them more discoverable.",
                )}
                labelType={DashboardLabelType.WIDE}
                tag="div"
            >
                <DashboardToggle
                    checked={taggingEnabled}
                    onChange={async (enabled) => {
                        await patchConfig({ "tagging.enabled": enabled });
                        setTaggingEnabled(enabled);
                    }}
                />
            </DashboardFormGroup>

            <DashboardFormGroup
                label={t("Enable Scoped Tagging")}
                description={t(
                    "When scoped tagging is enabled, you can assign tags to specific categories or subcommunities.",
                )}
                labelType={DashboardLabelType.WIDE}
                tag="div"
            >
                <ConditionalWrap
                    component={ToolTip}
                    condition={!taggingEnabled}
                    componentProps={{ label: t("Tagging must be enabled to enable scoped tagging.") }}
                >
                    <span>
                        <DashboardToggle
                            disabled={!taggingEnabled}
                            checked={taggingEnabled && scopedTaggingEnabled}
                            onChange={async (enabled) => {
                                await patchConfig({ "tagging.scopedTagging.enabled": enabled });
                                setScopedTaggingEnabled(enabled);
                            }}
                        />
                    </span>
                </ConditionalWrap>
            </DashboardFormGroup>

            {taggingEnabled && (
                <TagsRequestContextProvider initialRequestBody={initialRequestBody}>
                    <TagsRequestContext.Consumer>
                        {(contextValue) => {
                            const context = contextValue!;
                            const { limit, ...rest } = context.requestBody; //we want everything except `limit` in the query string
                            return (
                                <>
                                    <QueryString value={rest} syncOnFirstMount />
                                    <TaggingSettingsImpl scopeEnabled={scopedTaggingEnabled} />
                                </>
                            );
                        }}
                    </TagsRequestContext.Consumer>
                </TagsRequestContextProvider>
            )}
        </ErrorPageBoundary>
    );
}
