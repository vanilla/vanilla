/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { fragmentEditorClasses } from "@dashboard/appearance/fragmentEditor/FragmentEditor.classes";
import { useFragmentEditor } from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import { FragmentEditorSettingsModal } from "@dashboard/appearance/fragmentEditor/FragmentEditor.Settings";
import { FragmentEditorDiffViewer } from "@dashboard/appearance/fragmentEditor/FragmentEditorDiffViewer";
import { cx } from "@emotion/css";
import DropDown from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Button from "@library/forms/Button";
import { ButtonType } from "@library/forms/buttonTypes";
import { LeftChevronIcon, DownTriangleIcon } from "@library/icons/common";
import FlexSpacer from "@library/layout/FlexSpacer";
import { Row } from "@library/layout/Row";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useDeferredFocuser } from "@vanilla/react-utils";
import { labelize, guessOperatingSystem, OS } from "@vanilla/utils";
import { useState, useEffect } from "react";

export function FragmentEditorTitleBar() {
    const { form, updateForm, settings, updateSettings, fragmentUUID, saveFormMutation } = useFragmentEditor();
    const classes = fragmentEditorClasses.useAsHook();
    const [isTitleEditEnabled, setIsTitleEditEnabled] = useState(false);
    const [showSettings, setShowSettings] = useState(false);
    const [showCommitDiff, setShowCommitDiff] = useState<false | "shortcut" | "button">(false);

    const titlePlaceholder = t("Fragment Title");
    const deferredFocuser = useDeferredFocuser();
    useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            // Save event keyboard shortcut.
            const isCtrlPressed = (guessOperatingSystem() === OS.MAC && event.metaKey) || event.ctrlKey;
            if (isCtrlPressed) {
                if (event.key === "c" && event.shiftKey) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (fragmentUUID && event.shiftKey) {
                        setShowCommitDiff("shortcut");
                    }
                } else if (event.key === "s") {
                    event.preventDefault();
                    event.stopPropagation();
                    saveFormMutation.mutate({});
                }
            }
        };
        document.addEventListener("keydown", handler);
        return () => {
            document.removeEventListener("keydown", handler);
        };
    }, [fragmentUUID]);

    return (
        <div className={classes.titleBar}>
            {showCommitDiff === "shortcut" && (
                <FragmentEditorDiffViewer
                    modifiedRevisionUUID={"latest"}
                    onClose={() => {
                        setShowCommitDiff(false);
                    }}
                />
            )}
            <Row width={"100%"}>
                <LinkAsButton buttonType={"text"} className={cx(classes.backButton)} to={"/appearance/widget-builder"}>
                    <LeftChevronIcon />
                    {t("Back")}
                </LinkAsButton>
                <FlexSpacer actualSpacer={true} />
                <span className={classes.titleGroup}>
                    <span className={classes.titleType}>{labelize(form.fragmentType)}</span>
                    <span className={classes.titleSep}>{"/"}</span>
                    {isTitleEditEnabled ? (
                        <form
                            className={classes.titleValue}
                            onSubmit={(e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                if (!(document.getElementById("titleInput") as HTMLInputElement).reportValidity()) {
                                    return;
                                }
                                setIsTitleEditEnabled(false);
                                deferredFocuser.focusElementBySelector(`#titleEditButton`);
                            }}
                        >
                            <input
                                required={true}
                                minLength={4}
                                id="titleInput"
                                className={classes.titleInput}
                                type="text"
                                value={form.name}
                                onChange={(event) => {
                                    updateForm({ name: event.target.value });
                                }}
                                size={Math.min(Math.max(form.name.length, titlePlaceholder.length), 40)}
                                placeholder={titlePlaceholder}
                            />
                            <Button buttonType={ButtonType.ICON_COMPACT} submit={true}>
                                <Icon size={"compact"} icon="data-checked" />
                            </Button>
                        </form>
                    ) : (
                        <span className={classes.titleValue}>
                            {form.name}
                            <Button
                                id="titleEditButton"
                                buttonType={"iconCompact"}
                                onClick={() => {
                                    setIsTitleEditEnabled(true);
                                    deferredFocuser.focusElementBySelector(`#titleInput`);
                                }}
                            >
                                <Icon size={"compact"} icon={"edit"} />
                            </Button>
                        </span>
                    )}
                </span>

                <FlexSpacer actualSpacer={true} />
                <Row gap={12} align={"center"}>
                    <Button
                        buttonType={"text"}
                        onClick={() => {
                            setShowSettings(true);
                        }}
                    >
                        {t("Developer Settings")}
                    </Button>
                    <DropDown
                        buttonType={"textPrimary"}
                        buttonContents={
                            <Row align={"center"} gap={8}>
                                {!fragmentUUID ? t("Create") : t("Save Draft")}
                                <DownTriangleIcon />
                            </Row>
                        }
                    >
                        <DropDownItemButton
                            shortcut={`${platformSpecificKey()}S`}
                            onClick={() => {
                                saveFormMutation.mutate({});
                            }}
                        >
                            {t("Save Draft")}
                        </DropDownItemButton>
                        <DropDownItemButton
                            shortcut={`${platformSpecificKey()}⇧C`}
                            onClick={() => {
                                setShowCommitDiff("button");
                            }}
                        >
                            {t("Compare & Commit")}
                            {showCommitDiff === "button" && (
                                <FragmentEditorDiffViewer
                                    modifiedRevisionUUID={"latest"}
                                    onClose={() => {
                                        setShowCommitDiff(false);
                                    }}
                                />
                            )}
                        </DropDownItemButton>
                    </DropDown>
                </Row>
            </Row>
            <FragmentEditorSettingsModal
                settings={settings}
                onSave={updateSettings}
                isVisible={showSettings}
                onVisibilityChange={setShowSettings}
            />
        </div>
    );
}

function platformSpecificKey() {
    const os = guessOperatingSystem();
    return os === OS.MAC || OS.IOS ? "⌘" : "Ctrl";
}
