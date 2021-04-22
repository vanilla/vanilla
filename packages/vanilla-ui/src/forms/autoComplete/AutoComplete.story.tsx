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
    gridTemplateRows: "30px 60px auto 60px",
    alignItems: "center",
    columnGap: 32,
    rowGap: 16,
    gridAutoFlow: "column",

    h3: { borderBottom: "1px solid #ddd", paddingBottom: 8 },
    h4: { margin: "8px 0" },
});

function StoryAutoComplete(props: {
    autoCompleteProps?: Partial<UI.IAutoCompleteProps>;
    inputProps?: Partial<UI.IAutoCompleteInputProps>;
}) {
    const { autoCompleteProps, inputProps } = props;
    return (
        <UI.AutoComplete {...autoCompleteProps} openOnFocus>
            <UI.AutoCompleteInput value="Apple" {...inputProps} arrow clear />
            <UI.AutoCompletePopover>
                <UI.AutoCompleteList>
                    <UI.AutoCompleteOption value="Apple" />
                    <UI.AutoCompleteOption value="Banana" />
                    <UI.AutoCompleteOption value="Cherry" />
                </UI.AutoCompleteList>
            </UI.AutoCompletePopover>
        </UI.AutoComplete>
    );
}

export function AutoComplete() {
    return (
        <div className={storyStyle}>
            <h3>Default</h3>
            <div>
                <h4>Default</h4>
                <StoryAutoComplete />
            </div>
            <div style={{ marginBottom: 116 }}>
                <h4>Active</h4>
                <StoryAutoComplete inputProps={{ autoFocus: true }} />
            </div>
            <div>
                <h4>Disabled</h4>
                <StoryAutoComplete inputProps={{ disabled: true }} />
            </div>
            <h3>Small</h3>
            <div>
                <h4>Default</h4>
                <StoryAutoComplete autoCompleteProps={{ size: "small" }} />
            </div>
            <div />
            {/* Skip active as only one can be autoFocused */}
            <div>
                <h4>Disabled</h4>
                <StoryAutoComplete autoCompleteProps={{ size: "small" }} inputProps={{ disabled: true }} />
            </div>
        </div>
    );
}
