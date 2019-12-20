import React, { useState } from "react";

import Editor from "@monaco-editor/react";

export function TextEditor(_) {
    const [theme, setTheme] = useState("light");
    const [isEditorReady, setIsEditorReady] = useState(false);

    function handleEditorDidMount() {
        setIsEditorReady(true);
    }

    function toggleTheme() {
        setTheme(theme === "light" ? "dark" : "light");
    }

    return (
        <>
            <button onClick={toggleTheme} disabled={!isEditorReady}>
                Toggle theme
            </button>
            <Editor
                height="90vh" // By default, it fully fits with its parent
                theme={theme}
                language="html"
                editorDidMount={handleEditorDidMount}
                options={{ lineNumbers: "off" }}
            />
        </>
    );
}
