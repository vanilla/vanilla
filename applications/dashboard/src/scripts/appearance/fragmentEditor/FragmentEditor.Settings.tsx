/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { SchemaFormBuilder } from "@library/json-schema-forms";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import type { Dispatch, SetStateAction } from "react";

export interface IFragmentEditorSettings {
    theme: "dark" | "light";
    previewUpdateFrequency: "onChange" | "onSave";
    formatOnSave: boolean;
    filesWidthPercentage: string;
    fontSize: string;
    inlayHints: boolean;
    showMinimap: "yes" | "no";
}

interface IProps {
    settings: IFragmentEditorSettings;
    onSave: Dispatch<SetStateAction<IFragmentEditorSettings>>;
    isVisible: boolean;
    onVisibilityChange: (newVisibility: boolean) => void;
}

export function FragmentEditorSettingsModal(props: IProps) {
    return (
        <Modal size={ModalSizes.MEDIUM} isVisible={props.isVisible} exitHandler={() => props.onVisibilityChange(false)}>
            <Frame
                header={<FrameHeader title={"Developer Settings"} closeFrame={() => props.onVisibilityChange(false)} />}
                body={
                    <FrameBody>
                        <DashboardSchemaForm
                            autoValidate={true}
                            instance={props.settings}
                            onChange={(valuesDispatch) => props.onSave(valuesDispatch())}
                            schema={SchemaFormBuilder.create()
                                .selectStatic("theme", "Theme", "Set the visible theme in editor", [
                                    {
                                        label: "Light",
                                        value: "light",
                                    },
                                    {
                                        label: "Dark",
                                        value: "dark",
                                    },
                                ])
                                .required()
                                .selectStatic(
                                    "previewUpdateFrequency",
                                    "Preview Update Frequency",
                                    "Controls how frequently the preview updates.",
                                    [
                                        {
                                            label: "On Change",
                                            value: "onChange",
                                        },
                                        {
                                            label: "On Save",
                                            value: "onSave",
                                        },
                                    ],
                                )
                                .required()
                                .checkBoxRight(
                                    "formatOnSave",
                                    "Format on Save",
                                    "Automatically format the code when saving.",
                                )
                                .textBox("fontSize", "Font Size", "Set the font size in the editor.")
                                .required()
                                .selectStatic("showMinimap", "Show Minimap", "Show the minimap in the editor.", [
                                    {
                                        label: "Yes",
                                        value: "yes",
                                    },
                                    {
                                        label: "No",
                                        value: "no",
                                    },
                                ])
                                .checkBoxRight("inlayHints", "Inlay Hints", "Show inlay hints in the editor.")
                                .required()
                                .getSchema()}
                        />
                    </FrameBody>
                }
            />
        </Modal>
    );
}
