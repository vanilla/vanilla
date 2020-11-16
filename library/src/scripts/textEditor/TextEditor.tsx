import React, { useState, useContext } from "react";
import { ControlledEditor, ControlledEditorOnChange, DiffEditorDidMount } from "@vanilla/monaco-editor";
import { DarkThemeIcon, LightThemeIcon } from "@library/icons/common";
import textEditorClasses from "./textEditorStyles";
import { assetUrl } from "@library/utility/appUtils";
import { editor as Monaco } from "monaco-editor/esm/vs/editor/editor.api";

export interface ITextEditorProps {
    language: string;
    value?: string;
    onChange?: ControlledEditorOnChange;
    editorDidMount?: DiffEditorDidMount;
    minimal?: boolean;
}

type VsTheme = "vs-light" | "vs-dark";

interface IContext {
    theme: VsTheme;
    setTheme: (theme: VsTheme) => void;
}

const context = React.createContext<IContext>({
    theme: "vs-dark",
    setTheme: () => {},
});

export function TextEditorContextProvider(props: { children: React.ReactNode }) {
    const [theme, setTheme] = useState<VsTheme>("vs-light");

    return <context.Provider value={{ theme, setTheme }}>{props.children}</context.Provider>;
}

function useTextEditorContext() {
    return useContext(context);
}

const minimalOptions: Monaco.IEditorConstructionOptions = {
    lineNumbers: "on",
    minimap: { enabled: false },
    scrollbar: {
        vertical: "hidden",
        verticalScrollbarSize: 0,
    },
    overviewRulerLanes: 0,
    glyphMargin: false,
    folding: false,
    lineDecorationsWidth: 12,
    lineNumbersMinChars: 3,
    contextmenu: false,
};

const fullOptions: Monaco.IEditorConstructionOptions = {
    lineNumbers: "on",
    minimap: { enabled: false },
};

export default function TextEditor(props: ITextEditorProps) {
    const { language, value, onChange } = props;
    const { theme, setTheme } = useTextEditorContext();
    const [useColorChangeOverlay, setColorChangeOverlay] = useState(false);
    const [isEditorReady, setIsEditorReady] = useState(false);
    const classes = textEditorClasses();

    // Make sure we have the proper asset root set.
    window.MONACO_EDITOR_WEB_ROOT = assetUrl("/dist");

    function handleEditorDidMount() {
        setIsEditorReady(true);
    }

    function toggleTheme() {
        setTheme(theme === "vs-light" ? "vs-dark" : "vs-light");
        setColorChangeOverlay(true);

        setTimeout(() => {
            setColorChangeOverlay(false);
        }, 300);
    }

    const loadingOverlay = useColorChangeOverlay && <div className={classes.colorChangeOverlay(theme)}></div>;

    const themeModeButton = theme === "vs-light" ? <LightThemeIcon /> : <DarkThemeIcon />;

    return (
        <div className={classes.root(theme, props.minimal)}>
            <button type="button" onClick={toggleTheme} className={classes.themeToggleIcon} disabled={!isEditorReady}>
                {themeModeButton}
            </button>
            <ControlledEditor
                theme={theme}
                language={language}
                editorDidMount={handleEditorDidMount}
                options={props.minimal ? minimalOptions : fullOptions}
                value={value}
                onChange={onChange}
            />
            {loadingOverlay}
        </div>
    );
}
