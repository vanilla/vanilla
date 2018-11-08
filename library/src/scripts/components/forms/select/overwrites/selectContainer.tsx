/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { SelectContainer, ContainerProps } from "react-select/lib/components/containers";

/**
 * Overwrite for the selectContainer component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param children
 * @param props
 */
export default function selectContainer(props: ContainerProps<any>) {
    return <SelectContainer {...props} className="suggestedTextInput-selectContainer" />;
}
