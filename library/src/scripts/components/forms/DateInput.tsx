/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import DayPickerInput from "react-day-picker/DayPickerInput";
import { formatDate, parseDate } from "react-day-picker/moment";
import { guessOperatingSystem, OS } from "@library/utility";
import classNames from "classnames";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { leftChevron, rightChevron } from "@library/components/icons";

interface IProps {
    value: string; // ISO formatted date
    onChange: (value: string) => void;
    className?: string;
    alignment: "left" | "right";
}

/**
 * Implements the DateRange component
 */
export default class DateInput extends React.PureComponent<IProps> {
    public static defaultProps: Partial<IProps> = {
        alignment: "left",
    };

    public render() {
        // Attempt to use a native input on operating systems that have nice, accessible built in date pickers.
        // EG. mobile
        const os = guessOperatingSystem();
        const useNativeInput = os === OS.ANDROID || os === OS.IOS;

        return useNativeInput ? this.renderNativeInput() : this.renderReactInput();
    }

    private renderReactInput() {
        return (
            <DayPickerInput
                format="YYYY-MM-DD"
                placeholder="yyyy-mm-dd"
                formatDate={formatDate}
                parseDate={parseDate}
                value={new Date(this.props.value)}
                overlayComponent={this.CustomOverlay}
                onDayChange={this.handleDayChange}
                dayPickerProps={{
                    captionElement: this.CustomCaptionElement,
                    navbarElement: this.CustomNavBar,
                }}
                inputProps={{
                    className: classNames("inputText", this.props.className),
                    "aria-label": t("Date Input ") + "(yyyy-mm-dd)",
                }}
            />
        );
    }

    private renderNativeInput() {
        // The native date input MUST have it's value in short ISO format, even it doesn't display that way.
        const value = this.props.value ? this.props.value.substr(0, 10) : undefined;
        return <input className="inputText" type="date" onChange={this.handleNativeInputChange} value={value} />;
    }

    private handleDayChange = (day: Date) => {
        this.props.onChange(day.toISOString());
    };

    private handleNativeInputChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.props.onChange(event.target.valueAsDate);
    };

    private CustomOverlay = ({ classNames: c, selectedDay, children, ...props }) => {
        const contentsClasses = classNames("dropDown-contents", "isOwnWidth", {
            isRightAligned: this.props.alignment === "right",
        });
        return (
            <div className="dropDown" {...props}>
                <div className={contentsClasses}>{children}</div>
            </div>
        );
    };

    private CustomCaptionElement = () => {
        return null;
    };

    private CustomNavBar = ({ month, onPreviousClick, onNextClick, className }) => {
        // The example override shows these methods being rebound in this way.
        // If you attempt to pass these callbacks directly to the overriden component,
        // They crash it when clicked.
        const prev = () => onPreviousClick();
        const next = () => onNextClick();
        const title = (month as Date).toLocaleDateString(undefined, { year: "numeric", month: "long" });

        return (
            <div className="datePicker-header">
                <h3 className="datePicker-title">{title}</h3>
                <span className={classNames("datePicker-navigation", className)}>
                    <Button baseClass={ButtonBaseClass.ICON} onClick={prev}>
                        {leftChevron()}
                    </Button>
                    <Button baseClass={ButtonBaseClass.ICON} onClick={next}>
                        {rightChevron()}
                    </Button>
                </span>
            </div>
        );
    };
}
