/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { OptionProps } from "react-select/lib/components/Option";

/**
 * Overwrite for the menuOption component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param props
 */
export default function selectOption(props: OptionProps<any>) {
    const { isSelected, isFocused } = props;

    return (
        <li className="suggestedTextInput-item">
            <button
                {...props.innerProps}
                type="button"
                className={classNames("suggestedTextInput-option", {
                    isSelected,
                    isFocused,
                })}
            >
                <span className="suggestedTextInput-head">
                    <span className="suggestedTextInput-title">{props.children}</span>
                </span>
            </button>
        </li>
    );
}
