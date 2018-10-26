/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import DateTime from "@library/components/DateTime";
import { t } from "@library/application";
import LocationBreadcrumbs from "@knowledge/modules/locationPicker/components/LocationBreadcrumbs";
import { IKbCategoryFragment } from "@knowledge/@types/api";

export default function Option(props: any) {
    console.log("option props; ", props);
    const { data } = props;
    const { dateUpdated, locationData } = data;

    return (
        <li className={classNames(`${props.prefix}-item`, "suggestedTextInput-item")}>
            <button
                type="button"
                title={props.children}
                aria-label={props.children}
                className="suggestedTextInput-option"
            >
                <span className="suggestedTextInput-head">
                    <span className="suggestedTextInput-title">{props.children}</span>
                </span>
                <span className="suggestedTextInput-main">
                    <span className="metas">
                        <span className="meta">
                            <DateTime className="meta" timestamp={dateUpdated} />
                        </span>
                        <span className="meta">{t("location")}</span>
                        {locationData &&
                            locationData.length > 0 && (
                                <span className="meta">{LocationBreadcrumbs.renderString(locationData)}</span>
                            )}
                    </span>
                </span>
            </button>
        </li>
    );
}
