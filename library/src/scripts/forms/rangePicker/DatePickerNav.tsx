/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { NavbarElementProps } from "react-day-picker";
import { ChevronLeft } from "@library/forms/rangePicker/icons/ChevronLeft";
import { ChevronRight } from "@library/forms/rangePicker/icons/ChevronRight";
import moment from "moment";

export default function DatePickerNav(props: NavbarElementProps) {
    const { month, onNextClick, onPreviousClick, className, showNextButton, showPreviousButton } = props;
    return (
        <div className={className}>
            <button disabled={!showPreviousButton} onClick={() => onPreviousClick()}>
                <ChevronLeft />
            </button>
            <span>{moment(month).format("MMMM YYYY")}</span>
            <button disabled={!showNextButton} onClick={() => showNextButton && onNextClick()}>
                <ChevronRight />
            </button>
        </div>
    );
}
