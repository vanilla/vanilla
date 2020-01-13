import React, { useState } from "react";
import { ControlledEditor, ControlledEditorOnChange, DiffEditorDidMount } from "@monaco-editor/react";
import { DarkThemeIcon, LightThemeIcon } from "@library/icons/common";
import textEditorClasses from "./textEditorStyles";

export interface ITextEditorProps {
    language: string;
    value?: string;
    onChange?: ControlledEditorOnChange;
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
        setTheme(intialTheme === "light" ? "dark" : "light");
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
