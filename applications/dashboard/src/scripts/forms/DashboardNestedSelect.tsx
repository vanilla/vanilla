/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { cx } from "@emotion/css";
import { INestedSelectProps, NestedSelect } from "@library/forms/nestedSelect";
import { CategoryDropdown } from "@library/forms/nestedSelect/presets/CategoryDropdown";
import { TagDropdown } from "@library/forms/nestedSelect/presets/TagDropdown";
import { useMemo } from "react";

interface DashboardNestedSelectProps extends Omit<INestedSelectProps, "inputID" | "labelID"> {
    className?: string;
}

export const DashboardNestedSelect: React.FC<DashboardNestedSelectProps> = (_props: DashboardNestedSelectProps) => {
    const { className, ...props } = _props;
    const { inputID, labelType, labelID } = useFormGroup();
    const classes = dashboardClasses();
    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    return (
        <div className={cx(rootClass, className)}>
            <div className={classes.inputWrapper}>
                <NestedSelect {...props} inputID={inputID} labelID={labelID} />
            </div>
        </div>
    );
};

interface IDashboardNestedSelectPreset extends Omit<INestedSelectProps, "inputID" | "labelID"> {
    className?: string;
    presetType: "category" | "tag";
}

export const DashboardNestedSelectPreset: React.FC<IDashboardNestedSelectPreset> = (
    _props: IDashboardNestedSelectPreset,
) => {
    const { className, presetType, ...props } = _props;
    const { inputID, labelType, labelID } = useFormGroup();
    const classes = dashboardClasses();
    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    const Component = useMemo(() => {
        switch (presetType) {
            case "category":
                return CategoryDropdown;

            case "tag":
                return TagDropdown;
        }

        return null;
    }, [presetType]);

    if (!Component) {
        return null;
    }

    return (
        <div className={cx(rootClass, className)}>
            <div className={classes.inputWrapper}>
                <Component {...props} inputID={inputID} labelID={labelID} />
            </div>
        </div>
    );
};
