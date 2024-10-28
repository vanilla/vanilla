/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import {
    IBaseSchemaFormProps,
    IControlGroupProps,
    ISchemaRenderProps,
    ISchemaTab,
    ISectionProps,
    IFormTab,
    ITabsControl,
    JsonSchema,
} from "./types";
import { PartialSchemaForm } from "./PartialSchemaForm";

interface IPartialProps extends IBaseSchemaFormProps, ISchemaRenderProps {
    isRequired?: boolean;
    onChange(instance: any): void;
}

export function TabbedSchemaForm(props: IPartialProps) {
    let { rootInstance, instance, schema, rootSchema, path, FormTabs, onChange } = props;

    const control: ITabsControl = schema["x-control"];
    const propertyName = control.property;
    const tabIDs = schema.oneOf!.map((tabSchema) => tabSchema.properties[propertyName].const);

    const selectedTabID = instance[propertyName] ?? tabIDs[0];
    const setSelectedTabID = (tabID: string) =>
        onChange({
            ...instance,
            [propertyName]: tabID,
        });

    const tabs = schema.oneOf!.map((tabSchema): IFormTab => {
        const tabProperty = tabSchema.properties[propertyName];
        const tabID = tabProperty.const;
        return {
            tabID,
            contents: <PartialSchemaForm {...props} inheritSchema={schema} schema={tabSchema as JsonSchema} />,
            label: control.choices!.staticOptions![tabID],
        };
    });

    if (!FormTabs) {
        return null;
    }
    return (
        <FormTabs
            tabs={tabs}
            selectedTabID={selectedTabID}
            onSelectTab={setSelectedTabID}
            path={path}
            pathString={`/${path.join("/")}`}
            errors={[]}
            rootInstance={rootInstance}
            instance={instance}
            rootSchema={rootSchema}
            schema={schema}
        />
    );
}
