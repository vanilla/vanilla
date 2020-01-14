import React, { useState } from "react";
import { ControlledEditor, ControlledEditorOnChange, DiffEditorDidMount } from "@monaco-editor/react";
import { DarkThemeIcon, LightThemeIcon } from "@library/icons/common";
import textEditorClasses from "./textEditorStyles";
import MonacoDiffEditor, {DiffChangeHandler} from 'react-monaco-editor';

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
            <MonacoDiffEditor
                theme={intialTheme}
                language={language}
                editorDidMount={handleEditorDidMount}
                options={{ lineNumbers: "on" }}
                value={value}
                onChange={onChange}
                width={"1000"}
                height={"1000"}
            />
        </div>
    );
}
