/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { style } from "@library/styles/styleShim";
import React from "react";
import * as UI from "./";

export default {
    title: "Vanilla UI/Forms",
};

const storyStyle = style({
    display: "grid",
    gridTemplateColumns: "300px 200px",
    gridTemplateRows: "30px 60px 60px 60px",
    alignItems: "center",
    columnGap: 32,
    rowGap: 16,
    gridAutoFlow: "column",

    h3: { borderBottom: "1px solid #ddd", paddingBottom: 8 },
    h4: { margin: "8px 0" },
});

function StoryTextBox(extraProps: Partial<UI.ITextBoxProps>) {
    return <UI.TextBox {...extraProps} />;
}

export function TextBox() {
    return (
        <div className={storyStyle}>
            <h3>Default</h3>
            <div>
                <h4>Default</h4>
                <StoryTextBox />
            </div>
            <div>
                <h4>Active</h4>
                <StoryTextBox autoFocus />
            </div>
            <div>
                <h4>Disabled</h4>
                <StoryTextBox disabled />
            </div>
            <h3>Small</h3>
            <div>
                <h4>Default</h4>
                <StoryTextBox size="small" />
            </div>
            <div />
            {/* Skip active as only one can be autoFocused */}
            <div>
                <h4>Disabled</h4>
                <StoryTextBox size="small" disabled />
            </div>
        </div>
    );
}
