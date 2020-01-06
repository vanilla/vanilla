import React, { useState } from "react";
import Editor from "@monaco-editor/react";
import { ToggleIcon } from "@library/icons/common";
import textEditorClasses from "./textEditorStyles";

export interface ITextEditorProps {
    language: string;
}
export default function TextEditor(props: ITextEditorProps) {
    const { language } = props;
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
        <div style={{ position: "relative" }}>
            <button onClick={toggleTheme} className={classes.themeToggleIcon} disabled={!isEditorReady}>
                <ToggleIcon />
            </button>
            <Editor
                height={"90vh"} // By default, it fully fits with its parent
                theme={intialTheme}
                language={language}
                editorDidMount={handleEditorDidMount}
                options={{ lineNumbers: "on" }}
            />
        </div>
    );
}
