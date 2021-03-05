/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Property } from "csstype";
import { important } from "csx";
import { styleFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { CSSObject } from "@emotion/css";

export const userSelect = (value: Property.UserSelect = "none", isImportant: boolean = false): CSSObject => {
    const val = (isImportant ? important(value) : value) as any;
    return {
        WebkitUserSelect: val,
        MozUserSelect: val,
        msUserSelect: val,
        userSelect: val,
    };
};

export const pointerEvents = (value: Property.PointerEvents = "none") => {
    return {
        pointerEvents: important(value),
    };
};

export const pointerEventsClass = (value: Property.PointerEvents = "none") => {
    const style = styleFactory("pointerEvents");
    return style(pointerEvents(value));
};

export const disabledInput = () => {
    const formElementVars = formElementsVariables();
    return {
        pointerEvents: important("none"),
        ...userSelect("none", true),
        cursor: important("default"),
        opacity: important((formElementVars.disabled.opacity as any).toString()),
    };
};
