/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { flattenObject } from "@vanilla/utils";
import React from "react";

interface IProps {
    formKey: string; // Key of the form in dot notation. Eg. 'Options.MyKey'
    value: any;
    flatten?: boolean;
}

export function DashboardLegacyFormValue(props: IProps) {
    const { formKey, value, flatten } = props;
    const dottedKey = formKey.replace(".", "-dot-");
    if (value == null) {
        return <input type="hidden" value={""} name={dottedKey} style={{ display: "none" }} />;
    } else if (typeof value === "object") {
        if (flatten) {
            const flattened = flattenObject(value, formKey);
            return (
                <>
                    {Object.entries(flattened).map(([key, value]) => {
                        return <DashboardLegacyFormValue key={key} formKey={key} value={value} />;
                    })}
                </>
            );
        } else {
            const json = JSON.stringify(value);
            return <input type="hidden" value={json} name={dottedKey} style={{ display: "none" }} />;
        }
    } else {
        return <input type="hidden" value={value} name={dottedKey} style={{ display: "none" }} />;
    }
}
