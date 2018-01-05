/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import * as domUtility from "../dom-utility";

const input = `<script>alert("Got you!")</script>`;
const output = `&lt;script&gt;alert("Got you!")&lt;/script&gt;`;

it("escapes html", () => {
    expect(domUtility.escapeHTML(input)).toBe(output);
})

it("unescapes html", () => {
    expect(domUtility.unescapeHTML(output)).toBe(input);
})

describe("delegateEvent", () => {
    beforeEach(() => {
        domUtility.removeAllEventListeners();
        document.body.innerHTML = `
            <div class="alternativeScope">
                <button class="filterSelector">MyButton</button>
            </div>
        `;
    })

    test("A event can be registered successfully", () => {
        const callback = jest.fn();
        domUtility.delegateEvent("click", null, callback);

        const button = document.querySelector(".filterSelector");
        button.click();
        expect(callback.mock.calls.length).toBe(1);
    })

    test("identical events will not be registered twice", () => {
        const callback = jest.fn();
        domUtility.delegateEvent("click", null, callback);
        // domUtility.delegateEvent("click", null, callback);
        // domUtility.delegateEvent("click", null, callback);

        const button = document.querySelector(".filterSelector");
        button.click();
        expect(callback.mock.calls.length).toBe(1);
    });
});
