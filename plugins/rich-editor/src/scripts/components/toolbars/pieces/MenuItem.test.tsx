/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { shallow } from "enzyme";
import sinon from "sinon";
import MenuItem from "./MenuItem";
import { bold } from "@rich-editor/components/icons";

const noop = () => {
    return;
};

describe("MenuItem", () => {
    it("has a working click handler", () => {
        const spy = sinon.spy();
        const item = shallow(
            <MenuItem
                onClick={spy}
                label="Bold"
                role="menuitem"
                icon={bold()}
                focusNextItem={noop}
                focusPrevItem={noop}
                isActive={true}
            />,
        );
        item.find(".richEditor-button").simulate("click");
        sinon.assert.calledOnce(spy);
    });
});
