/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { style } from "@library/styles/styleShim";
import React from "react";
import * as UI from "../../index";

export default {
    title: "Vanilla UI/Forms",
};

const storyStyle = style({
    display: "grid",
    gridTemplateColumns: "300px 300px",
    gridTemplateRows: "auto auto",
    columnGap: 32,
    rowGap: 16,
    gridAutoFlow: "column",

    h3: { borderBottom: "1px solid #ddd", paddingBottom: 8 },
    h4: { margin: "8px 0" },
});

export function FormGroup() {
    return (
        <article className={storyStyle}>
            <h3>Default</h3>
            <section>
                <UI.FormGroup>
                    <UI.FormGroupLabel>First Name</UI.FormGroupLabel>
                    <UI.FormGroupInput>{(props) => <UI.TextBox {...props} />}</UI.FormGroupInput>
                </UI.FormGroup>
                <UI.FormGroup>
                    <UI.FormGroupLabel>Last Name</UI.FormGroupLabel>
                    <UI.FormGroupInput>{(props) => <UI.TextBox {...props} />}</UI.FormGroupInput>
                </UI.FormGroup>
                <UI.FormGroup>
                    <UI.FormGroupLabel>City</UI.FormGroupLabel>
                    <UI.FormGroupInput>
                        {(props) => (
                            <UI.AutoComplete openOnFocus>
                                <UI.AutoCompleteInput {...props} arrow clear />
                                <UI.AutoCompletePopover>
                                    <UI.AutoCompleteList>
                                        <UI.AutoCompleteOption value="Montreal" />
                                        <UI.AutoCompleteOption value="Detroit" />
                                        <UI.AutoCompleteOption value="Toronto" />
                                    </UI.AutoCompleteList>
                                </UI.AutoCompletePopover>
                            </UI.AutoComplete>
                        )}
                    </UI.FormGroupInput>
                </UI.FormGroup>
            </section>
            <h3>Side-by-side</h3>
            <section>
                <UI.FormGroup sideBySide>
                    <UI.FormGroupLabel>First Name</UI.FormGroupLabel>
                    <UI.FormGroupInput>{(props) => <UI.TextBox {...props} />}</UI.FormGroupInput>
                </UI.FormGroup>
                <UI.FormGroup sideBySide>
                    <UI.FormGroupLabel>Last Name</UI.FormGroupLabel>
                    <UI.FormGroupInput>{(props) => <UI.TextBox {...props} />}</UI.FormGroupInput>
                </UI.FormGroup>
                <UI.FormGroup sideBySide>
                    <UI.FormGroupLabel>City</UI.FormGroupLabel>
                    <UI.FormGroupInput>
                        {(props) => (
                            <UI.AutoComplete openOnFocus>
                                <UI.AutoCompleteInput {...props} arrow clear />
                                <UI.AutoCompletePopover>
                                    <UI.AutoCompleteList>
                                        <UI.AutoCompleteOption value="Montreal" />
                                        <UI.AutoCompleteOption value="Detroit" />
                                        <UI.AutoCompleteOption value="Toronto" />
                                    </UI.AutoCompleteList>
                                </UI.AutoCompletePopover>
                            </UI.AutoComplete>
                        )}
                    </UI.FormGroupInput>
                </UI.FormGroup>
            </section>
        </article>
    );
}
