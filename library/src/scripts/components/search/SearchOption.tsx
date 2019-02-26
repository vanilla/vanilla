/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import DateTime from "../DateTime";
import BreadCrumbString from "../BreadCrumbString";
import { OptionProps } from "react-select/lib/components/Option";
import { SelectOption } from "../forms/select/overwrites";
import classNames from "classnames";
import { IComboBoxOption } from "@library/components/forms/select/SearchBar";
import { ICrumb } from "@library/components/Breadcrumbs";
import SmartLink from "@library/components/navigation/SmartLink";

export interface ISearchOptionData {
    crumbs: ICrumb[];
    name: string;
    dateUpdated: string;
    url: string;
}

interface IProps extends OptionProps<ISearchOptionData> {
    data: IComboBoxOption<ISearchOptionData>;
}

/**
 */
export default function SearchOption(props: IProps) {
    const { innerProps, isSelected, isFocused } = props;
    const data = props.data.data;

    if (data) {
        const { dateUpdated, crumbs, url } = data;
        const hasLocationData = crumbs && crumbs.length > 0;
        return (
            <li className="suggestedTextInput-item">
                <SmartLink
                    {...innerProps as any}
                    // We want to use the SmarkLink clickHandler, not the innerProps one from the SearchBar.
                    // The innerProps click handler will trigger a search event (goes to search page).
                    // The SmartLink will navigate to the result itself.
                    onClick={undefined}
                    to={url}
                    title={props.label}
                    aria-label={props.label}
                    className={classNames("suggestedTextInput-option", {
                        isSelected,
                        isFocused,
                    })}
                >
                    <span className="suggestedTextInput-head">
                        <span className="suggestedTextInput-title">{props.children}</span>
                    </span>
                    <span className="suggestedTextInput-main">
                        <span className="metas isFlexed">
                            {dateUpdated && (
                                <span className="meta">
                                    <DateTime className="meta" timestamp={dateUpdated} />
                                </span>
                            )}
                            {hasLocationData && <BreadCrumbString className="meta" crumbs={crumbs} />}
                        </span>
                    </span>
                </SmartLink>
            </li>
        );
    } else {
        return <SelectOption {...props} />;
    }
}
