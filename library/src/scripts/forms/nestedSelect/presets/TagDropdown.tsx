/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INestedSelectProps, NestedSelect } from "@library/forms/nestedSelect";
import { Select } from "@library/json-schema-forms";
import { useState } from "react";

export function TagDropdown(_props: INestedSelectProps) {
    const { prefix = "tag-select", onInputChange, ...props } = _props;
    const [inputValue, setInputValue] = useState<string>(props.inputValue ?? "");

    const optionsLookup: Select.LookupApi = {
        searchUrl: "/tags?type=User&query=%s",
        singleUrl: "/tags/%s",
        labelKey: "name",
        valueKey: "tagID",
    };

    const handleInputChange: INestedSelectProps["onInputChange"] = (newValue = "") => {
        setInputValue(newValue);
        onInputChange?.(newValue);
    };

    return (
        <NestedSelect
            {...props}
            prefix={prefix}
            optionsLookup={optionsLookup}
            onInputChange={handleInputChange}
            inputValue={inputValue}
        />
    );
}
