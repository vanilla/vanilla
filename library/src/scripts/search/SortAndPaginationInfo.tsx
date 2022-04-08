/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import SelectBox, { ISelectBoxItem, ISelectBoxProps } from "@library/forms/select/SelectBox";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { ResultPaginationInfo } from "@library/result/ResultPaginationInfo";
import { searchMiscellaneousComponentsClasses } from "@library/search/searchMiscellaneousComponents.styles";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n/src";
import * as React from "react";

export interface ISearchSort extends Omit<ISelectBoxProps, "describedBy" | "widthOfParent"> {}

export interface ISearchSortAndPages {
    sortOptions?: ISelectBoxItem[];
    sortValue?: string;
    onSortChange?: (newSortValue: string) => void;
    pages?: ILinkPages;
    alignRight?: boolean;
}

export function SortAndPaginationInfo(props: ISearchSortAndPages) {
    const { sortOptions, sortValue, onSortChange, pages, alignRight } = props;
    const classes = searchMiscellaneousComponentsClasses();
    const sortID = useUniqueID("sortBy");

    const hasSort = sortOptions && sortOptions.length > 1;

    if (!hasSort && !pages) {
        return null;
    }

    const valueOption =
        sortOptions?.find((option) => {
            return option.value === sortValue;
        }) ?? sortOptions?.[0];

    let content = <ResultPaginationInfo pages={props.pages} alignRight={alignRight} />;

    return (
        <div className={classes.root}>
            {sortOptions && sortOptions?.length > 0 && (
                <label className={classes.sort}>
                    <span id={sortID} className={classes.sortLabel}>{`${t("Sort By")}: `}</span>
                    <SelectBox
                        options={sortOptions}
                        value={valueOption}
                        onChange={(option) => {
                            onSortChange?.(option.value);
                        }}
                        describedBy={sortID}
                        widthOfParent={false}
                        renderLeft={false}
                        className={classes.sort}
                    />
                </label>
            )}
            {pages && content}
        </div>
    );
}
