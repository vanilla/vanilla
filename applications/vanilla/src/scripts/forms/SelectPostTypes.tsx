/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { css, cx } from "@emotion/css";
import { JSONSchemaType } from "@library/json-schema-forms";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { RecordID } from "@vanilla/utils";
import React, { useEffect } from "react";

interface IProps {
    ideaCategory: RecordID;
    useDownvotes: boolean;
    UseBestOfIdeation: any[];
    QnaFollowUpNotification: boolean;
    allowedPostTypeIDs: string[];
    formSchema: JSONSchemaType<SchemaProperties>;
    initialValue: boolean;
    fieldName: string;
    contents: string;
    instance: SchemaProperties;
}

interface SchemaProperties {
    hasRestrictedPostTypes: boolean;
    allowedPostTypeIDs: string[];
}

export function SelectPostTypes(props: IProps) {
    const [values, setValues] = React.useState<SchemaProperties>(props.instance);

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
                <input
                    type="hidden"
                    name={"hasRestrictedPostTypes"}
                    defaultValue={JSON.stringify(values?.["hasRestrictedPostTypes"])}
                />
                <input
                    type="hidden"
                    name={props.fieldName}
                    defaultValue={JSON.stringify(values?.["allowedPostTypeIDs"] ?? [])}
                />
                <DashboardSchemaForm schema={props.formSchema} instance={values} onChange={setValues} />
            </div>
        </>
    );
}
