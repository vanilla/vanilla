import { AppearanceProperty, PointerEventsProperty, UserSelectProperty } from "csstype";
import { important } from "csx";
import { styleFactory } from "@library/styles/styleUtils";

export const appearance = (value: AppearanceProperty = "none", isImportant: boolean = false) => {
    const val = (isImportant ? important(value) : value) as any;
    return {
        "-webkit-appearance": val,
        "-moz-appearance": val,
        appearance: val,
    };
};
