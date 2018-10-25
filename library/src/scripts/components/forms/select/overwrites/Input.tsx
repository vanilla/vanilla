/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";
import classNames from "classnames";

export default function Input(props: any) {
    if (props.isHidden) {
        return <components.Input {...props} />;
    }
    return <components.Input styles={{}} className={classNames(`${props.prefix}-textInput`, "suggestedTextInput-textInput")} />;
}
