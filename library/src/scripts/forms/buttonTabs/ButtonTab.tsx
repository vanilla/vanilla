/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { buttonTabClasses } from "@library/forms/buttonTabs/buttonTabStyles";
import RadioTab, { ITabProps } from "@library/forms/radioTabs/RadioTab";

interface IProps extends Omit<ITabProps, "classes"> {}

/**
 * Implement what looks like buttons, but what is semantically radio button. To be used in the RadioButtonsAsTabs component
 */
export function ButtonTab(props: IProps) {
    return <RadioTab {...props} classes={buttonTabClasses()} />;
}
