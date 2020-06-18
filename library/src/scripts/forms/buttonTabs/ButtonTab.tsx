/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { buttonTabClasses } from "@library/forms/buttonTabs/buttonTabStyles";
import RadioTab from "@library/forms/radioTabs/RadioTab";
import { buttonClasses } from "@library/forms/buttonStyles";
import { withTabs } from "@library/contexts/TabContext";
import { ITabProps } from "@library/forms/radioTabs/RadioAsButton";

interface IProps extends Omit<ITabProps, "classes" | "active"> {}

/**
 * Implement what looks like buttons, but what is semantically radio button. To be used in the RadioButtonsAsTabs component
 */
export function ButtonTab(props: IProps) {
    const classes = buttonClasses();
    return (
        <RadioTab
            {...props}
            classes={buttonTabClasses()}
            customTabActiveClass={classes.primary}
            customTabInactiveClass={classes.standard}
            active={props.activeItem !== undefined ? props.activeItem === props.data : false}
        />
    );
}

export default withTabs<ITabProps>(ButtonTab);
