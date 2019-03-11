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
import { metasClasses } from "@library/styles/metasStyles";

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
        const classesMetas = metasClasses();
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
                        <span className={classNames("isFlexed", classesMetas.root)}>
                            {dateUpdated && (
                                <span className={classesMetas.meta}>
                                    <DateTime className={classesMetas.meta} timestamp={dateUpdated} />
                                </span>
                            )}
                            {hasLocationData && <BreadCrumbString className={classesMetas.meta} crumbs={crumbs} />}
                        </span>
                    </span>
                </SmartLink>
            </li>
        );
    } else {
        return <SelectOption {...props} />;
    }
}
