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

export default function menuOption(props: any) {
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
                {dateUpdated &&
                    hasLocationData && (
                        <span className="suggestedTextInput-main">
                            <span className="metas isFlexed">
                                {dateUpdated && (
                                    <span className="meta">
                                        <DateTime className="meta" timestamp={dateUpdated} />
                                    </span>
                                )}
                                {hasLocationData && (
                                    <BreadCrumbString className="meta">{locationData}</BreadCrumbString>
                                )}
                            </span>
                        </span>
                    )}
            </button>
        </li>
    );
}
