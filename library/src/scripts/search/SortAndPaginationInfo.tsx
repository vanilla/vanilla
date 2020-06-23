/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import SelectBox, { IExternalLabelledProps, ISelectBoxItem } from "@library/forms/select/SelectBox";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { ResultPaginationInfo } from "@library/result/ResultPaginationInfo";
import { searchMiscellaneousComponentsClasses } from "@library/search/searchMiscellaneousComponents.styles";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n/src";
import * as React from "react";

export interface ISearchSort extends Omit<IExternalLabelledProps, "describedBy" | "widthOfParent" | "describedBy: "> {}

export interface ISearchSortAndPages {
    sortOptions?: ISelectBoxItem[];
    sortValue?: string;
    onSortChange?: (newSortValue: string) => void;
    pages?: ILinkPages;
}

export function SortAndPaginationInfo(props: ISearchSortAndPages) {
    const { sortOptions, sortValue, onSortChange, pages } = props;
    const classes = searchMiscellaneousComponentsClasses();
    const sortID = useUniqueID("sortBy");

    const hasSort = sortOptions && sortOptions.length > 1;

    if (!hasSort && !pages) {
        return null;
    }

    const valueOption = sortOptions?.find(option => {
        return option.value === sortValue;
    });

    let content = <ResultPaginationInfo pages={props.pages} />;

    return (
        <div className={classes.sortAndPagination}>
            {sortOptions && sortOptions?.length > 0 && (
                <label className={classes.sort}>
                    <span id={sortID} className={classes.sortLabel}>{`${t("Sort By")}: `}</span>
                    <SelectBox
                        options={sortOptions}
                        value={valueOption}
                        onChange={option => {
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
