/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { render } from "@testing-library/react";
import DateTime from "@library/content/DateTime";
import { setCurrentLocale } from "@vanilla/i18n";
import { expect } from "chai";

const timeStamp = "2020-04-22T14:31:19Z";

describe("DateTime", () => {
    after(() => {
        setCurrentLocale("en");
    });

    it("Formats a pretty date", () => {
        const { container } = render(<DateTime timestamp={timeStamp} timezone={"UTC"} />);
        const time = container.querySelector("time");
        expect(time).not.equals(null);
        expect(time?.getAttribute("dateTime")).equals(timeStamp);
        /**
         * Seems like chrome is flipping back and forth between which white space character to use
         * in locale strings. This will normalize them to a regular space for the purposes
         * of our tests
         */
        const title = time?.getAttribute("title")?.replace(/\s/g, " ");
        expect(title).equals("Wednesday, April 22, 2020 at 2:31 PM");
        expect(time?.textContent).equals("Apr 22, 2020");
    });

    it("Works for regional locales", () => {
        // Type of locale our backend returns.
        // An actual JS locale would be zh-TW.
        setCurrentLocale("zh_TW");
        const { container } = render(<DateTime timestamp={timeStamp} timezone={"UTC"} />);
        const time = container.querySelector("time");
        expect(time).not.equals(null);
        expect(time?.getAttribute("dateTime")).equals(timeStamp);
        expect(time?.getAttribute("title")).equals("2020年4月22日 星期三 下午2:31");
        expect(time?.textContent).equals("2020年4月22日");
    });
});
