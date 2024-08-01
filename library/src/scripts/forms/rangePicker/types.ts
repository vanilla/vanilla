import moment from "moment";

export type DateStartEndUnit = "week" | "month";
export type DateStartEndOperation = {
    type: "startOf" | "endOf";
    amount?: never;
    unit: DateStartEndUnit;
};

export type DateAddSubtractUnit = "days" | "weeks" | "months" | "quarters" | "years";
export type DateAddSubtractOperation = {
    type: "add" | "subtract";
    amount: number;
    unit: DateAddSubtractUnit;
};

/**
 * Defines an operation on a date we can perform using Moment.
 */
export type DateOperation = DateStartEndOperation | DateAddSubtractOperation;

/**
 * This is an object representation of operations we can perform on a date using Moment.
 * The purpose of this interface is to help us easily compare operations on dates,
 * for exemple if we represent 15 minutes from now as the following object:
 * { date: undefined, operations: [{ type: "minus", amount: 15, unit: "minutes"}] }
 * we can then display it as a relative date operation, a preset or a date on a calendar.
 */
export interface IDateModifier {
    /**
     * All operations are relative to this date.
     * Date is today when null.
     */
    date?: Date;
    operations?: DateOperation[];
}

export interface IDateModifierRange {
    from: IDateModifier;
    to: IDateModifier;
}

export interface IDateModifierRangePickerProps {
    range: IDateModifierRange;
    setRange(range: IDateModifierRange): void;
}

export interface IDateModifierPickerProps {
    dateModifier: IDateModifier;
    setDateModifier(dateModifier: IDateModifier): void;
}

export interface IDateOperationPickerProps {
    operation: DateOperation;
    setOperation(operation: DateOperation): void;
}

export enum TimeInterval {
    HOURLY = "hourly",
    DAILY = "daily",
    WEEKLY = "weekly",
    MONTHLY = "monthly",
    YEARLY = "yearly",
}
