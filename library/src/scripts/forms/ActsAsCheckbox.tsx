/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent, useState } from "react";
import { actsAsCheckboxClasses } from "@library/forms/ActsAsCheckbox.styles";
import { useUniqueID } from "@library/utility/idUtils";

interface IProps {
    checked?: boolean;
    onChange: () => any;
    children: (props: { checked: boolean; disabled: boolean }) => JSX.Element;
}

const ActsAsCheckbox: FunctionComponent<IProps> = ({ checked = false, onChange, children }) => {
    const classes = actsAsCheckboxClasses();

    const [disabled, setDisabled] = useState(false);
    async function handleChange() {
        setDisabled(true);
        await Promise.resolve(onChange());
        setDisabled(false);
    }

    const id = useUniqueID();

    return (
        <label htmlFor={id} className={classes.label}>
            <input
                id={id}
                type="checkbox"
                className={classes.checkbox}
                onChange={handleChange}
                checked={checked}
                disabled={disabled}
            />
            {children({
                checked,
                disabled,
            })}
        </label>
    );
};

export default ActsAsCheckbox;
