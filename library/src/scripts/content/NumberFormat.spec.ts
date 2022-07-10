/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { expect } from "chai";
import { humanReadableNumber, numberWithCommas } from "./NumberFormatted";

describe("NumberFormat", () => {
    it("number to human readable", () => {
        expect(humanReadableNumber(15.445)).equals("15.4");
        expect(humanReadableNumber(15.465)).equals("15.5");
        expect(humanReadableNumber(242)).equals("242.0");
        expect(humanReadableNumber(1000)).equals("1.0k");
        expect(humanReadableNumber(4836)).equals("4.8k");
        expect(humanReadableNumber(4876)).equals("4.9k");
        expect(humanReadableNumber(-25476)).equals("-25.5k");
        expect(humanReadableNumber(1000000)).equals("1.0m");
        expect(humanReadableNumber(4436596)).equals("4.4m");
        expect(humanReadableNumber(4486596)).equals("4.5m");
        expect(humanReadableNumber(1000000000)).equals("1.0b");
        expect(humanReadableNumber(4436000596)).equals("4.4b");
        expect(humanReadableNumber(4486000596)).equals("4.5b");
        expect(humanReadableNumber(1000000000000)).equals("1.0t");
        expect(humanReadableNumber(1200000000000)).equals("1.2t");
    });

    it("number with commas", () => {
        expect(numberWithCommas(15.445)).equals("15");
        expect(numberWithCommas(15.62)).equals("16");
        expect(numberWithCommas(100000)).equals("100,000");
        expect(numberWithCommas(154557.69)).equals("154,558");
        expect(numberWithCommas(1006070)).equals("1,006,070");
    });
});
