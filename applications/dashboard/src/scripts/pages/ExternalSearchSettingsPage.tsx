/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
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

export interface IExternalSearchSettings {
    externalSearchQuery: string;
}

export function ExternalSearchSettingsPage() {
    const configs = useConfigsByKeys(["externalSearch.query"]);
    const configPatcher = useConfigPatcher();
    const [value, setValue] = useState<IExternalSearchSettings>(
        { externalSearchQuery: configs.data?.["externalSearch.query"] } ?? {},
    );
    const [error, setError] = useState(null);
    useEffect(() => {
        setValue({ externalSearchQuery: configs.data?.["externalSearch.query"] } ?? {});
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
        },
    };

    return (
        <MemoryRouter>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    try {
                        configPatcher.patchConfig({
                            "externalSearch.query": value.externalSearchQuery,
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
                            <JsonSchemaForm
                                disabled={configs.status !== LoadStatus.SUCCESS}
                                schema={schema}
                                instance={value}
                                FormControlGroup={DashboardFormControlGroup}
                                FormControl={DashboardFormControl}
                                onChange={(value) => {
                                    setValue(value);
                                }}
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
