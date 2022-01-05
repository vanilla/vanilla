/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import React, { useContext, useEffect, useMemo } from "react";
import { FormGroupContext } from "./FormGroup";
import { formGroupClasses } from "./FormGroup.styles";

export interface IFormGroupLabelProps {
    id?: string;
    className?: string;
    children?: React.ReactNode | React.ReactNode[];
    description?: React.ReactNode;
}

/**
 * Renders <label> with a labelized inputID and the proper aria attributes.
 * When no children are specified, <FormGroupLabelText /> is rendered.
 * It is possible to customize the component used to render this label with the `as` property.
 */
export const FormGroupLabel = React.forwardRef(function FormGroupLabelImpl(
    props: IFormGroupLabelProps,
    forwardedRef: React.Ref<HTMLLabelElement>,
) {
    const { children, description, id, ...otherProps } = props;
    const { inputID, labelID, setLabelID, setLabel, sideBySide } = useContext(FormGroupContext);
    const classes = useMemo(() => formGroupClasses({ sideBySide }), [sideBySide]);

    useEffect(() => {
        if (id && id !== labelID) {
            setLabelID(id);
        }
    }, [id, labelID, setLabelID]);

    useEffect(() => {
        if (children && typeof children === "string") {
            setLabel(children);
        }
    }, [children]);

    return (
        <label
            htmlFor={inputID}
            id={id || labelID}
            {...otherProps}
            ref={forwardedRef}
            className={cx(classes.labelContainer, props.className)}
        >
            <span className={classes.label}>{children}</span>
            {description && <p className={classes.description}>{description}</p>}
        </label>
    );
});
