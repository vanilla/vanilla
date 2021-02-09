/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import TextEditor from "@vanilla/library/src/scripts/textEditor/TextEditor";
import { mountReact } from "@vanilla/react-utils";
import { logWarning } from "@vanilla/utils";
import React, { useState } from "react";

interface IProps {
    initialValue: string;
    inputName: string;
    inputID?: string;
    language?: string;
}

export function DashboardCodeEditor(props: IProps) {
    const [value, setValue] = useState(props.initialValue);
    const formGroup = useFormGroup();
    const inputID = props.inputID ?? formGroup.inputID;

    return (
        <div className="input-wrap">
            <input id={inputID} name={props.inputName} type="hidden" value={value} aria-hidden={true} />
            <TextEditor
                minimal
                language={props.language ?? "text/html"}
                value={props.initialValue}
                onChange={(e, value) => setValue(value ?? "")}
            />
        </div>
    );
}

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
            <DashboardCodeEditor initialValue={initialContent} inputName={mount.name} inputID={mount.id} />,
            mount,
            undefined,
            { overwrite: true },
        );
    });
}
