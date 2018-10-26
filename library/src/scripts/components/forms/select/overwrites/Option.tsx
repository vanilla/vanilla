/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import DateTime from "@library/components/DateTime";
import LocationBreadcrumbs from "@knowledge/modules/locationPicker/components/LocationBreadcrumbs";

export default function Option(props: any) {
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
                            <span className="metas">
                                {dateUpdated && (
                                    <span className="meta">
                                        <DateTime className="meta" timestamp={dateUpdated} />
                                    </span>
                                )}
                                {hasLocationData && (
                                    <span className="meta">{LocationBreadcrumbs.renderString(locationData)}</span>
                                )}
                            </span>
                        </span>
                    )}
            </button>
        </li>
    );
}
