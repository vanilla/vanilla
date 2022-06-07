/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */
import React, { useEffect, useRef, useState } from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { EditIcon, LeftChevronIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n";
import LinkAsButton from "@library/routing/LinkAsButton";
import classNames from "classnames";
import { AutoWidthInput } from "@library/forms/AutoWidthInput";
import { autoWidthInputClasses } from "@library/forms/AutoWidthInput.classes";
import { adminEditTitleBarClasses } from "@dashboard/components/AdminTitleBar.classes";
import { useDebouncedInput } from "@dashboard/hooks";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";

interface IProps {
    title: string;
    disableSave?: boolean;
    cancelPath: string;
    onTitleChange?(value: string): void;
    autoFocusTitleInput?: boolean;
    isCompact?: boolean;
    leftPanel?: boolean;
    onSave?(): void;
    noSaveButton?: boolean;
    actions?: React.ReactNode;
}

export default function AdminEditTitleBar(props: IProps) {
    const { cancelPath, disableSave, onSave, autoFocusTitleInput, leftPanel } = props;

    const device = useTitleBarDevice();
    const isCompact = props.isCompact ?? device === TitleBarDevices.COMPACT;

    const editableRef = useRef<HTMLInputElement | null>();
    const [ownTitle, ownSetTitle] = useState(props.title);
    const title = props.title ?? ownTitle;
    const setTitle = props.onTitleChange ?? ownSetTitle;

    const classes = adminEditTitleBarClasses();

    const focusAndSelectAll = (event?: any) => {
        if (editableRef.current && editableRef.current !== document.activeElement) {
            if (event) event.preventDefault();
            editableRef.current.focus();
            document.execCommand("selectAll");
        }
    };

    const onSaveHandler = () => {
        if (title.trim() !== "") {
            onSave && onSave();
        } else {
            editableRef.current?.reportValidity();
        }
    };

    // Hook to debounce the input value to prevent calling onTitleChange as users type
    const debouncedTitle = useDebouncedInput(title, 500);

    useEffect(() => {
        if (debouncedTitle !== props.title && debouncedTitle !== "" && setTitle) {
            setTitle(debouncedTitle);
        }
    }, [debouncedTitle]);

    return (
        <>
            <div className={classes.editingContainerWrapper}>
                <div className={classNames(classes.editingContainer, !leftPanel ? classes.noLeftPanel : undefined)}>
                    <div className={classes.wrapper}>
                        <div className={classes.editLeftActions}>
                            <LinkAsButton buttonType={ButtonTypes.TEXT} className={classes.backButton} to={cancelPath}>
                                <LeftChevronIcon />
                                {t("Cancel")}
                            </LinkAsButton>
                        </div>
                        {!isCompact && (
                            <div className={classes.editTitle}>
                                <AutoWidthInput
                                    required
                                    onChange={(event) => setTitle(event.target.value)}
                                    className={classNames(autoWidthInputClasses().themeInput)}
                                    ref={(ref) => (editableRef.current = ref)}
                                    value={title}
                                    placeholder={t("Untitled")}
                                    disabled={disableSave}
                                    onFocus={(event) => event.target.select()}
                                    autoFocus={autoFocusTitleInput}
                                    onKeyDown={(event) => {
                                        if (event.key === "Enter") {
                                            event.preventDefault();
                                            (event.target as HTMLElement).blur();
                                        }
                                    }}
                                    onMouseDown={focusAndSelectAll}
                                    maxLength={100}
                                />

                                <Button
                                    buttonType={ButtonTypes.ICON}
                                    onClick={focusAndSelectAll}
                                    disabled={disableSave}
                                >
                                    <EditIcon small />
                                </Button>
                            </div>
                        )}
                        <div className={classes.editActions}>
                            {props.actions}
                            {!props.noSaveButton && (
                                <Button
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                    className={classes.saveButton}
                                    onClick={onSaveHandler}
                                    disabled={disableSave}
                                >
                                    {t("Save")}
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            </div>
            {isCompact && (
                <div className={classNames(classes.editTitle, classes.editTitleOnMobile)}>
                    <AutoWidthInput
                        required
                        onChange={(event) => setTitle(event.target.value)}
                        className={classNames(autoWidthInputClasses().themeInput)}
                        ref={(ref) => (editableRef.current = ref)}
                        value={title}
                        placeholder={t("Untitled")}
                        disabled={disableSave}
                        onFocus={(event) => event.target.select()}
                        autoFocus={autoFocusTitleInput}
                        onKeyDown={(event) => {
                            if (event.key === "Enter") {
                                event.preventDefault();
                                (event.target as HTMLElement).blur();
                            }
                        }}
                        onMouseDown={focusAndSelectAll}
                        maxLength={100}
                    />

                    <Button buttonType={ButtonTypes.ICON} onClick={focusAndSelectAll}>
                        <EditIcon small />
                    </Button>
                </div>
            )}
        </>
    );
}
