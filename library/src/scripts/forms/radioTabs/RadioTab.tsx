/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IBaseTabProps, ITabProps } from "@library/forms/radioTabs/RadioAsButton";
import { RadioInputAsButton } from "@library/forms/radioAsButtons/RadioInputAsButton";
import { radioTabClasses } from "@library/forms/radioTabs/radioTabStyles";
import { ITabContext, withTabs } from "@library/contexts/TabContext";

/**
 * Implement what looks like tabs (or other inputs with the classes prop), but what is semantically radio buttons.
 */
export function RadioTab(props: IBaseTabProps) {
    const classes = radioTabClasses();
    return (
        <RadioInputAsButton
            {...props}
            labelClass={`${(props.position === "left" ? classes.leftTab : "") +
                (props.position === "right" ? classes.rightTab : "")}`}
        />
    );
}

export default withTabs<ITabProps>(RadioTab);
