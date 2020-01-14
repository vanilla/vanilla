import React, { useState } from "react";
import { ControlledEditor, ControlledEditorOnChange, DiffEditorDidMount } from "@vanilla/monaco-editor";
import { DarkThemeIcon, LightThemeIcon } from "@library/icons/common";
import textEditorClasses from "./textEditorStyles";
import MonacoDiffEditor, {DiffChangeHandler} from 'react-monaco-editor';
import {assetUrl} from "@library/utility/appUtils";

export interface ITextEditorProps {
    language: string;
    value?: string;
    onChange?: DiffChangeHandler;
    editorDidMount?: DiffEditorDidMount;
}

export default function TextEditor(props: ITextEditorProps) {
    const { language, value, onChange } = props;
    const [intialTheme, setTheme] = useState("dark");
    const [isEditorReady, setIsEditorReady] = useState(false);
    const classes = textEditorClasses();

    // Make sure we have the proper asset root set.
    window.MONACO_EDITOR_WEB_ROOT = assetUrl('/dist');

    function handleEditorDidMount() {
        setIsEditorReady(true);
    }

    function toggleTheme() {
        setTheme(intialTheme === "vs-light" ? "vs-dark" : "vs-light");
    }

    const themeModeButton = intialTheme === "light" ? <LightThemeIcon /> : <DarkThemeIcon />;

    return (
        <div className={classes.root(intialTheme)}>
            <button onClick={toggleTheme} className={classes.themeToggleIcon} disabled={!isEditorReady}>
                {themeModeButton}
            </button>
            <ControlledEditor
                theme={intialTheme}
                language={language}
                editorDidMount={handleEditorDidMount}
                options={{ lineNumbers: "on" }}
                value={value}
                onChange={onChange}
            />
        </div>
    );
}
