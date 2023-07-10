/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ElementType } from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { ToolTip } from "@library/toolTip/ToolTip";
import { InformationIcon } from "@library/icons/common";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { cx } from "@emotion/css";

interface IProps {
    label: React.ReactNode;
    description?: string | React.ReactNode;
    afterDescription?: React.ReactNode;
    inputType?: string;
    labelType?: DashboardLabelType;
    tooltip?: string;
    fieldset?: boolean;
}

export enum DashboardLabelType {
    STANDARD = "standard",
    WIDE = "wide",
    NONE = "none", //for cases when component for inputType already has label code to render
}

export const DashboardFormLabel: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelID } = useFormGroup();

    const { labelType = DashboardLabelType.STANDARD, fieldset = false } = props;
    const rootClass = labelType === DashboardLabelType.WIDE ? "label-wrap-wide" : "label-wrap";

    if (props.inputType === "checkBox") {
        // Just a spacer or no label element at all.
        return labelType === DashboardLabelType.NONE ? <></> : <div className={rootClass} id={labelID} />;
    }

    const LabelOrDiv = fieldset ? "div" : ("label" as ElementType);

    return (
        <div className={rootClass} id={labelID}>
            <ConditionalWrap condition={!!props.tooltip} component={ToolTip} componentProps={{ label: props.tooltip }}>
                <LabelOrDiv htmlFor={!fieldset ? inputID : undefined} className={cx(dashboardClasses().label)}>
                    {props.label}
                    {props.tooltip && <InformationIcon className={dashboardClasses().labelIcon} />}
                </LabelOrDiv>
            </ConditionalWrap>

            {!!props.description && (
                <>
                    {typeof props.description === "string" ? (
                        <div className="info" dangerouslySetInnerHTML={{ __html: props.description }} />
                    ) : (
                        <div className="info">{props.description}</div>
                    )}
                </>
            )}
            {props.afterDescription}
        </div>
    );
};
