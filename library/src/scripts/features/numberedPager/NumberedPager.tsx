/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forum Inc.
 * @license Proprietary
 */

import React, { useMemo, useState, useEffect, useRef } from "react";
import { numberedPagerClasses } from "@library/features/numberedPager/NumberedPager.styles";
import { numberedPagerVariables } from "@library/features/numberedPager/NumberedPager.variables";
import { numberWithCommas, humanReadableNumber } from "@library/content/NumberFormatted";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon, IconType } from "@vanilla/icons";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import NextPrevButton from "@library/features/numberedPager/NextPrevButton";
import NumberedPagerJumper from "@library/features/numberedPager/NumberedPagerJumper";
import { RecordID } from "@vanilla/utils";
import { useMeasure } from "@vanilla/react-utils";

export interface INumberedPagerProps {
    onChange?: (page: number) => void;
    className?: string;
    currentPage?: number;
    pageLimit?: number;
    totalResults?: number;
    isMobile?: boolean;
    rangeOnly?: boolean;
}

export function NumberedPager(props: INumberedPagerProps) {
    const {
        onChange = (page) => null,
        currentPage = 0,
        pageLimit = 10,
        totalResults = 0,
        rangeOnly = false,
        isMobile: _isMobile,
        className,
    } = props;
    const selfRef = useRef<HTMLDivElement>(null);
    const measure = useMeasure(selfRef);

    //in test environment, measure.width is 0. so we must be able to force mobile/not mobile through props
    const isMobile = _isMobile !== undefined ? _isMobile : measure.width > 600;

    const classes = numberedPagerClasses(isMobile);
    const vars = numberedPagerVariables();
    const [showJumper, setShowJumper] = useState<boolean>(false);
    const [pageNumber, setPageNumber] = useState<number>(1);
    const [displayRange, setDisplayRange] = useState<string>("0 - 0 of 0");

    useEffect(() => {
        if (currentPage && currentPage !== pageNumber) {
            setPageNumber(currentPage);
        }
    }, [currentPage]);

    const totalPages = useMemo<number>(() => {
        if (totalResults > 0) {
            return Math.ceil(totalResults / pageLimit);
        }

        return 1;
    }, [totalResults, pageLimit]);

    const hasMorePages = useMemo<boolean>(() => {
        return totalPages >= 100 && totalResults > pageLimit;
    }, [pageLimit, totalResults, totalPages]);

    useEffect(() => {
        let minNumber: RecordID = 0;
        let maxNumber: RecordID = 0;
        const totalCount = hasMorePages ? pageLimit * 100 : totalResults;

        if (totalCount > 0) {
            minNumber = (pageNumber - 1) * pageLimit + 1;
            maxNumber = pageNumber * pageLimit;

            if (maxNumber > totalCount && !hasMorePages) maxNumber = totalCount;
            if (minNumber < 0) minNumber = 1;
        }

        // Apply compacted formatting if the theme variable is set to true,
        // but not if the number is less than 1000 so that the decimal does not appear on smaller numbers
        minNumber =
            vars.formatNumber.resultRange && minNumber > 999
                ? humanReadableNumber(minNumber, vars.formatNumber.rangePrecision)
                : numberWithCommas(minNumber);
        maxNumber =
            vars.formatNumber.resultRange && maxNumber > 999
                ? humanReadableNumber(maxNumber, vars.formatNumber.rangePrecision)
                : numberWithCommas(maxNumber);
        const total = `
            ${
                vars.formatNumber.totalResults && totalCount > 999
                    ? humanReadableNumber(totalCount, vars.formatNumber.totalPrecision)
                    : numberWithCommas(totalCount)
            }${hasMorePages ? "+" : ""}`;

        const returnValue = `${minNumber} - ${maxNumber} of ${total}`;

        if (pageNumber > 0 && returnValue !== "0 - 0 of 0") {
            setDisplayRange(returnValue);
        }
    }, [pageNumber, totalResults, pageLimit, hasMorePages]);

    const handlePrevPage = () => {
        const prevPage = pageNumber > 1 ? pageNumber - 1 : 1;
        setPageNumber(prevPage);
        onChange(prevPage);
    };

    const handleNextPage = () => {
        const nextPage = currentPage + 1;
        setPageNumber(nextPage);
        onChange(nextPage);
    };

    const handlePageJump = (page: number) => {
        setPageNumber(page);
        onChange(page);
        setShowJumper(false);
    };

    // Only return the result count range if `rangeOnly` is true
    if (rangeOnly) return <div className={cx(classes.resultCount, className)}>{displayRange}</div>;

    // Return a full pager component
    return (
        <div className={cx(classes.root, className)} ref={selfRef}>
            {!isMobile && <div aria-hidden="true" />}
            <div className={classes.nextPageWrapper}>
                <Button
                    className={classes.nextPageButton}
                    onClick={handleNextPage}
                    buttonType={vars.buttons.nextPage.name as ButtonTypes}
                    disabled={pageNumber === totalPages && !hasMorePages}
                >
                    Next Page
                </Button>
            </div>
            <div className={classes.resultCount}>
                {showJumper ? (
                    <NumberedPagerJumper
                        currentPage={pageNumber}
                        totalPages={numberWithCommas(totalPages)}
                        selectPage={handlePageJump}
                        close={() => setShowJumper(false)}
                        hasMorePages={hasMorePages}
                    />
                ) : (
                    <>
                        {displayRange}
                        {!isMobile && (
                            <>
                                <NextPrevButton
                                    direction="prev"
                                    onClick={handlePrevPage}
                                    disabled={pageNumber === 1}
                                    tooltip={t("Previous Page")}
                                />
                                <span className={classes.pageNumber}>{pageNumber}</span>
                                <NextPrevButton
                                    direction="next"
                                    onClick={handleNextPage}
                                    disabled={pageNumber === totalPages && !hasMorePages}
                                    tooltip={t("Next Page")}
                                />
                            </>
                        )}
                        <ToolTip label={t("Jump to a specific page")}>
                            <span>
                                <Button
                                    buttonType={vars.buttons.iconButton.name as ButtonTypes}
                                    className={classes.iconButton}
                                    onClick={() => setShowJumper(true)}
                                    ariaLabel={t("Jump to a specific page")}
                                >
                                    <Icon icon={"navigation-skip" as IconType} />
                                </Button>
                            </span>
                        </ToolTip>
                    </>
                )}
            </div>
        </div>
    );
}

export default NumberedPager;
