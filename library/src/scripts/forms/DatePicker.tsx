/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { NullComponent } from "@library/forms/select/overwrites";
import DayPicker from "react-day-picker/DayPicker";
import { t } from "@library/utility/appUtils";
import moment, { Moment } from "moment";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { guessOperatingSystem, OS } from "@vanilla/utils";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { dayPickerClasses } from "@library/forms/datePickerStyles";
import classNames from "classnames";
import { LeftChevronIcon, RightChevronIcon } from "@library/icons/common";
/**
 * fixme: Use our own flyout, or wrap in a stacking context in the next iteration
 * https://higherlogic.atlassian.net/browse/VNLA-3384
 */
import RelativePortal from "react-relative-portal";
import "@library/forms/DatePicker.libStyles.scss";
import { getRequiredID } from "@library/utility/idUtils";

interface IProps {
    value: string; // ISO formatted date
    onChange: (value: string) => void;
    contentClassName?: string;
    inputClassName?: string;
    datePickerDropdownClassName?: string;
    alignment: "left" | "right";
    disabledDays?: any; // See http://react-day-picker.js.org/examples/disabled
    inputAriaLabel?: string;
    id?: string;
    fieldName?: string; //this one is for legacy form submits, hidden input should have a name so it appears in gdn form values
    required?: boolean;
}

interface IState {
    hasBadValue: boolean;
    showPopover: boolean;
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
        showPopover: false,
    };

    public render() {
        // Attempt to use a native input on operating systems that have nice, accessible built in date pickers.
        // EG. mobile
        const os = guessOperatingSystem();
        const useNativeInput = os === OS.ANDROID || os === OS.IOS;

        const value = this.props.value ? moment(this.props.value).toDate() : undefined;
        const classes = dayPickerClasses();
        const ariaLabel = t("Date Input ") + "(mm/dd/yyyy)";
        const id = getRequiredID({ id: this.props.id }, "datePicker");
        const contentsClasses = classNames("dropDown-contents", dropDownClasses().contents, "isOwnWidth", {
            isRightAligned: this.props.alignment === "right",
        });

        return (
            <div className={classNames(classes.root)}>
                <div className={classNames(classes.wrapper)}>
                    <input
                        tabIndex={-1}
                        name={this.props.fieldName}
                        autoComplete="off"
                        style={{ opacity: 0, height: 0, width: "100%", position: "absolute" }}
                        value={this.props.value ? this.normalizeIsoString(this.props.value) : ""}
                        required={this.state.hasBadValue ? false : this.props.required}
                        onChange={() => {}}
                    />
                </div>

                <input
                    id={id}
                    className={classNames("inputText", this.props.inputClassName, {
                        isInvalid: this.state.hasBadValue,
                    })}
                    aria-label={this.props.inputAriaLabel ? `${this.props.inputAriaLabel} ${ariaLabel}` : ariaLabel}
                    type="date"
                    role="date"
                    onClick={(e) => {
                        if (!useNativeInput) {
                            e.preventDefault();
                            this.setState({ showPopover: true });
                        }
                    }}
                    onChange={this.handleNativeInputChange}
                    value={this.props.value}
                />

                {this.state.showPopover && (
                    <RelativePortal
                        component="div"
                        top={0}
                        right={this.props.alignment === "right" ? 0 : undefined}
                        className={dayPickerClasses().root}
                        onOutClick={() => {
                            this.setState({ showPopover: false });
                        }}
                    >
                        <div
                            className={classNames(
                                "dropDown",
                                dropDownClasses().root,
                                this.props.datePickerDropdownClassName,
                            )}
                            role="dialog"
                            aria-label={t("DatePicker")}
                        >
                            <div className={contentsClasses}>
                                <DayPicker
                                    initialMonth={value}
                                    selectedDays={[value]}
                                    onDayClick={(event) => {
                                        this.updateDate(moment(event));
                                        this.setState({ showPopover: false });
                                    }}
                                    captionElement={NullComponent}
                                    navbarElement={this.CustomNavBar}
                                    disabledDays={this.props.disabledDays}
                                    showOutsideDays={true}
                                />
                            </div>
                        </div>
                    </RelativePortal>
                )}
            </div>
        );
    }

    private handleNativeInputChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.updateDate(event.target.valueAsDate ? moment(event.target.valueAsDate) : null, event.target.value === "");
    };

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
                    <Button buttonType={ButtonTypes.ICON} onClick={prev}>
                        <LeftChevronIcon centred={true} />
                    </Button>
                    <Button buttonType={ButtonTypes.ICON} onClick={next}>
                        <RightChevronIcon centred={true} />
                    </Button>
                </span>
            </div>
        );
    };
}
