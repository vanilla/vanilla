/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import DateTime from "@library/components/DateTime";
import BreadCrumbString from "@library/components/BreadCrumbString";

/**
 * Overwrite for the menuOption component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param props
 */
export default function selectOption(props: any) {
    const { data, innerProps, isFocused } = props;
    const { dateUpdated, locationData } = data;
    const hasLocationData = locationData && locationData.length > 0;

    const handleClick = e => {
        e.preventDefault();
        props.innerProps.onClick();
    };

    return (
        <li className={classNames(`${props.prefix}-item`, "suggestedTextInput-item")}>
            <button
                type="button"
                title={props.children}
                aria-label={props.children}
                className="suggestedTextInput-option"
                onClick={handleClick}
            >
                <span className="suggestedTextInput-head">
                    <span className="suggestedTextInput-title">{props.children}</span>
                </span>
            </button>
        </li>
    );
}
