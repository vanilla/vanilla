/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { DateElement, humanizedRelativeTime, isSameDate } from "@library/content/DateTimeHelpers";
import { expect } from "chai";

describe("DateTime helpers", () => {
    it("is same date", () => {
        const date1 = new Date("2020-04-22T14:31:19Z");
        const date2 = new Date("2020-04-22T15:31:19Z");
        const date3 = new Date("2020-04-23T14:31:19Z");
        expect(isSameDate(date1, date3, DateElement.YEAR)).equals(true);
        expect(isSameDate(date1, date3, DateElement.MONTH)).equals(true);
        expect(isSameDate(date1, date2, DateElement.DAY)).equals(true);
        expect(isSameDate(date1, date3, DateElement.DAY)).equals(false);
    });

    describe("humanizedRelativeTime", () => {
        const dateFrom = new Date("2020-04-22T14:31:19Z");

        it("Displays intervals in a user-friendly form", () => {
            expect(humanizedRelativeTime(new Date("2020-04-22T14:30:36Z"), dateFrom, false)).equals("a few seconds");
            expect(humanizedRelativeTime(new Date("2020-04-22T14:30:35Z"), dateFrom, false)).equals("44 seconds");
            expect(humanizedRelativeTime(new Date("2020-04-22T14:30:20Z"), dateFrom, false)).equals("a minute");
            expect(humanizedRelativeTime(new Date("2020-04-22T14:12:14Z"), dateFrom, false)).equals("19 minutes");
            expect(humanizedRelativeTime(new Date("2020-04-22T13:45:20Z"), dateFrom, false)).equals("an hour");
            expect(humanizedRelativeTime(new Date("2020-04-22T10:30:20Z"), dateFrom, false)).equals("4 hours");
            expect(humanizedRelativeTime(new Date("2020-04-19T14:30:20Z"), dateFrom, false)).equals("3 days");
            expect(humanizedRelativeTime(new Date("2020-03-22T14:30:20Z"), dateFrom, false)).equals("a month");
            expect(humanizedRelativeTime(new Date("2020-01-22T14:30:20Z"), dateFrom, false)).equals("3 months");
            expect(humanizedRelativeTime(new Date("2019-05-22T14:30:20Z"), dateFrom, false)).equals("a year");
            expect(humanizedRelativeTime(new Date("2017-06-22T14:30:20Z"), dateFrom, false)).equals("3 years");
        });

        it("Displays past date/times with a localized prefix/suffix", () => {
            expect(humanizedRelativeTime(new Date("2020-04-22T14:30:20Z"), dateFrom)).equals("a minute ago");
        });

        it("Displays future date/times with a localized prefix/suffix", () => {
            expect(humanizedRelativeTime(new Date("2020-04-25T14:30:20Z"), dateFrom)).equals("in 3 days");
        });
    });
});
