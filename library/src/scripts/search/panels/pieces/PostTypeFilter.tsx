/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */


import React from "react";
import Checkbox, {ICheckbox} from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";

interface IProps {
    types: ICheckbox[]
}

/**
 * Implement search filter panel main component
 */
export function PostTypeFilter(props: IProps) {
    if (props.types.length === 0) {
        return null;
    }

    return (
        <CheckboxGroup label={"What to Search"} grid={true} tight={true}>
            {props.types.map((type) => {
                return <Checkbox {...type}/>
            })}
        </CheckboxGroup>
    );
}
