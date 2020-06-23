/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { searchMiscellaneousComponentsClasses } from "@library/search/searchMiscellaneousComponents.styles";
import SelectBox, { IExternalLabelledProps } from "@library/forms/select/SelectBox";
import * as React from "react";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import Translate from "@library/content/Translate";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";
import { useUniqueID } from "@library/utility/idUtils";
import { numberFormattedForTranslations } from "@library/content/NumberFormatted";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { ResultPaginationInfo } from "@library/result/ResultPaginationInfo";

export interface ISearchSort extends Omit<IExternalLabelledProps, "describedBy" | "widthOfParent" | "describedBy: "> {}

export interface ISearchSortAndPages {
    sort: ISearchSort;
    pages?: ILinkPages;
}

export function SortAndPaginationInfo(props: ISearchSortAndPages) {
    const { sort, pages } = props;
    const classes = searchMiscellaneousComponentsClasses();
    const sortID = useUniqueID("sortBy");

    if (sort.options.length === 0 && !pages) {
        return null;
    }

    let content = <ResultPaginationInfo pages={props.pages} />;

    return (
        <div className={classes.sortAndPagination}>
            {sort.options.length > 0 && (
                <label className={classes.sort}>
                    <span id={sortID} className={classes.sortLabel}>{`${t("Sort By")}: `}</span>
                    <SelectBox
                        {...sort}
                        describedBy={sortID}
                        widthOfParent={false}
                        renderLeft={false}
                        className={classNames(sort && sort["className"] ? sort["className"] : "", classes.sort)}
                    />
                </label>
            )}
            {pages && content}
        </div>
    );
}
