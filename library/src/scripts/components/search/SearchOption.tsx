/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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

export interface ISearchOptionData {
    crumbs: ICrumb[];
    name: string;
    dateUpdated: string;
}

interface IProps extends OptionProps<any> {
    data: IComboBoxOption<ISearchOptionData>;
}

/**
 */
export default function SearchOption(props: IProps) {
    const { data, innerProps, isSelected, isFocused } = props;

    if (data.data) {
        const { dateUpdated, crumbs } = data.data!;
        const hasLocationData = crumbs && crumbs.length > 0;
        return (
            <li className="suggestedTextInput-item">
                <button
                    {...innerProps}
                    type="button"
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
                </button>
            </li>
        );
    } else {
        return <SelectOption {...props} />;
    }
}
