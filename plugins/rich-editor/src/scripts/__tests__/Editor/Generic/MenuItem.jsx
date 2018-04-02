import React from "react";
import { shallow } from "enzyme";
import MenuItem from "../../../Editor/Generic/MenuItem";

test("matches snapshot", () => {
    const clickHandler = () => {};
    const item = shallow(
        <MenuItem clickHandler={clickHandler} propertyName="bold" label="Bold" isActive={true} isFirst={false} isLast={false}/>
    );

    expect(item).toMatchSnapshot();
});

test("click handler is called", () => {
    const mockClickHandler = jest.fn();
    const item = shallow(
        <MenuItem clickHandler={mockClickHandler} propertyName="bold" label="Bold" isActive={true} isFirst={false} isLast={false}/>
    );
    item.find(".richEditor-button").simulate("click");
    expect(mockClickHandler.mock.calls.length).toBe(1);
});
