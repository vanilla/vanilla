/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { NullComponent } from "@library/forms/select/overwrites";
import DayPickerInput from "react-day-picker/DayPickerInput";
import { t } from "@library/utility/appUtils";
import moment, { Moment } from "moment";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { guessOperatingSystem, OS } from "@vanilla/utils";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { dayPickerClasses } from "@library/forms/datePickerStyles";
import classNames from "classnames";
import { leftChevron, rightChevron } from "@library/icons/common";
import { formatDate, parseDate } from "react-day-picker/moment";

interface IProps {
    value: string; // ISO formatted date
    onChange: (value: string) => void;
    contentClassName?: string;
    inputClassName?: string;
    alignment: "left" | "right";
    disabledDays?: any; // See http://react-day-picker.js.org/examples/disabled
}

interface IState {
    hasBadValue: boolean;
    wasBlurred: boolean;
}

/**
 * Implements the DateRange component
 */
export default class DatePicker extends React.PureComponent<IProps, IState> {
    public static defaultProps: Partial<IProps> = {
        alignment: "left",
    };

    public state: IState = {
        hasBadValue: false,
        wasBlurred: false,
    };

    public render() {
        // Attempt to use a native input on operating systems that have nice, accessible built in date pickers.
        // EG. mobile
        const os = guessOperatingSystem();
        const useNativeInput = os === OS.ANDROID || os === OS.IOS;

        return useNativeInput ? this.renderNativeInput() : this.renderReactInput();
    }

    /**
     * Render a react day picker component.
     */
    private renderReactInput() {
        const value = this.props.value ? moment(this.props.value).toDate() : undefined;
        const classes = dayPickerClasses();
        return (
            <div className={classNames(classes.root)}>
                <DayPickerInput
                    format="YYYY-MM-DD"
                    placeholder={t(`yyyy-mm-dd`)}
                    formatDate={formatDate}
                    parseDate={parseDate}
                    value={value}
                    overlayComponent={this.CustomOverlay}
                    onDayChange={this.handleDayPickerChange}
                    classNames={
                        {
                            container: classNames("dayPickerInput-container", this.props.contentClassName),
                            overlay: "dayPickerInput-overlay",
                        } as any
                    }
                    dayPickerProps={{
                        captionElement: NullComponent,
                        navbarElement: this.CustomNavBar,
                        disabledDays: this.props.disabledDays,
                        showOutsideDays: true,
                    }}
                    inputProps={{
                        className: classNames("inputText", this.props.inputClassName, {
                            isInvalid: this.state.hasBadValue && this.state.wasBlurred,
                        }),
                        "aria-label": t("Date Input ") + "(yyyy-mm-dd)",
                        onBlur: this.handleBlur,
                        onFocus: this.handleFocus,
                        onChange: this.handleNativeInputChange,
                    }}
                />
            </div>
        );
    }

    /**
     * Render a native date picker component. These can be much nicer on mobile devices.
     */
    private renderNativeInput() {
        // The native date input MUST have it's value in short ISO format, even it doesn't display that way.
        const value = this.props.value ? this.normalizeIsoString(this.props.value) : "";
        return (
            <input
                className="inputText"
                type="date"
                placeholder={t(`yyyy-mm-dd`)}
                onChange={this.handleNativeInputChange}
                value={value}
            />
        );
    }

    /**
     * Handle a new date.
     */
    private updateDate = (date?: Moment | null, isEmpty: boolean = false) => {
        if (date) {
            this.setState({ hasBadValue: false });
            this.props.onChange(this.normalizeIsoString(date.toISOString()));
        } else if (!isEmpty) {
            // invalid date
            this.setState({ hasBadValue: true });
            this.props.onChange("");
        } else {
            this.setState({ hasBadValue: false });
            this.props.onChange("");
        }
    };

    /**
     * Ensure that our date string is always the day only form of an ISO date. EG. No time.
     */
    private normalizeIsoString(isoDate: string): string {
        return isoDate.substr(0, 10);
    }

    /**
     * Track blurred state.
     */
    private handleBlur = (event: React.FocusEvent) => {
        this.setState({ wasBlurred: true });
    };

    /**
     * Track blurred state.
     */
    private handleFocus = (event: React.FocusEvent) => {
        this.setState({ wasBlurred: false });
    };

    /**
     * Handle changes in the day picker input. Eg. A day selection in the modal.
     *
     * Other changes will call the handleTextChange event.
     */
    private handleDayPickerChange = (date?: Date | null) => {
        if (date) {
            this.updateDate(moment(date));
        }
    };

    /**
     * Handle changes in the native input.
     */
    private handleNativeInputChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.updateDate(event.target.valueAsDate, event.target.value === "");
    };

    /**
     * Override for the date pickers flyouts.
     */
    private CustomOverlay = ({ classNames: c, selectedDay, children, ...props }) => {
        const classes = dropDownClasses();
        const contentsClasses = classNames("dropDown-contents", classes.contents, "isOwnWidth", {
            isRightAligned: this.props.alignment === "right",
        });
        return (
            <div className={classNames("dropDown", classes.root)} {...props}>
                <div className={contentsClasses}>{children}</div>
            </div>
        );
    };

    /**
     * Override date pickers navigation component to use our icons.
     */
    private CustomNavBar = ({ month, onPreviousClick, onNextClick, className }) => {
        // The example override shows these methods being rebound in this way.
        // If you attempt to pass these callbacks directly to the overriden component,
        // They crash it when clicked.
        const prev = () => onPreviousClick();
        const next = () => onNextClick();
        const title = (month as Date).toLocaleDateString(undefined, { year: "numeric", month: "long" });
        const classes = dayPickerClasses();
        return (
            <div className={classNames("datePicker-header", classes.header)}>
                <h3 className={classNames("datePicker-title", classes.title)}>{title}</h3>
                <span className={classNames("datePicker-navigation", className, classes.navigation)}>
                    <Button baseClass={ButtonTypes.ICON} onClick={prev}>
                        {leftChevron("", true)}
                    </Button>
                    <Button baseClass={ButtonTypes.ICON} onClick={next}>
                        {rightChevron("", true)}
                    </Button>
                </span>
            </div>
        );
    };
}
