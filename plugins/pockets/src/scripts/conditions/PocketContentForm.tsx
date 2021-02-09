/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardCodeEditor } from "@dashboard/forms/DashboardCodeEditor";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardFormGroupPlaceholder } from "@dashboard/forms/DashboardFormGroupPlaceholder";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardLegacyFormValue } from "@dashboard/forms/DashboardLegacyFormValue";
import { DashboardSelectLookup } from "@dashboard/forms/DashboardSelectLookup";
import { WidgetFormGenerator } from "@dashboard/widgets/WidgetFormGenerator";
import { t } from "@vanilla/i18n";
import { IComboBoxOption } from "@vanilla/library/src/scripts/features/search/SearchBar";
import { ILookupApi } from "@vanilla/library/src/scripts/forms/select/SelectLookup";
import { LoadingRectangle } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";
import React, { useEffect, useState } from "react";

interface IProps {
    widgetID?: string | null;
    initialWidgetParameters?: Record<any, any>;
    format?: string;
    initialBody?: string;
    forceLoading?: boolean;
}

const widgetsApi: ILookupApi = {
    searchUrl: "/widgets",
    singleUrl: "/widgets/%s",
    valueKey: "widgetID",
    labelKey: "name",
    excludeLookups: ["raw"],
    processOptions: (options) => {
        const customOption: IComboBoxOption = {
            label: t("Custom HTML"),
            value: "raw",
        };
        return [customOption, ...options];
    },
};

export function PocketContentForm(props: IProps) {
    const { initialWidgetParameters, initialBody } = props;
    const initialValue = props.format === "widget" && props.widgetID ? props.widgetID : "raw";
    const [typeOption, setTypeOption] = useState<IComboBoxOption | null>(
        initialValue === "raw"
            ? {
                  label: t("Custom HTML"),
                  value: "raw",
              }
            : null,
    );
    const [mountCounter, setMountCounter] = useState(0);
    const [values, setValues] = useState(props.initialWidgetParameters ?? {});
    const [body, setBody] = useState("");

    useEffect(() => {
        setValues(initialWidgetParameters ?? {});
        setMountCounter((value) => value + 1);
    }, [initialWidgetParameters]);

    useEffect(() => {
        setBody(initialBody ?? "");
        setMountCounter((value) => value + 1);
    }, [initialBody]);

    return (
        <>
            <ul>
                <DashboardFormGroup
                    label={t("Type")}
                    description={t("Create your own custom Pocket, or configure one of our existing widgets.")}
                >
                    {typeOption?.value === "raw" ? (
                        <DashboardLegacyFormValue formKey="Format" value={typeOption.value} />
                    ) : (
                        <>
                            <DashboardLegacyFormValue formKey="Format" value={"widget"} />
                            <DashboardLegacyFormValue formKey="WidgetID" value={typeOption?.value} />
                        </>
                    )}
                    <DashboardSelectLookup
                        onChange={(val) => {
                            setTypeOption(val);
                        }}
                        isClearable={false}
                        onInitialValueLoaded={setTypeOption}
                        value={typeOption?.value ?? initialValue}
                        api={widgetsApi}
                    />
                </DashboardFormGroup>
            </ul>
            {!typeOption || props.forceLoading ? (
                <ul>
                    <DashboardFormSubheading>
                        <LoadingRectangle height={18} width={"200px"} />
                    </DashboardFormSubheading>

                    <DashboardFormGroupPlaceholder descriptionLines={2} />
                    <DashboardFormGroupPlaceholder />
                    <DashboardFormGroupPlaceholder />
                </ul>
            ) : (
                <section>
                    <DashboardFormSubheading>{typeOption.label}</DashboardFormSubheading>
                    {typeOption.value === "raw" ? (
                        <DashboardFormGroup
                            label={t("Custom HTML")}
                            description={t(
                                "Enter the text of the pocket. This will be output exactly as you type it so make sure that you enter valid HTML.",
                            )}
                        >
                            <DashboardCodeEditor key={mountCounter} inputName={"Body"} initialValue={body} />
                        </DashboardFormGroup>
                    ) : (
                        <>
                            <WidgetFormGenerator
                                key={mountCounter}
                                schema={typeOption.data.schema}
                                onChange={setValues}
                                instance={values}
                            />
                            <DashboardLegacyFormValue formKey="WidgetParameters" value={values} />
                        </>
                    )}
                </section>
            )}
        </>
    );
}
