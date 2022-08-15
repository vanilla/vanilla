/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */
import { ColorPicker } from "@dashboard/components/ColorPicker";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { cx } from "@emotion/css";
import InputBlock from "@library/forms/InputBlock";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { inputClasses } from "@library/forms/inputStyles";
import React from "react";

interface IProps {
    /**The valid color hex code*/
    value: string;
    onChange(hexCode: string): void;
    disabled?: boolean;
    placeholder?: string;
    defaultBackground?: string;
}

export function DashboardColorPicker(props: IProps) {
    const dashboardClassNames = dashboardClasses();
    const classes = inputBlockClasses();
    const inputClassNames = inputClasses();
    return (
        <div className="input-wrap">
            <InputBlock noMargin>
                <ColorPicker
                    inputClassName={cx(inputClassNames.inputText, dashboardClassNames.colorInput)}
                    swatchClassName={cx(dashboardClassNames.swatch, { [dashboardClassNames.disabled]: props.disabled })}
                    {...props}
                />
            </InputBlock>
        </div>
    );
}
