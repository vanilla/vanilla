import moment from "moment";
import {
    DateAddSubtractUnit,
    DateStartEndUnit,
    IDateModifier,
    IDateModifierRange,
} from "@library/forms/rangePicker/types";
import { KeenTimeframe } from "keen-analysis";

/**
 * Generates a date for any range.
 */
export function applyDateModifier(modifier: IDateModifier) {
    let m = moment(modifier.date);
    if (modifier.operations) {
        modifier.operations.forEach((operation) => {
            const { type, amount, unit } = operation;
            switch (type) {
                case "add":
                    return m.add(amount, unit);
                case "subtract":
                    return m.subtract(amount, unit);
                case "startOf":
                    return m.startOf(unit);
                case "endOf":
                    return m.endOf(unit);
            }
        });
    }
    return m.toDate();
}

export class DateModifierBuilder {
    private modifier: IDateModifier;

    constructor(date?: Date) {
        this.modifier = { date, operations: [] };
    }

    static fromDateModifier(modifier: IDateModifier) {
        const builder = new DateModifierBuilder();
        builder.modifier = modifier;
        return builder;
    }

    add(amount: number, unit: DateAddSubtractUnit) {
        this.modifier.operations!.push({ type: "add", amount, unit });
        return this;
    }

    subtract(amount: number, unit: DateAddSubtractUnit) {
        this.modifier.operations!.push({ type: "subtract", amount, unit });
        return this;
    }

    startOf(unit: DateStartEndUnit) {
        this.modifier.operations!.push({ type: "startOf", unit });
        return this;
    }

    endOf(unit: DateStartEndUnit) {
        this.modifier.operations!.push({ type: "endOf", unit });
        return this;
    }

    build() {
        return this.modifier;
    }
}

export const dateModifier = (date?: Date) => new DateModifierBuilder(date);

export const timeFrameFromDateModifierRange = (range: IDateModifierRange): KeenTimeframe => {
    return {
        start: moment(applyDateModifier(range.from)).startOf("day").toISOString(true),
        end: moment(applyDateModifier(range.to)).endOf("day").toISOString(true),
    };
};
