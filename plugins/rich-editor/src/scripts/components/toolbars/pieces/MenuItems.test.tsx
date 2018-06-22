/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { shallow } from "enzyme";
import { expect } from "chai";
import { MenuItems } from "./MenuItems";
import MenuItem from "./MenuItem";

const noop = () => {
    return;
};

describe("MenuItems", () => {
    it("generates correct number of <EditorMenuItem /> Components", () => {
        const quill = {
            on: noop,
        };
        const menuItems = {
            foo: {
                active: false,
            },
            bar: {
                active: false,
            },
            other: {
                active: true,
            },
            thing: {
                active: false,
            },
        };
        const toolbar = shallow(<MenuItems quill={quill as any} menuItems={menuItems} />);

        expect(toolbar.find(MenuItem).length).eq(4);
    });
});
