/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import ButtonLoader from "@library/components/ButtonLoader";
import { OptionProps } from "react-select/lib/components/Option";
import SelectOption from "@library/components/forms/select/overwrites/selectOption";

/**
 * Overwrite for the menuOption component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param props
 */
export default function LoadingOptions(props: OptionProps<any>) {
    props = {
        ...props,
        children: <ButtonLoader />,
    };

    return <SelectOption {...props} />;
}
