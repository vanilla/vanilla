import React from "react";
import { shallow } from "enzyme";
import EditorMenuItem from "../../components/EditorMenuItem";

test("matches snapshot", () => {
    const clickHandler = () => {};
    const item = shallow(
        <EditorMenuItem clickHandler={clickHandler} propertyName="bold" label="Bold" isActive={true} isFirst={false} isLast={false}/>
    );

    expect(item).toMatchSnapshot();
});

test("click handler is called", () => {
    const mockClickHandler = jest.fn();
    const item = shallow(
        <EditorMenuItem clickHandler={mockClickHandler} propertyName="bold" label="Bold" isActive={true} isFirst={false} isLast={false}/>
    );
    item.find(".richEditor-button").simulate("click");
    expect(mockClickHandler.mock.calls.length).toBe(1);
});
