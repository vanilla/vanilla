/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import Axios from "axios";
import React, { useState } from "react";
import { labelize } from "@vanilla/utils";
import { FormGroup, FormGroupLabel, FormGroupInput } from "../../index";
import { AutoComplete as UIAutoComplete, AutoCompleteLookupOptions, ILookupApi } from "./";

export default {
    title: "Forms/Admin Form Fields",
};

const storyStyle = css({
    display: "grid",
    gridTemplateColumns: "300px 200px",
    gridTemplateRows: "30px repeat(10, auto)",
    alignItems: "center",
    columnGap: 32,
    rowGap: 16,
    gridAutoFlow: "column",

    h3: { borderBottom: "1px solid #ddd", paddingBottom: 8 },
    h4: { margin: "8px 0" },
});

interface StoryAutoCompleteProps extends React.ComponentProps<typeof UIAutoComplete> {
    label?: string;
}

function StoryAutoComplete(props: StoryAutoCompleteProps) {
    const { label, ...autoCompleteProps } = props;
    const defaultValue = props.hasOwnProperty("value") ? props.value : 1;
    const [value, setValue] = useState<any | any[] | undefined>(props.multiple ? [defaultValue] : defaultValue);
    const setDataValue = (values) => {
        setValue(Array.isArray(values) ? [...values] : values);
    };
    return (
        <>
            <FormGroup>
                <FormGroupLabel>{label}</FormGroupLabel>
                <FormGroupInput>
                    {(props) => (
                        <UIAutoComplete
                            {...autoCompleteProps}
                            {...props}
                            value={value}
                            onChange={setDataValue}
                            options={[
                                { value: 0, label: "Pizza" },
                                { value: 1, label: "Apple", group: "Fruits" },
                                { value: 2, label: "Banana", group: "Fruits" },
                                { value: 3, label: "Cherry", group: "Fruits" },
                                { value: 4, label: "Broccoli", group: "Vegetables" },
                                { value: 5, label: "Carrot", group: "Vegetables" },
                            ]}
                        />
                    )}
                </FormGroupInput>
            </FormGroup>
            <span>{`${JSON.stringify(value)}`}</span>
        </>
    );
}

const api = Axios.create({
    baseURL: "https://pokeapi.co/api/v2",
});

const lookup: ILookupApi = {
    searchUrl: "pokemon?limit=50", // This is not a searchable endpoint (only for illustration purposes)
    singleUrl: "pokemon/%s",
    resultsKey: "results",
    processOptions: (options) =>
        options.map((option) => ({ ...option, label: labelize(option.label ?? option.value) })),
};

function StoryAutoCompleteLookup(props: StoryAutoCompleteProps) {
    const { label, ...autoCompleteProps } = props;
    const [value, setValue] = useState<any | any[]>(props.value ?? "bulbasaur");
    const setDataValue = (values) => {
        setValue(Array.isArray(values) ? [...values] : values);
    };
    return (
        <>
            <FormGroup>
                <FormGroupLabel>{label}</FormGroupLabel>
                <FormGroupInput>
                    {(inputProps) => (
                        <UIAutoComplete
                            {...inputProps}
                            {...autoCompleteProps}
                            value={value}
                            onChange={setDataValue}
                            optionProvider={<AutoCompleteLookupOptions api={api} lookup={lookup} />}
                        />
                    )}
                </FormGroupInput>
            </FormGroup>
            <span>{`${JSON.stringify(value)}`}</span>
        </>
    );
}

export function AutoComplete() {
    return (
        <div className={storyStyle}>
            <h3>Default</h3>
            <div>
                <StoryAutoComplete label="Default" />
            </div>
            <div style={{ marginBottom: 116 }}>
                <StoryAutoComplete label="Opened" autoFocus />
            </div>
            <div>
                <StoryAutoComplete label="Disabled" disabled />
            </div>
            <div>
                <StoryAutoComplete label="Clearable" clear />
            </div>
            <div>
                <StoryAutoCompleteLookup label="Lookup" clear />
            </div>
            <div>
                <StoryAutoComplete label="Multiple - No Default Value" multiple />
            </div>
            <div>
                <StoryAutoComplete
                    label="Multiple"
                    multiple
                    placeholder={"Here is some long place holder that should be visible from behind the input values"}
                />
            </div>
            <div>
                <StoryAutoComplete label="Default multiple clearable" multiple clear />
            </div>
            <div>
                <StoryAutoCompleteLookup label="Lookup Multiple" multiple value={["bulbasaur", "pikachu"]} />
            </div>
            <div>
                <StoryAutoComplete
                    label="Allow Arbitrary Input Multiple"
                    placeholder={"Here is some long place holder that should be visible from behind the input values"}
                    multiple
                    value={undefined}
                    allowArbitraryInput
                />
            </div>
            {/* ------------------- */}
            <h3>Small</h3>
            <div>
                <StoryAutoComplete label="Default" size="small" />
            </div>
            <div>{/* Skip active as only one can be autoFocused */}</div>
            <div>
                <StoryAutoComplete label="Disabled" size="small" disabled />
            </div>
            <div>
                <StoryAutoComplete label="Clearable" size="small" clear />
            </div>
            <div>
                <StoryAutoCompleteLookup label="Lookup" size="small" clear />
            </div>
            <div>
                <StoryAutoComplete label="Multiple - No Default Value" multiple size="small" value={undefined} />
            </div>
            <div>
                <StoryAutoComplete label="Multiple" multiple size="small" />
            </div>
            <div>
                <StoryAutoComplete label="Default multiple clearable" size="small" multiple clear />
            </div>
            <div>
                <StoryAutoCompleteLookup label="Lookup Multiple" size="small" multiple />
            </div>
            <div>
                <StoryAutoComplete
                    label="Allow Arbitrary Input Multiple"
                    size="small"
                    multiple
                    value={undefined}
                    allowArbitraryInput
                />
            </div>
        </div>
    );
}
