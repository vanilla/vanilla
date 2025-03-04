/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { css, cx } from "@emotion/css";
import { extractSchemaDefaults, JSONSchemaType } from "@library/json-schema-forms";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import React, { useEffect } from "react";

interface IProps {
    instance: SchemaProperties;
    schema: JSONSchemaType<SchemaProperties>;
}

interface SchemaProperties {
    hasRestrictedPostTypes: boolean;
    allowedPostTypeIDs: string[];
}

export function SelectPostTypes(props: IProps) {
    const defaults = extractSchemaDefaults(props.schema);

    const initialValues = {
        ...defaults,
        ...props.instance,
    };

    const [values, setValues] = React.useState<SchemaProperties>(initialValues);

    const rowClasses = css({
        // Fighting with old styles here
        "& div[class*='formToggle-well'], & div[class*='formToggle-slider']": {
            borderColor: "#949aa2",
        },
        "& li:last-child": {
            borderBottom: singleBorder(),
        },
    });

    useEffect(() => {
        if (values?.hasRestrictedPostTypes === false && values?.allowedPostTypeIDs.length > 0) {
            setValues({ ...values, allowedPostTypeIDs: [] });
        }
    }, [values]);

    return (
        <>
            <div className={cx(rowClasses)}>
                {Object.entries(values).map(([key, value]) => {
                    return <input type="hidden" key={key} name={key} defaultValue={JSON.stringify(value)} />;
                })}
                <DashboardSchemaForm schema={props.schema} instance={values} onChange={setValues} />
            </div>
        </>
    );
}
