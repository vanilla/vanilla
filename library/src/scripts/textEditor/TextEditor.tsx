import React, { useState } from "react";
import Editor from "@monaco-editor/react";
import { ToggleIcon } from "@library/icons/common";
import textEditorClasses from "./textEditorStyles";

interface IOptions {
    lineNumbers: string;
}
export interface ITextEditorProps {
    theme: string;
    language: string;
    options: IOptions;
    height: string;
}
export default function TextEditor(props: ITextEditorProps) {
    const { theme, language, options, height } = props;
    const [intialTheme, setTheme] = useState(theme);
    const [isEditorReady, setIsEditorReady] = useState(false);
    const classes = textEditorClasses();

    function handleEditorDidMount() {
        setIsEditorReady(true);
    }

    function toggleTheme() {
        setTheme(intialTheme === "light" ? "dark" : "light");
    }

    return (
        <div style={{ position: "relative" }}>
            <button
                onClick={toggleTheme}
                className={classes.themeToggleIcon}
                disabled={!isEditorReady}
                style={{ position: "absolute" }}
            >
                <ToggleIcon />
            </button>
            <Editor
                height={height} // By default, it fully fits with its parent
                theme={intialTheme}
                language={language}
                editorDidMount={handleEditorDidMount}
                options={options}
            />
        </div>
    );
}
