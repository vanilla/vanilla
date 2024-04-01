/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { resultPaginationInfoClasses } from "@library/result/ResultPaginationInfo.styles";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import Translate from "@library/content/Translate";
import { numberFormattedForTranslations } from "@library/content/NumberFormatted";
import classNames from "classnames";

interface IProps {
    pages?: ILinkPages;
    alignRight?: boolean;
}

/**
 * Component for displaying pagination information in a format like "11-20 of 563".
 */
export function ResultPaginationInfo(props: IProps) {
    const { pages, alignRight = false } = props;
    if (!pages || pages.currentPage == null || pages.limit == null) {
        return null;
    }

    const { total, currentPage, limit } = pages;

    // If we don't know the total results, use format "11 to 20" instead of "11-20 of 563"
    const resultsStartNoTotal = (currentPage - 1) * limit + 1;
    const resultsEndNoTotalDefault = (currentPage - 1) * limit + limit;
    const resultsEndNoTotal =
        pages?.currentResultsLength && pages.currentResultsLength < limit
            ? resultsStartNoTotal + pages.currentResultsLength - 1
            : resultsEndNoTotalDefault;

    let resultStart = Math.min((currentPage - 1) * limit + 1, total ?? 0);
    let resultEnd = Math.min(resultStart + limit - 1, total ?? 0);

    const classes = resultPaginationInfoClasses();

    return (
        <div
            className={classNames(classes.root, {
                [classes.alignRight]: alignRight,
            })}
        >
            {total !== undefined && total >= 0 ? (
                <>
                    <ScreenReaderContent>
                        <Translate
                            source={"Result(s) <0/> to <1/> of <2/>"}
                            c0={resultStart}
                            c1={resultEnd}
                            c2={total}
                        />
                    </ScreenReaderContent>

                    <span aria-hidden={true}>
                        <Translate
                            source={"<0/>-<1/> of <2/>"}
                            c0={numberFormattedForTranslations({
                                value: resultStart,
                            })}
                            c1={numberFormattedForTranslations({
                                value: resultEnd,
                            })}
                            c2={numberFormattedForTranslations({
                                value: total,
                            })}
                        />
                    </span>
                </>
            ) : (
                <>
                    <ScreenReaderContent>
                        <Translate source={"Result(s) <0/> to <1/>"} c0={resultsStartNoTotal} c1={resultsEndNoTotal} />
                    </ScreenReaderContent>

                    <span aria-hidden={true}>
                        <Translate
                            source={"Result(s) <0/> to <1/>"}
                            c0={numberFormattedForTranslations({
                                value: resultsStartNoTotal,
                            })}
                            c1={numberFormattedForTranslations({
                                value: resultsEndNoTotal,
                            })}
                        />
                    </span>
                </>
            )}
        </div>
    );
}
