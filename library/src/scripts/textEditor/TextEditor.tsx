import React, { useState } from "react";
import { ControlledEditor, ControlledEditorOnChange, DiffEditorDidMount } from "@monaco-editor/react";
import { ToggleIcon } from "@library/icons/common";
import textEditorClasses from "./textEditorStyles";

export interface ITextEditorProps {
    language: string;
    value?: string;
    onChange?: ControlledEditorOnChange;
    editorDidMount?: DiffEditorDidMount;
}
export default function TextEditor(props: ITextEditorProps) {
    const { language, value } = props;
    const [intialTheme, setTheme] = useState("dark");
    const [isEditorReady, setIsEditorReady] = useState(false);
    const classes = textEditorClasses();

    function handleEditorDidMount() {
        setIsEditorReady(true);
    }

    function toggleTheme() {
        setTheme(intialTheme === "light" ? "dark" : "light");
    }

    return (
        <div className={classes.root(intialTheme)}>
            <button onClick={toggleTheme} className={classes.themeToggleIcon} disabled={!isEditorReady}>
                <ToggleIcon />
            </button>
            <ControlledEditor
                theme={intialTheme}
                language={language}
                editorDidMount={handleEditorDidMount}
                options={{ lineNumbers: "on" }}
                value={value}
            />
        </div>
    );
}
