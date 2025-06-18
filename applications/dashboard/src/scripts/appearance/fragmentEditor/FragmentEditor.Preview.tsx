/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { fragmentEditorClasses } from "@dashboard/appearance/fragmentEditor/FragmentEditor.classes";
import { useFragmentEditor } from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import { FragmentEditorCommunication } from "@dashboard/appearance/fragmentEditor/FragmentEditorCommunication";
import { FragmentEditorPreviewRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import { EditorRolePreviewDropDownItem, EditorRolePreviewProvider } from "@dashboard/roles/EditorRolePreviewContext";
import { cx } from "@emotion/css";
import DropDown from "@library/flyouts/DropDown";
import DropDownSwitchButton from "@library/flyouts/DropDownSwitchButton";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import { ButtonType } from "@library/forms/buttonTypes";
import { NestedSelect } from "@library/forms/nestedSelect";
import type { Select } from "@library/json-schema-forms";
import FlexSpacer from "@library/layout/FlexSpacer";
import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";
import { EditorThemePreviewDropDownItem, EditorThemePreviewProvider } from "@library/theming/EditorThemePreviewContext";
import { t } from "@vanilla/i18n";
import { useSessionStorage } from "@vanilla/react-utils";
import { useEffect, useRef, useState } from "react";

export function FragmentEditorPreview() {
    const editor = useFragmentEditor();
    const { form, Communication } = editor;
    const [alignment, setAlignment] = useState<string>("none");

    const iframeRef = useRef<HTMLIFrameElement>(null);

    useEffect(() => {
        const iframeWindow = iframeRef.current?.contentWindow;
        if (iframeWindow) {
            editor.onCommunicationEstablished(new FragmentEditorCommunication(window, iframeWindow));
        }
    }, [iframeRef.current]);

    const previewDataOptions: Select.Option[] = editor.form.previewData.map((data, i) => {
        return {
            label: data.name,
            value: i.toString(),
        };
    });
    const [scalePercent, setScalePercent] = useSessionStorage("fragmentEditorScalePercent", "100%");

    const transform = (() => {
        switch (scalePercent) {
            case "150%":
                return "scale(0.6666666667)";
            case "200%":
                return "scale(0.5)";
            default:
                return undefined;
        }
    })();
    const classes = fragmentEditorClasses();

    return (
        <div className={classes.previewContainer}>
            <div className={classes.previewControls}>
                <NestedSelect
                    inline={true}
                    compact={true}
                    label={t("Preview Data") + ":"}
                    value={editor.selectedPreviewDataIndex}
                    onChange={(value: string) => {
                        editor.setSelectedPreviewDataIndex(parseInt(value));
                    }}
                    options={previewDataOptions}
                />
                <NestedSelect
                    inline={true}
                    compact={true}
                    label={t("Scale") + ":"}
                    value={scalePercent}
                    onChange={(value: string) => {
                        setScalePercent(value);
                        Communication.sendMessage({ type: "rerender" });
                    }}
                    options={[
                        { label: "200%", value: "200%" },
                        { label: "150%", value: "150%" },
                        { label: "100%", value: "100%" },
                        { label: "75%", value: "75%" },
                        { label: "50%", value: "50%" },
                        { label: "25%", value: "25%" },
                    ]}
                />
                <FlexSpacer actualSpacer={true} />
                <EditorThemePreviewProvider
                    onPreviewedThemeIDChange={(themeID) => {
                        if (themeID) {
                            Communication.sendMessage({
                                type: "previewSettings",
                                previewThemeID: themeID,
                            });
                        }
                    }}
                >
                    <EditorRolePreviewProvider
                        onSelectedRoleIDsChange={(newRoleIDs) => {
                            Communication.sendMessage({
                                type: "previewSettings",
                                previewRoleIDs: newRoleIDs,
                            });
                        }}
                    >
                        <DropDown buttonType={ButtonType.TEXT}>
                            <EditorThemePreviewDropDownItem />
                            <DropDownItemSeparator />
                            <EditorRolePreviewDropDownItem />
                            <DropDownItemSeparator />
                            <DropDownSection title={"Alignment"}>
                                <DropDownSwitchButton
                                    onClick={() => {
                                        setAlignment("none");
                                        Communication.sendMessage({
                                            type: "previewSettings",
                                            alignment: "none",
                                        });
                                    }}
                                    label={t("None")}
                                    status={alignment === "none"}
                                />
                                <DropDownSwitchButton
                                    label={t("Centered")}
                                    status={alignment === "center"}
                                    onClick={() => {
                                        setAlignment("center");
                                        Communication.sendMessage({
                                            type: "previewSettings",
                                            alignment: "center",
                                        });
                                    }}
                                />
                            </DropDownSection>
                        </DropDown>
                    </EditorRolePreviewProvider>
                </EditorThemePreviewProvider>
            </div>
            {!editor.isPreviewLoaded && (
                <div>
                    <Loader size={200} loaderStyleClass={loaderClasses().mediumLoader} />
                </div>
            )}
            <div
                className={classes.previewFrameWrapper}
                style={{
                    transform,
                    width: scalePercent,
                    minHeight: scalePercent,
                    height: scalePercent,
                    transformOrigin: "top left",
                }}
            >
                <iframe
                    ref={iframeRef}
                    className={cx(classes.previewFrame, { isLoaded: editor.isPreviewLoaded })}
                    width="100%"
                    height="100%"
                    scrolling="yes"
                    src={FragmentEditorPreviewRoute.url({ fragmentType: editor.form.fragmentType })}
                />
            </div>
        </div>
    );
}
