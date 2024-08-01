/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import SelectOne, { ISelectOneProps } from "@library/forms/select/SelectOne";
import { t } from "@library/utility/appUtils";
import React from "react";
import { IComboBoxOption } from "@library/features/search/SearchBar";

interface IProps extends ISelectOneProps {
    className?: string;
    placeholder?: string;
}

export default function RoleInput(props: IProps) {
    const options: IComboBoxOption[] = [];

    return <SelectOne {...props} options={options} />;
}
