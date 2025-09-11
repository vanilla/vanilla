/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { css } from "@emotion/css";
import MonacoEditor from "@library/textEditor/MonacoEditor";
import { mountReact } from "@vanilla/react-utils";
import { logWarning } from "@vanilla/utils";
import { useMemo, useState } from "react";

interface IProps {
    value: string;
    onChange(value: string): void;
    inputID?: string;
    inputName?: string;
    language?: string;
    jsonSchemaUri?: string;
    boxHeightOverride?: number;
}

export function DashboardCodeEditor(props: IProps) {
    const { value, onChange, inputName, language, jsonSchemaUri, boxHeightOverride } = props;
    const formGroup = useFormGroup();
    const inputID = props.inputID ?? formGroup.inputID;

    const styleOverrides = useMemo(() => {
        return {
            ...(boxHeightOverride && {
                className: css({
                    height: boxHeightOverride,
                }),
            }),
        };
    }, [boxHeightOverride]);

    return (
        <DashboardInputWrap>
            <input id={inputID} name={inputName} type="hidden" value={value || ""} aria-hidden={true} />
            <MonacoEditor
                minimal
                language={language ?? "text/html"}
                jsonSchemaUri={jsonSchemaUri}
                value={value}
                onChange={(value) => onChange(value ?? "")}
                {...styleOverrides}
            />
        </DashboardInputWrap>
    );
}

DashboardCodeEditor.Uncontrolled = function UncontrolledDashboardCodeEditor(
    props: { initialValue?: string } & Omit<IProps, "onChange" | "value">,
) {
    const { initialValue, ...otherProps } = props;
    const [value, setValue] = useState(props.initialValue);
    return <DashboardCodeEditor value={value || ""} onChange={setValue} {...otherProps} />;
};

export function mountDashboardCodeEditors() {
    const mounts = document.querySelectorAll(".js-code-editor");
    mounts.forEach((mount) => {
        if (!(mount instanceof HTMLTextAreaElement)) {
            logWarning("Cannot mount a js-code-editor if it's not a <textarea />");
            return;
        }

        mount.classList.remove("js-code-editor");
        const initialContent = mount.value;

        mountReact(
            <DashboardCodeEditor.Uncontrolled
                initialValue={initialContent}
                inputName={mount.name}
                inputID={mount.id}
            />,
            mount,
            undefined,
            { overwrite: true },
        );
    });
}
