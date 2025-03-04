/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import Button from "@library/forms/Button";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ButtonTypes } from "@library/forms/buttonTypes";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import { JSONSchemaType, JsonSchemaForm } from "@vanilla/json-schema-forms";
import React, { useEffect, useState } from "react";
import { MemoryRouter } from "react-router";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";

export interface IExternalSearchSettings {
    externalSearchQuery: string;
    externalSearchResultsInNewTab: boolean;
}

export function ExternalSearchSettingsPage() {
    const configs = useConfigsByKeys(["externalSearch.query", "externalSearch.resultsInNewTab"]);
    const configPatcher = useConfigPatcher();
    const externalSearchValuesFromConfig = {
        externalSearchQuery: configs.data?.["externalSearch.query"],
        externalSearchResultsInNewTab: configs.data?.["externalSearch.resultsInNewTab"],
    };
    const [value, setValue] = useState<IExternalSearchSettings>(externalSearchValuesFromConfig ?? {});
    const [error, setError] = useState(null);
    useEffect(() => {
        setValue(externalSearchValuesFromConfig ?? {});
    }, [configs.data]);

    const schema: JSONSchemaType<IExternalSearchSettings> = {
        type: "object",
        properties: {
            externalSearchQuery: {
                type: "string",
                "x-control": {
                    label: t("Search Query"),
                    inputType: "textBox",
                    description: t(
                        "Enter full search query for our searchboxes to point to. Use %s as a placeholder for the search term. For example: https://www.google.com/search?q=%s",
                    ),
                },
            },
            externalSearchResultsInNewTab: {
                type: "boolean",
                default: false,
                "x-control": {
                    label: t("Search Result in New Tab"),
                    inputType: "toggle",
                    description: t("When enabled, search result will open in a new browser tab."),
                    labelType: DashboardLabelType.WIDE,
                },
            },
        },
    };

    return (
        <MemoryRouter>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    try {
                        void configPatcher.patchConfig({
                            "externalSearch.query": value.externalSearchQuery,
                            "externalSearch.resultsInNewTab": value.externalSearchResultsInNewTab,
                        });
                    } catch (error) {
                        setError(error);
                    }
                }}
            >
                <DashboardHeaderBlock
                    title={t("External Search")}
                    actionButtons={
                        <Button
                            buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                            disabled={configs.status === LoadStatus.LOADING}
                            submit
                        >
                            {t("Save")}
                        </Button>
                    }
                />

                <section>
                    {error && (
                        <div style={{ padding: 18 }}>
                            <ErrorMessages errors={[error]} />
                        </div>
                    )}
                    <ErrorBoundary>
                        <DashboardFormList>
                            <DashboardSchemaForm
                                disabled={configs.status !== LoadStatus.SUCCESS}
                                schema={schema}
                                instance={value}
                                onChange={setValue}
                            />
                        </DashboardFormList>
                    </ErrorBoundary>
                    <DashboardHelpAsset>
                        <h3>{t("About External Search")}</h3>
                        <p>
                            {t(
                                "External search replaces Vanilla’s out of the box search with a third party search provider of your choice.",
                            )}
                        </p>
                        <h3>{t("Need more help?")}</h3>
                        <p>
                            <SmartLink to="https://success.vanillaforums.com/kb/articles/1542-external-search">
                                {t("External Search")}
                            </SmartLink>
                        </p>
                    </DashboardHelpAsset>
                </section>
            </form>
        </MemoryRouter>
    );
}
