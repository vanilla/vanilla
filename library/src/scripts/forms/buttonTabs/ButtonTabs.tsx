/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { buttonTabClasses } from "@library/forms/buttonTabs/buttonTabStyles";
import RadioTabs, { IRadioTabsProps } from "@library/forms/radioTabs/RadioTabs";

interface IProps extends Omit<IRadioTabsProps, "classes"> {}

/**
 * Implement what looks like buttons, but what is semantically radio buttons.
 */
export function ButtonTabs(props: IProps) {
    return (
        <RadioTabs
            accessibleTitle={props.accessibleTitle}
            groupName={props.groupName ?? "radioButtonsAsButtons"}
            setData={props.setData}
            activeTab={props.activeTab}
            classes={buttonTabClasses()}
        >
            {props.children}
        </RadioTabs>
    );
}
