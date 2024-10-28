/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { dashboardFormGroupClasses } from "@dashboard/forms/DashboardFormGroup.classes";
import { FormGroupContext } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { useDashboardFormStyle } from "@dashboard/forms/DashboardFormStyleContext";
import { cx } from "@emotion/css";
import { useContext } from "react";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
    isInline?: boolean;
    isGrid?: boolean;
    isVertical?: boolean;
}

export const DashboardInputWrap = React.forwardRef(function DashboardInputWrap(
    props: IProps,
    ref: React.Ref<HTMLDivElement>,
) {
    const { className, children, isGrid, isInline, isVertical, ...rest } = props;
    const formGroup = useContext(FormGroupContext);

    const groupClasses = dashboardFormGroupClasses();

    const { labelType } = formGroup || {};
    let rootClass = (() => {
        switch (labelType) {
            case DashboardLabelType.WIDE:
            case DashboardLabelType.JUSTIFIED:
                return groupClasses.inputWrapRight;
            case DashboardLabelType.NONE:
                return groupClasses.inputWrapNone;
            case DashboardLabelType.STANDARD:
            default:
                return groupClasses.inputWrap;
        }
    })();

    const formStyle = useDashboardFormStyle();

    return (
        <div
            ref={ref}
            className={cx(
                rootClass,
                "modernInputWrap",
                { isCompact: formStyle.compact, isGrid, isInline, isVertical },
                props.className,
            )}
            {...rest}
        >
            {props.children}
        </div>
    );
});
