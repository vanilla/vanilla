/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import DayPickerInput from "react-day-picker/DayPickerInput";
import MomentLocaleUtils, { formatDate, parseDate } from "react-day-picker/moment";
import { guessOperatingSystem, OS } from "@library/utility";
import classNames from "classnames";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { leftChevron, rightChevron } from "@library/components/Icons";

interface IProps {
    value: Date;
    onChange: (value: Date) => void;
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
        const os = guessOperatingSystem();
        const useNativeInput = os === OS.ANDROID || os === OS.IOS;

        return useNativeInput ? this.renderNativeInput() : this.renderReactInput();
    }

    private renderReactInput() {
        return (
            <DayPickerInput
                format="YYYY-MM-DD"
                placeholder="yyyy-mm-dd"
                value={"2018-11-06"}
                formatDate={formatDate}
                parseDate={parseDate}
                overlayComponent={this.CustomOverlay}
                onDayChange={this.props.onChange}
                showOverlay={true}
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
        return <input className="inputText" type="date" />;
    }

    private handleNativeInputChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.props.onChange(new Date(event.target.value));
    };

    private handleDayChange = (selectedDay: Date) => {};

    private CustomOverlay = ({ classNames, selectedDay, children, ...props }) => {
        return (
            <div className="dropDown" {...props}>
                <div className="dropDown-contents isOwnWidth">{children}</div>
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
