/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import classNames from "classnames";
import InputBlock, { InputTextBlockBaseClass } from "InputBlock";
import { t } from "../dom/appUtils";
import moment from "moment";
import { style } from "typestyle";
import { dateRangeClasses } from "@library/styles/dateRangeStyles";
import DatePicker from "DatePicker";

interface IProps {
    start: string | undefined;
    end: string | undefined;
    onStartChange: (value: string) => void;
    onEndChange: (value: string) => void;
    className?: string;
}

interface IState {}

/**
 * Implements the DateRange component
 */
export default class DateRange extends React.PureComponent<IProps> {
    public render() {
        const endDate = this.props.end ? moment(this.props.end).toDate() : null;
        const startDate = this.props.start ? moment(this.props.start).toDate() : null;
        const fromLabel = t("From");
        const toLabel = t("To");
        const rangeClasses = dateRangeClasses();

        return (
            <fieldset className={classNames("dateRange", "inputBlock", this.props.className, rangeClasses.root)}>
                <legend className={classNames("inputBlock-sectionTitle")}>{t("Date Updated")}</legend>
                <label className={classNames("dateRange-boundary", rangeClasses.boundary)}>
                    <span className={classNames("dateRange-label", rangeClasses.label)}>{fromLabel}</span>
                    <DatePicker
                        alignment="right"
                        contentClassName={rangeClasses.input}
                        onChange={this.props.onStartChange}
                        value={this.props.start}
                        disabledDays={[
                            {
                                after: endDate,
                            },
                        ]}
                    />
                </label>
                <label className={classNames("dateRange-boundary", rangeClasses.boundary)}>
                    <span className={classNames("dateRange-label", rangeClasses.label)}>{toLabel}</span>
                    <DatePicker
                        alignment="right"
                        contentClassName={rangeClasses.input}
                        onChange={this.props.onEndChange}
                        value={this.props.end}
                        disabledDays={[
                            {
                                before: startDate,
                            },
                        ]}
                    />
                </label>
            </fieldset>
        );
    }
}
