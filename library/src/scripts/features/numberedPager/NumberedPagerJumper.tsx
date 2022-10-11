/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forum Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { numberedPagerClasses } from "@library/features/numberedPager/NumberedPager.styles";
import { numberedPagerVariables } from "@library/features/numberedPager/NumberedPager.variables";
import InputTextBlock, { InputTextBlockBaseClass } from "@library/forms/InputTextBlock";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { LeftChevronSmallIcon } from "@library/icons/common";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";

interface IProps {
    currentPage: number;
    totalPages: string;
    selectPage: (page: number) => void;
    close: () => void;
    hasMorePages?: boolean;
}

export function NumberedPagerJumper(props: IProps) {
    const vars = numberedPagerVariables();
    const classes = numberedPagerClasses();
    const [pageNumber, setPageNumber] = useState<string>(props.currentPage.toString());

    const handlePageChange = ({ target: { value } }) => {
        let tempValue = value.replace(/\D/g, "");
        if (tempValue.length > 0) {
            tempValue = parseInt(tempValue);
            if (tempValue < 1) tempValue = 1;
            if (tempValue > props.totalPages) tempValue = props.totalPages;
            if (tempValue > 100) tempValue = 100;
        }
        setPageNumber(tempValue.toString());
    };

    const handleSelectPage = () => {
        const page = pageNumber.length > 0 ? parseInt(pageNumber) : props.currentPage;
        props.selectPage(page);
    };

    return (
        <>
            <ToolTip label={t("Back to post count")}>
                <span>
                    <Button
                        onClick={props.close}
                        className={classes.iconButton}
                        buttonType={vars.buttons.iconButton.name as ButtonTypes}
                        ariaLabel={t("Back to post count")}
                    >
                        <LeftChevronSmallIcon />
                    </Button>
                </span>
            </ToolTip>
            Jump to page
            <InputTextBlock
                inputProps={{
                    value: pageNumber,
                    onChange: handlePageChange,
                    "aria-label": t("Jump to page"),
                }}
                className={classes.jumperInput}
                baseClass={InputTextBlockBaseClass.CUSTOM}
            />
            of {props.hasMorePages ? "100" : props.totalPages}
            <Button onClick={handleSelectPage} className={classes.jumperButton} buttonType={ButtonTypes.PRIMARY}>
                Go
            </Button>
        </>
    );
}

export default NumberedPagerJumper;
