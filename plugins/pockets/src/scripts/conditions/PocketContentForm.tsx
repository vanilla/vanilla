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
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ILookupApi } from "@library/forms/select/SelectLookup";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import React, { useEffect, useState } from "react";
import { JsonSchema } from "@vanilla/json-schema-forms";

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

    /**
     * Some widget schema's are shared with layout widget and handle default values differently.
     * This function will set default values from the schema on the initial parameters if they are not already set.
     *
     * This should prevent schema resolution and validation from accessing native functions like `sort` on empty arrays.
     */
    const setDefaultValuesOnInitialParams = (schema: JsonSchema, values: object): object => {
        let defaultValues = {};
        // Iterate through the schema
        Object.keys(schema).forEach((key) => {
            // If we don't already have a value for this key, set it
            if (!values.hasOwnProperty(key)) {
                if (schema[key].hasOwnProperty("properties")) {
                    const childValues = setDefaultValuesOnInitialParams(schema[`${key}`].properties, {});
                    defaultValues[`${key}`] = childValues;
                } else {
                    const property = schema[key] ?? {};
                    if (property.hasOwnProperty("default") && property.default !== null) {
                        // If the property has a default value, set it
                        defaultValues[`${key}`] = property.default;
                    }
                }
            }
        });
        return defaultValues;
    };

    useEffect(() => {
        const mergedValues = setDefaultValuesOnInitialParams(
            typeOption?.data?.schema?.properties ?? {},
            initialWidgetParameters ?? {},
        );
        setValues(mergedValues);
        setMountCounter((value) => value + 1);
    }, [initialWidgetParameters]);

    useEffect(() => {
        setBody(initialBody ?? "");
        setMountCounter((value) => value + 1);
    }, [initialBody]);

    useEffect(() => {
        if (typeOption?.data?.schema?.properties) {
            const mergedValues = setDefaultValuesOnInitialParams(
                typeOption.data.schema.properties,
                initialWidgetParameters ?? {},
            );
            setValues(mergedValues);
        }
    }, [typeOption]);

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
                            <DashboardCodeEditor.Uncontrolled
                                key={mountCounter}
                                inputName={"Body"}
                                initialValue={body}
                            />
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
