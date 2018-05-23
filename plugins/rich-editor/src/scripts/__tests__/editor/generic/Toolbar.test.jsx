/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { mount, shallow } from "enzyme";
import Quill from "quill/core";
import EditorMenuItem from "../../../editor/generic/MenuItem";
import { Toolbar } from "../../../editor/generic/Toolbar";

jest.mock('quill/core');

test("matches snapshot", () => {
    const quill = new Quill();
    const toolbar = shallow(
        <Toolbar quill={quill}/>
    );

    expect(toolbar).toMatchSnapshot();
});

test("generates correct number of <EditorMenuItem /> Components", () => {
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
    const quill = new Quill();
    const toolbar = shallow(
        <Toolbar quill={quill} menuItems={menuItems}/>
    );

    expect(toolbar.find(EditorMenuItem).length).toBe(4);
});

test("can receive a custom formatter for the menu item click handler.", () => {
    const mockFormatter = jest.fn();

    const menuItems = {
        bold: {
            active: false,
            formatter: mockFormatter,
        },
    };
    const quill = new Quill();
    const toolbar = mount(
        <Toolbar quill={quill} menuItems={menuItems}/>
    );

    toolbar.find(".richEditor-button").simulate("click");
    toolbar.find(".richEditor-button").simulate("click");

    expect(mockFormatter.mock.calls.length).toBe(2);
});

describe("update", () => {
    let toolbar;

    beforeAll(() => {
        const quill = new Quill();
        const menuItems = {
            bold: {
                active: false,
            },
        };

        toolbar = shallow(
            <Toolbar quill={quill} menuItems={menuItems}/>
        );
    });

    it("updates boolean type ", () => {

    });
});
