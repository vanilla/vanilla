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
import { inputClasses } from "./inputStyles";
import { DayPickerProps, Modifier } from "react-day-picker";

interface IProps {
    value: string; // ISO formatted date
    onChange: (value: string) => void;
    inputClassName?: string;
    datePickerDropdownClassName?: string;
    alignment: "left" | "right";
    max?: React.InputHTMLAttributes<HTMLInputElement>["max"];
    min?: React.InputHTMLAttributes<HTMLInputElement>["min"];

    onBlur?: DayPickerProps["onBlur"];
    inputAriaLabel?: string;
    id?: string;
    fieldName?: string; //this one is for legacy form submits, hidden input should have a name so it appears in gdn form values
    required?: boolean;
}

interface IState {
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
        showPopover: false,
    };

    public render() {
        // Attempt to use a native input on operating systems that have nice, accessible built in date pickers.
        // EG. mobile
        const os = guessOperatingSystem();
        const useNativeInput = os === OS.ANDROID || os === OS.IOS;

        const value = this.props.value ? moment(this.props.value).toDate() : undefined;
        const classes = dayPickerClasses();
        const ariaLabel = t("Date Input");
        const id = getRequiredID({ id: this.props.id }, "datePicker");
        const contentsClasses = classNames("dropDown-contents", dropDownClasses().contents, "isOwnWidth", {
            isRightAligned: this.props.alignment === "right",
        });

        return (
            <div className={classNames(classes.root)}>
                <input
                    id={id}
                    name={this.props.fieldName}
                    className={classNames(inputClasses().text, this.props.inputClassName)}
                    aria-label={this.props.inputAriaLabel ? `${this.props.inputAriaLabel} ${ariaLabel}` : ariaLabel}
                    type="date"
                    role="date"
                    max={this.props.max ? moment(this.props.max).format("YYYY-MM-DD") : undefined}
                    min={this.props.min ? moment(this.props.min).format("YYYY-MM-DD") : undefined}
                    onClick={(e) => {
                        if (!useNativeInput) {
                            e.preventDefault();
                            this.setState({ showPopover: true });
                        }
                    }}
                    onChange={this.handleNativeInputChange}
                    value={this.props.value ?? ""}
                    onBlur={this.props.onBlur}
                    required={this.props.required}
                />

                {this.state.showPopover && (
                    <RelativePortal
                        component="div"
                        top={0}
                        right={this.props.alignment === "right" ? 0 : undefined}
                        className={classes.root}
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
                                    onBlur={this.props.onBlur}
                                    initialMonth={value}
                                    selectedDays={[value]}
                                    onDayClick={(date, { disabled, selected }) => {
                                        if (disabled) {
                                            return;
                                        }
                                        this.updateDate(moment(date));
                                        this.setState({ showPopover: false });
                                    }}
                                    captionElement={NullComponent}
                                    navbarElement={this.CustomNavBar}
                                    disabledDays={
                                        {
                                            ...(this.props.max
                                                ? {
                                                      after: new Date(this.props.max),
                                                  }
                                                : undefined),

                                            ...(this.props.min
                                                ? {
                                                      before: new Date(this.props.min),
                                                  }
                                                : undefined),
                                        } as Modifier
                                    }
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
        this.updateDate(event.target.value ? moment(event.target.value) : null, event.target.value === "");
    };

    /**
     * Handle a new date.
     */
    private updateDate = (date?: Moment | null, isEmpty: boolean = false) => {
        if (date) {
            this.props.onChange(this.normalizeIsoString(date.toISOString()));
        } else {
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
