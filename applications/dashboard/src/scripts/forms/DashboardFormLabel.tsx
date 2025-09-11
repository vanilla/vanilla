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
import { dashboardFormGroupClasses } from "@dashboard/forms/DashboardFormGroup.classes";
import { useDashboardFormStyle } from "@dashboard/forms/DashboardFormStyleContext";
import ReactMarkdown from "react-markdown";
import { userContentClasses } from "@library/content/UserContent.styles";
import { DashboardLabelType } from "@dashboard/forms/DashboardLabelType";

interface IDashboardFormLabelProps {
    label: React.ReactNode;
    metas?: React.ReactNode;
    description?: string | React.ReactNode;
    afterDescription?: React.ReactNode;
    inputType?: string;
    labelType?: DashboardLabelType;
    tooltip?: string | React.ReactNode;
    fieldset?: boolean;
    required?: boolean;
    checkPosition?: "left" | "right";
}

export const DashboardFormLabel: React.FC<IDashboardFormLabelProps> = (props: IDashboardFormLabelProps) => {
    const { inputID, labelID } = useFormGroup();

    const { labelType = DashboardLabelType.STANDARD, fieldset = false } = props;
    const formStyle = useDashboardFormStyle();

    if (labelType === DashboardLabelType.NONE) {
        return <></>;
    }

    const { required, tooltip, label, description, afterDescription } = props;
    const classes = dashboardFormGroupClasses();
    let rootClass = (() => {
        switch (labelType) {
            case DashboardLabelType.WIDE:
                return classes.labelWrapWide;
            case DashboardLabelType.STANDARD:
            default:
                return classes.labelWrap;
        }
    })();

    rootClass = cx(rootClass, {
        isCompact: formStyle.compact,
        isVertical: labelType === DashboardLabelType.VERTICAL || formStyle.forceVerticalLabels,
    });

    if (props.inputType === "checkBox" && props.checkPosition !== "right") {
        if (formStyle.compact) {
            return <></>;
        }
        // Just a spacer or no label element at all.
        return <div className={rootClass} id={labelID} />;
    }

    const LabelOrDiv = fieldset ? "div" : ("label" as ElementType);
    const displayAsterisk = required && label && label != "";

    return (
        <div className={rootClass} id={labelID}>
            <ConditionalWrap
                condition={!!tooltip || !!required}
                component={ToolTip}
                componentProps={{ label: tooltip ? tooltip : t("Required field") }}
            >
                <LabelOrDiv htmlFor={!fieldset ? inputID : undefined} className={cx(dashboardClasses().label)}>
                    {displayAsterisk && (
                        <span aria-label={t("required")} className={cx(dashboardClasses().labelRequired)}>
                            *
                        </span>
                    )}
                    <span>
                        {label}
                        {tooltip && <InformationIcon className={dashboardClasses().labelIcon} />}
                    </span>
                </LabelOrDiv>
            </ConditionalWrap>
            {props.metas}

            {!!description && (
                <>
                    {typeof description === "string" ? (
                        <ReactMarkdown className={cx(userContentClasses().root, classes.labelInfo)}>
                            {description}
                        </ReactMarkdown>
                    ) : (
                        <div className={cx(userContentClasses().root, classes.labelInfo)}>{description}</div>
                    )}
                </>
            )}
            {afterDescription}
        </div>
    );
};
