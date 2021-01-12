/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@vanilla/i18n";
import React from "react";
import RadioInputAsButton from "@library/forms/radioAsButtons/RadioInputAsButton";
import { RadioGroup } from "@vanilla/library/src/scripts/forms/radioAsButtons/RadioGroup";
import { searchInFilterClasses } from "@library/search/searchInFilter.styles";
import { buttonClasses } from "@library/forms/buttonStyles";

export interface ISearchInButton {
    label: string;
    icon: React.ReactNode;
    data: string;
}

interface IProps {
    activeItem?: string; // same type as data
    setData: (data: string) => void;
    filters: ISearchInButton[];
    endFilters?: ISearchInButton[]; // At the end, separated by vertical line
}

/**
 * Implements filters for search page
 */
export function SearchInFilter(props: IProps) {
    const { filters = [], setData, endFilters = [], activeItem } = props;
    if (filters.length + endFilters.length <= 1) {
        return null; // no filters, or only 1 is not helpful
    }
    const classes = searchInFilterClasses();
    const buttonClass = buttonClasses().radio;
    return (
        <RadioGroup
            accessibleTitle={t("Search in:")}
            setData={setData}
            activeItem={activeItem}
            classes={classes}
            buttonClass={buttonClass}
            buttonActiveClass={buttonClass}
        >
            <>
                {filters.map((filter, i) => {
                    return <RadioInputAsButton buttonAutoMinWidth={true} key={i} {...filter} />;
                })}
                {endFilters.length > 0 && (
                    <>
                        <span className={classes.separator} role="separator" />
                        {endFilters.map((filter, i) => {
                            return <RadioInputAsButton buttonAutoMinWidth={true} key={i} {...filter} />;
                        })}
                    </>
                )}
            </>
        </RadioGroup>
    );
}
