/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";
import RadioButton from "@library/forms/RadioButton";

export function DashboardRadioButton(props: React.ComponentProps<typeof RadioButton>) {
    return <RadioButton {...props} className={checkRadioClasses().dashboardRadioButton} />;
}
