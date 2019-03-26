/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { shallow } from "enzyme";
import sinon from "sinon";
import { bold } from "@library/icons/editorIcons";
import MenuItem from "@rich-editor/toolbars/pieces/MenuItem";

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
                legacyMode={false}
            />,
        );
        item.find(".richEditor-button").simulate("click");
        sinon.assert.calledOnce(spy);
    });
});
