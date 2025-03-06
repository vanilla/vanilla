/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import gdn from "@library/gdn";
import { escapeHTML } from "@vanilla/dom-utils";
import Translate from "@library/content/Translate";
import { render } from "@testing-library/react";

describe("<Translate />", () => {
    it("renders nothing when given an empty string source", () => {
        const { container } = render(<Translate source="" />);
        expect(container.innerHTML === "");
    });

    it("translates value from the translation list", () => {
        gdn.translations.test = "test translated";

        const { container } = render(<Translate source="test" />);
        expect(container.innerHTML === "test translated");
    });

    it("can intepolate a self closing component", () => {
        const TestComponent = () => <div>Hello Test</div>;
        const expected = "<div>Before<div>Hello Test</div>After</div>";
        const testStrings = ["Before<0/>After", "Before<0 />After"];

        testStrings.forEach((str) => {
            const { container } = render(
                <div>
                    <Translate source={str} c0={<TestComponent />} />
                </div>,
            );
            expect(container.innerHTML).equals(expected);
        });
    });

    it("can intepolate multiple self closing components", () => {
        const TestComponent = () => <div>Hello Test</div>;
        const TestComponent2 = () => <div>Hello Test 2</div>;
        const expected = "<div>Before<div>Hello Test</div>Middle<div>Hello Test 2</div>After</div>";
        const testString = "Before<0/>Middle<1 />After";

        const { container } = render(
            <div>
                <Translate source={testString} c0={<TestComponent />} c1={<TestComponent2 />} />
            </div>,
        );
        expect(container.innerHTML).equals(expected);
    });

    it("can intepolate a string with children", () => {
        const TestComponent = (props: any) => <div>{props.content}</div>;

        const interpolatedContent = "Interpolated $$ < ?Content";
        const testString = `Before<0>${interpolatedContent}</0>After`;
        const expected = `<div>Before<div>${escapeHTML(interpolatedContent)}</div>After</div>`;
        const c0 = (content) => <TestComponent content={content} />;

        const { container } = render(
            <div>
                <Translate source={testString} c0={c0} />
            </div>,
        );
        expect(container.innerHTML).equals(expected);
    });

    it("can intepolate sprintf strings", () => {
        const TestComponent = () => <div>hello world</div>;

        const testString = `Before %s%4$d After <script></script>`;
        const expected = `<div>Before <div>hello world</div><div>hello world</div> After &lt;script&gt;&lt;/script&gt;</div>`;

        const { container } = render(
            <div>
                <Translate source={testString} c0={<TestComponent />} c1={<TestComponent />} />
            </div>,
        );
        expect(container.innerHTML).equals(expected);
    });
});
