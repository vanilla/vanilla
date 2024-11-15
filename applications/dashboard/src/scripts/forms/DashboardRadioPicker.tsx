/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { dashboardFormGroupClasses } from "@dashboard/forms/DashboardFormGroup.classes";
import { useDashboardFormStyle } from "@dashboard/forms/DashboardFormStyleContext";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { cx } from "@emotion/css";
import { RadioPicker } from "@library/forms/RadioPicker";

type IProps = React.ComponentProps<typeof RadioPicker> & {
    className?: string;
};

export function DashboardRadioPicker(props: IProps) {
    const { className, ...restProps } = props;
    const { compact } = useDashboardFormStyle();
    return (
        <DashboardInputWrap className={props.className}>
            <RadioPicker compact={compact} {...restProps} />
        </DashboardInputWrap>
    );
}
