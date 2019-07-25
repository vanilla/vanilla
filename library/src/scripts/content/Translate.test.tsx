/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import gdn from "@library/gdn";
import { escapeHTML } from "@vanilla/dom-utils";
import Translate from "@library/content/Translate";
import { render } from "enzyme";
import { expect } from "chai";

// tslint:disable:jsx-use-translation-function

describe("<Translate />", () => {
    it("renders nothing when given an empty string source", () => {
        const rendered = render(<Translate source="" />);
        expect(rendered.html() === "");
    });

    it("translates value from the translation list", () => {
        gdn.translations.test = "test translated";

        const rendered = render(<Translate source="test" />);
        expect(rendered.html() === "test translated");
    });

    it("can intepolate a self closing component", () => {
        const TestComponent = () => <div>Hello Test</div>;
        const expected = "Before<div>Hello Test</div>After";
        const testStrings = ["Before<0/>After", "Before<0 />After"];

        testStrings.forEach(str => {
            const rendered = render(
                <div>
                    <Translate source={str} c0={<TestComponent />} />
                </div>,
            );
            expect(rendered.html()).equals(expected);
        });
    });

    it("can intepolate multiple self closing components", () => {
        const TestComponent = () => <div>Hello Test</div>;
        const TestComponent2 = () => <div>Hello Test 2</div>;
        const expected = "Before<div>Hello Test</div>Middle<div>Hello Test 2</div>After";
        const testString = "Before<0/>Middle<1 />After";

        const rendered = render(
            <div>
                <Translate source={testString} c0={<TestComponent />} c1={<TestComponent2 />} />
            </div>,
        );
        expect(rendered.html()).equals(expected);
    });

    it("can intepolate a string with children", () => {
        const TestComponent = (props: any) => <div>{props.content}</div>;

        const interpolatedContent = "Interpolated $$ < ?Content";
        const testString = `Before<0>${interpolatedContent}</0>After`;
        const expected = `Before<div>${escapeHTML(interpolatedContent)}</div>After`;
        const c0 = content => <TestComponent content={content} />;

        const rendered = render(
            <div>
                <Translate source={testString} c0={c0} />
            </div>,
        );
        expect(rendered.html()).equals(expected);
    });
});
