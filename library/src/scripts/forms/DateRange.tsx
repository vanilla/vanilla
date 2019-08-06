/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import React from "react";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import { t } from "@library/utility/appUtils";
import DatePicker from "@library/forms/DatePicker";
import moment from "moment";
import classNames from "classnames";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";

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
        const classesInputBlock = inputBlockClasses();

        return (
            <fieldset
                className={classNames("dateRange", classesInputBlock.root, this.props.className, rangeClasses.root)}
            >
                <legend className={classNames(classesInputBlock.sectionTitle)}>{t("Date Updated")}</legend>
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
