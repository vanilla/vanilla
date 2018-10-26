/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";
import classNames from "classnames";

/**
 * Overwrite for the input component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param props - input props
 */
export default function input(props: any) {
    if (props.isHidden) {
        return <components.Input {...props} />;
    }
    return <components.Input {...props} className={classNames(`${props.prefix}-textInput`, "suggestedTextInput-textInput")} />;
}
