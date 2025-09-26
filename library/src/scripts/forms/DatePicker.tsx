/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { inputClasses } from "@library/forms/inputStyles";
import { createLoadableComponent } from "@vanilla/react-utils";

export const DatePicker = createLoadableComponent({
    loadFunction: () => import("./DatePicker.loadable"),
    fallback: (props) => (
        <input
            value={props.value ?? ""}
            onChange={(e) => props.onChange(e.target.value)}
            id={props.id}
            required={props.required}
            type="date"
            role="date"
            aria-label={props.inputAriaLabel}
            className={cx(inputClasses().text, props.inputClassName)}
        />
    ),
});

export default DatePicker;
