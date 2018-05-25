/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { mount, shallow } from "@testroot/enzyme";
import Quill from "quill/core";
import EditorMenuItem from "../../../editor/generic/MenuItem";
import { Toolbar } from "../../../editor/generic/Toolbar";
import { expect } from "chai";
import sinon from "sinon";

const noop = () => {
    return;
};

describe("Toolbar", () => {
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
        const toolbar = shallow(<Toolbar quill={quill as any} menuItems={menuItems} onBlur={noop} />);

        expect(toolbar.find(EditorMenuItem).length).eq(4);
    });
});
