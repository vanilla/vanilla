/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { DateElement, humanizedTimeFrom, isSameDate } from "@library/content/DateTimeHelpers";
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

    it("humanized time from", () => {
        const dateFrom = new Date("2020-04-22T14:31:19Z");
        expect(humanizedTimeFrom(new Date("2020-04-22T14:30:36Z"), dateFrom)).equals("a few seconds ago");
        expect(humanizedTimeFrom(new Date("2020-04-22T14:30:35Z"), dateFrom)).equals("44 seconds ago");
        expect(humanizedTimeFrom(new Date("2020-04-22T14:30:20Z"), dateFrom)).equals("a minute ago");
        expect(humanizedTimeFrom(new Date("2020-04-22T14:12:14Z"), dateFrom)).equals("19 minutes ago");
        expect(humanizedTimeFrom(new Date("2020-04-22T13:45:20Z"), dateFrom)).equals("an hour ago");
        expect(humanizedTimeFrom(new Date("2020-04-22T10:30:20Z"), dateFrom)).equals("4 hours ago");
        expect(humanizedTimeFrom(new Date("2020-04-19T14:30:20Z"), dateFrom)).equals("3 days ago");
        expect(humanizedTimeFrom(new Date("2020-03-22T14:30:20Z"), dateFrom)).equals("a month ago");
        expect(humanizedTimeFrom(new Date("2020-01-22T14:30:20Z"), dateFrom)).equals("3 months ago");
        expect(humanizedTimeFrom(new Date("2019-05-22T14:30:20Z"), dateFrom)).equals("a year ago");
        expect(humanizedTimeFrom(new Date("2017-06-22T14:30:20Z"), dateFrom)).equals("3 years ago");
    });
});
