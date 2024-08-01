/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { searchWidgetClasses } from "./SearchWidget.classes";
import SelectOne from "@library/forms/select/SelectOne";
import { Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IControlProps, IForm, JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { SelectLookup } from "@library/forms/select/SelectLookup";
import { useWidgetSectionClasses } from "@library/layout/WidgetLayout.context";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { ISearchWidgetOptions, searchWidgetVariables } from "@library/searchWidget/SearchWidget.variables";
import { t } from "@vanilla/i18n";

interface ISearchWidgetProps {
    title: string;
    formSchema: JsonSchema;
    options?: ISearchWidgetOptions;
}

const FormControl = (props: IControlProps) => {
    const { disabled, onChange, control, instance, required } = props;

    switch (control.inputType) {
        case "dropDown": {
            const { label, choices, placeholder } = control;
            const { api, staticOptions } = choices;
            if (api) {
                return (
                    <SelectLookup
                        label={label!}
                        disabled={disabled}
                        isClearable={!props.required}
                        placeholder={placeholder || undefined}
                        value={instance}
                        onChange={(option) => onChange(option?.value)}
                        api={api}
                    />
                );
            }
            const options = staticOptions
                ? Object.entries(staticOptions).map(([value, label]: [string, string]) => ({
                      value,
                      label,
                  }))
                : [];
            return (
                <SelectOne
                    label={label!}
                    disabled={disabled}
                    isClearable={!required}
                    value={options.find((opt) => opt.value === String(instance))}
                    placeholder={placeholder || undefined}
                    onChange={(option) => onChange(option?.value)}
                    options={options}
                />
            );
        }
    }
    return null;
};

function buildFormUrl({ url, searchParams }: IForm) {
    const queryStr = searchParams
        ? "?" +
          Object.entries(searchParams)
              .filter(([key, value]) => value?.length)
              .map(([key, value]) => key + "=" + value)
              .join("&")
        : "";
    return url + queryStr;
}

export function SearchWidget(props: ISearchWidgetProps) {
    const vars = searchWidgetVariables(props.options);
    const { options } = vars;
    const { formSchema } = props;
    const classes = searchWidgetClasses();
    const widgetClasses = useWidgetSectionClasses();
    const [value, setValue] = useState({});

    return (
        <div className={widgetClasses.widgetClass}>
            <PageHeadingBox
                title={props.title}
                options={{
                    alignment: options.headerAlignment,
                }}
            />
            <div className={classes.container}>
                <JsonSchemaForm
                    schema={formSchema}
                    instance={value}
                    onChange={setValue}
                    FormControl={FormControl}
                    Form={(props) => (
                        <>
                            {props.children}
                            <div className={classes.tabFooter}>
                                <LinkAsButton to={buildFormUrl(props.form)} buttonType={ButtonTypes.PRIMARY}>
                                    {t(props.form.submitButtonText ?? "Submit")}
                                </LinkAsButton>
                            </div>
                        </>
                    )}
                    FormTabs={(props) => (
                        <Tabs
                            tabType={TabsTypes.GROUP}
                            data={props.tabs}
                            defaultTabIndex={props.tabs.findIndex((t) => t.tabID === props.selectedTabID)}
                            onChange={(tab) => props.onSelectTab(tab.tabID! as string)}
                        />
                    )}
                />
            </div>
        </div>
    );
}
