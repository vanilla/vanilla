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
import { t } from "@vanilla/i18n";

interface IProps {
    label: React.ReactNode;
    description?: string | React.ReactNode;
    afterDescription?: React.ReactNode;
    inputType?: string;
    labelType?: DashboardLabelType;
    tooltip?: string;
    fieldset?: boolean;
    required?: boolean;
}

export enum DashboardLabelType {
    STANDARD = "standard",
    WIDE = "wide",
    NONE = "none", //for cases when component for inputType already has label code to render
}

export const DashboardFormLabel: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelID } = useFormGroup();

    const { labelType = DashboardLabelType.STANDARD, fieldset = false } = props;
    const { required, tooltip, label, description, afterDescription } = props;
    const rootClass = labelType === DashboardLabelType.WIDE ? "label-wrap-wide" : "label-wrap";

    if (props.inputType === "checkBox") {
        // Just a spacer or no label element at all.
        return labelType === DashboardLabelType.NONE ? <></> : <div className={rootClass} id={labelID} />;
    }

    const LabelOrDiv = fieldset ? "div" : ("label" as ElementType);

    return (
        <div className={rootClass} id={labelID}>
            <ConditionalWrap
                condition={!!tooltip || !!required}
                component={ToolTip}
                componentProps={{ label: required ? t("Required field") : tooltip }}
            >
                <LabelOrDiv htmlFor={!fieldset ? inputID : undefined} className={cx(dashboardClasses().label)}>
                    {required && (
                        <span aria-label={t("required")} className={cx(dashboardClasses().labelRequired)}>
                            *
                        </span>
                    )}
                    {label}
                    {tooltip && <InformationIcon className={dashboardClasses().labelIcon} />}
                </LabelOrDiv>
            </ConditionalWrap>

            {!!description && (
                <>
                    {typeof description === "string" ? (
                        <p className="info" dangerouslySetInnerHTML={{ __html: description }} />
                    ) : (
                        <p className="info">{description}</p>
                    )}
                </>
            )}
            {afterDescription}
        </div>
    );
};
