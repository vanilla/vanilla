/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { TextInput, InputValidationFilter } from "@library/forms/TextInput";
import { mountReact } from "@library/dom/domUtils";
import { logDebug } from "@vanilla/utils";

/**
 * Mount <TextInput /> components on top of <input data-react-input/> elements.
 *
 * If the input has a "data-validation-filter" that will be applied as the validationFilter.
 * Ideal for enhancing GDN_Form rendered forms.
 */
export function mountInputs() {
    const inputs = document.querySelectorAll("[data-react-input]");
    logDebug(`Mounting React inputs over ${inputs.length} existing inputs.`);
    inputs.forEach(input => {
        if (input instanceof HTMLInputElement) {
            const validationFilter = input.getAttribute("data-validation-filter") as InputValidationFilter;
            mountReact(
                <TextInput
                    validationFilter={validationFilter}
                    defaultValue={input.value}
                    id={input.id}
                    className={input.className}
                    name={input.name}
                />,
                input,
                undefined,
                { overwrite: true },
            );
        }
    });
}
