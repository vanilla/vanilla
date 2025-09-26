/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forum Inc.
 * @license Proprietary
 */

import React, { useMemo, useState, useEffect, useRef, useLayoutEffect } from "react";
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
import { useDeferredFocuser, useMeasure } from "@vanilla/react-utils";
import { useUniqueID } from "@library/utility/idUtils";

export interface INumberedPagerProps {
    onChange?: (page: number) => void;
    className?: string;
    currentPage?: number;
    pageLimit?: number;
    totalResults?: number;
    rangeOnly?: boolean;

    /** This one indicates that actual results might be more than totalResults, as normally we have a limit from APIs*/
    hasMorePages?: boolean;

    /** This one is responsible for showing the big "Next Page" button, the one in the form is still there */
    showNextButton?: boolean;
}

export function NumberedPager(props: INumberedPagerProps) {
    const {
        onChange = (page) => null,
        currentPage = 0,
        pageLimit = 10,
        totalResults = 0,
        rangeOnly = false,
        showNextButton = true,
        className,
        hasMorePages,
    } = props;

    const classes = numberedPagerClasses.useAsHook();
    const vars = numberedPagerVariables.useAsHook();
    const [showJumper, setShowJumper] = useState<boolean>(false);
    const [pageNumber, setPageNumber] = useState<number>(1);

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

    const jumperInputID = useUniqueID("jumperInput");
    const showJumperID = useUniqueID("jumperButton");
    const deferredFocuser = useDeferredFocuser();
    const [displayRange, setDisplayRange] = useState<string>("0 - 0 of 0");

    useLayoutEffect(() => {
        let minNumber: RecordID = 0;
        let maxNumber: RecordID = 0;

        if (totalResults > 0) {
            minNumber = (pageNumber - 1) * pageLimit + 1;
            maxNumber = pageNumber * pageLimit;

            if (maxNumber > totalResults && !hasMorePages) maxNumber = totalResults;
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
                vars.formatNumber.totalResults && totalResults > 999
                    ? humanReadableNumber(totalResults, vars.formatNumber.totalPrecision)
                    : numberWithCommas(totalResults)
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
        deferredFocuser.focusElementBySelector(`#${showJumperID}`);
    };

    // Only return the result count range if `rangeOnly` is true
    if (rangeOnly) {
        return <div className={cx(classes.resultCount, className)}>{displayRange}</div>;
    }

    // Return a full pager component
    return (
        <div className={cx(classes.root, className)}>
            <div aria-hidden="true" className={"noMobile"} />
            {showNextButton && (
                <div className={classes.nextPageWrapper}>
                    <Button
                        className={classes.nextPageButton}
                        onClick={handleNextPage}
                        buttonType={vars.buttons.nextPage.name as ButtonTypes}
                        disabled={pageNumber === totalPages && !hasMorePages}
                    >
                        {t("Next Page")}
                    </Button>
                </div>
            )}
            <div className={classes.resultCount}>
                {showJumper ? (
                    <NumberedPagerJumper
                        inputID={jumperInputID}
                        currentPage={pageNumber}
                        totalPages={numberWithCommas(totalPages)}
                        selectPage={handlePageJump}
                        close={() => {
                            deferredFocuser.focusElementBySelector(`#${showJumperID}`);
                            setShowJumper(false);
                        }}
                        hasMorePages={hasMorePages}
                    />
                ) : (
                    <>
                        {displayRange}

                        <NextPrevButton
                            className={"noMobile"}
                            direction="prev"
                            onClick={handlePrevPage}
                            disabled={pageNumber === 1}
                            tooltip={t("Previous Page")}
                        />
                        <span className={cx(classes.pageNumber, "noMobile")}>{pageNumber}</span>
                        <NextPrevButton
                            className={"noMobile"}
                            direction="next"
                            onClick={handleNextPage}
                            disabled={pageNumber === totalPages && !hasMorePages}
                            tooltip={t("Next Page")}
                        />
                        <ToolTip label={t("Jump to a specific page")}>
                            <span>
                                <Button
                                    buttonType={vars.buttons.iconButton.name as ButtonTypes}
                                    className={classes.iconButton}
                                    id={showJumperID}
                                    onClick={() => {
                                        deferredFocuser.focusElementBySelector(`#${jumperInputID}`);
                                        setShowJumper(true);
                                    }}
                                    ariaLabel={t("Jump to a specific page")}
                                >
                                    <Icon icon={"pager-skip" as IconType} />
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
