/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 *
 * Note: These tests use relative dates (with no timezone) to test that everything behaves properly
 * in different timezones. In order to test with a specific timezone, run tests with TZ={timezone}.
 * It is unfortunately not possible to mock timezones at runtime in javascript.
 *
 * Ex:  `TZ=EST yarn test:unit` (Eastern Standard Time GMT-05:00)
 *      `TZ=NZ yarn test:unit` (New Zealand Standard Time GMT+12:00)
 */

import { expect } from "chai";
import { timeFrameFromDateModifierRange } from "./utils";

function mockDate(value: string | Date) {
    Date.now = jest.fn(() => new Date(value).valueOf());
}

function areDatesEqual(date1: Date, date2: Date) {
    return date1.getTime() === date2.getTime();
}

const fakeDateStart = new Date("1955-11-05T00:00:00.000");
const fakeDateEnd = new Date("1955-11-05T23:59:59.999");

describe("timeFrameFromDateModifierRange()", () => {
    describe("fixed range", () => {
        it("start date returns the same date when passed date is Nov 5th 1955 00:00", () => {
            const timeFrame = timeFrameFromDateModifierRange({ from: { date: fakeDateStart }, to: {} });
            // expect(new Date(timeFrame.start)).to.eq(date);
            expect(areDatesEqual(fakeDateStart, new Date(timeFrame.start))).to.be.true;
        });
        it("start date returns the start of day when passed date is Nov 5th 1955 23:59", () => {
            const timeFrame = timeFrameFromDateModifierRange({ from: { date: fakeDateEnd }, to: {} });
            expect(areDatesEqual(fakeDateStart, new Date(timeFrame.start))).to.be.true;
        });
        it("end date returns the same date when passed date is Nov 5th 1955 23:59", () => {
            const timeFrame = timeFrameFromDateModifierRange({ from: {}, to: { date: fakeDateEnd } });
            expect(areDatesEqual(fakeDateEnd, new Date(timeFrame.end))).to.eq(true);
        });
        it("end date returns the end of day when passed date is Nov 5th 1955 00:00", () => {
            const timeFrame = timeFrameFromDateModifierRange({ from: {}, to: { date: fakeDateStart } });
            expect(areDatesEqual(fakeDateEnd, new Date(timeFrame.end))).to.eq(true);
        });
    });
    describe("relative range", () => {
        it("start date returns the same date when current date is Nov 5th 1955 00:00", () => {
            mockDate(fakeDateStart);
            const timeFrame = timeFrameFromDateModifierRange({ from: {}, to: {} });
            expect(areDatesEqual(fakeDateStart, new Date(timeFrame.start))).to.eq(true);
        });
        it("start date returns the start of day when current date is Nov 5th 1955 23:59", () => {
            mockDate(fakeDateEnd);
            const timeFrame = timeFrameFromDateModifierRange({ from: {}, to: {} });
            expect(areDatesEqual(fakeDateStart, new Date(timeFrame.start))).to.eq(true);
        });
        it("end date returns the same date when current date is Nov 5th 1955 23:59", () => {
            mockDate(fakeDateEnd);
            const timeFrame = timeFrameFromDateModifierRange({ from: {}, to: {} });
            expect(areDatesEqual(fakeDateEnd, new Date(timeFrame.end))).to.eq(true);
        });
        it("end date returns the end of day when current date is Nov 5th 1955 00:00", () => {
            mockDate(fakeDateStart);
            const timeFrame = timeFrameFromDateModifierRange({ from: {}, to: {} });
            expect(areDatesEqual(fakeDateEnd, new Date(timeFrame.end))).to.eq(true);
        });
        it("can subtract 14 days", () => {
            mockDate(fakeDateStart);
            const timeFrame = timeFrameFromDateModifierRange({
                from: { operations: [{ type: "subtract", amount: 14, unit: "days" }] },
                to: {},
            });
            expect(areDatesEqual(new Date(timeFrame.start), new Date("1955-10-22T00:00:00.000"))).to.eq(true);
        });
        it("can subtract 2 weeks", () => {
            mockDate(fakeDateStart);
            const timeFrame = timeFrameFromDateModifierRange({
                from: { operations: [{ type: "subtract", amount: 2, unit: "weeks" }] },
                to: {},
            });
            expect(areDatesEqual(new Date(timeFrame.start), new Date("1955-10-22T00:00:00.000"))).to.eq(true);
        });
        it("can subtract 1 month", () => {
            mockDate(fakeDateStart);
            const timeFrame = timeFrameFromDateModifierRange({
                from: { operations: [{ type: "subtract", amount: 1, unit: "months" }] },
                to: {},
            });
            expect(areDatesEqual(new Date(timeFrame.start), new Date("1955-10-05T00:00:00.000"))).to.eq(true);
        });
        it("can return the start of the month", () => {
            mockDate(fakeDateStart);
            const timeFrame = timeFrameFromDateModifierRange({
                from: { operations: [{ type: "startOf", unit: "month" }] },
                to: {},
            });
            expect(areDatesEqual(new Date(timeFrame.start), new Date("1955-11-01T00:00:00.000"))).to.eq(true);
        });
        it("can return the end of the month", () => {
            mockDate(fakeDateStart);
            const timeFrame = timeFrameFromDateModifierRange({
                from: { operations: [{ type: "endOf", unit: "month" }] },
                to: {},
            });
            expect(areDatesEqual(new Date(timeFrame.start), new Date("1955-11-30T00:00:00.000"))).to.eq(true);
        });
        it("can return the start of the week", () => {
            mockDate(fakeDateStart);
            const timeFrame = timeFrameFromDateModifierRange({
                from: { operations: [{ type: "startOf", unit: "week" }] },
                to: {},
            });
            expect(areDatesEqual(new Date(timeFrame.start), new Date("1955-10-30T00:00:00.000"))).to.eq(true);
        });
        it("can return the end of the week", () => {
            mockDate(fakeDateStart);
            const timeFrame = timeFrameFromDateModifierRange({
                from: { operations: [{ type: "endOf", unit: "week" }] },
                to: {},
            });
            expect(areDatesEqual(new Date(timeFrame.start), new Date("1955-11-05T00:00:00.000"))).to.eq(true);
        });
    });
});
