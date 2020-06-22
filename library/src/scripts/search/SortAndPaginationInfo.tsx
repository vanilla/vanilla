/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import {searchMiscellaneousComponentsClasses} from "@library/search/searchMiscellaneousComponents.styles";
import SelectBox, {IExternalLabelledProps, ISelfLabelledProps} from "@library/forms/select/SelectBox";
import * as React from "react";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import Translate from "@library/content/Translate";
import {t} from "@vanilla/i18n/src";
import classNames from "classnames";
import {useUniqueID} from "@library/utility/idUtils";
import {
    numberFormattedForTranslations
} from "@library/content/NumberFormatted";

export interface ISearchSort extends Omit<IExternalLabelledProps, "describedBy" | "widthOfParent"> {}

export interface ISearchPaginationInfo {
    resultStart: number;
    resultEnd: number;
    total: number;
}

export interface ISearchSortAndPaginationInfo {
    sort?: ISelfLabelledProps,
    paginationInfo?: ISearchPaginationInfo,
}


export function SortAndPaginationInfo(props: ISearchSortAndPaginationInfo) {
    const { sort, paginationInfo } = props;
    if (!sort && !paginationInfo) {
        return null;
    }
    const classes = searchMiscellaneousComponentsClasses();
    const sortID = useUniqueID("sortBy");

    let content = null as null | JSX.Element;
    if (paginationInfo) {
        const resultStart = numberFormattedForTranslations({value: paginationInfo.resultStart});
        const resultEnd = numberFormattedForTranslations({value: paginationInfo.resultEnd});
        const total = numberFormattedForTranslations({value: paginationInfo.total});
        content = (
            <div className={classes.pagination}>
                <ScreenReaderContent>
                    <Translate source={"Result(s) <0/> to <1/> of <2/>"} c0={paginationInfo.resultStart} c1={paginationInfo.resultEnd} c2={paginationInfo.total} />
                </ScreenReaderContent>
                <span aria-hidden={true}>
                    <Translate source={"<0/>-<1/> of <2/>"} c0={resultStart} c1={resultEnd} c2={total} />
                </span>
            </div>
        );
    }


    return <div className={classes.sortAndPagination}>
        {sort && (
            <label className={classes.sort}>
                <span id={sortID} className={classes.sortLabel}>{`${t("Sort By")}: `}</span>
                <SelectBox
                    {...sort}
                    describedBy={sortID}
                    widthOfParent={false}
                    renderLeft={false}
                    className={classNames(sort && sort['className'] ? sort['className'] : "", classes.sort)}
                />
            </label>
        )}
        {content}
    </div>;
}
