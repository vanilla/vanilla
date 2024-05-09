/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { css } from "@emotion/css";
import { IInputProps } from "@library/forms/InputTextBlock";
import TextEditor from "@library/textEditor/TextEditor";
import { mountReact } from "@vanilla/react-utils";
import { logWarning } from "@vanilla/utils";
import React, { useMemo, useState } from "react";

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
        <div className="input-wrap">
            <input id={inputID} name={inputName} type="hidden" value={value || ""} aria-hidden={true} />
            <TextEditor
                minimal
                language={language ?? "text/html"}
                jsonSchemaUri={jsonSchemaUri}
                value={value}
                onChange={(e, value) => onChange(value ?? "")}
                {...styleOverrides}
            />
        </div>
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
