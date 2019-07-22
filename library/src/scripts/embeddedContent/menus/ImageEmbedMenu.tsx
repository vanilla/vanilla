/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject, useCallback, useMemo, useRef, useState } from "react";
import { t } from "@library/utility/appUtils";
import DropDown, { FlyoutSizes } from "@library/flyouts/DropDown";
import classNames from "classnames";
import { accessibleImageMenu } from "@library/icons/common";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import InputTextBlock from "@library/forms/InputTextBlock";
import ButtonSubmit from "@library/forms/ButtonSubmit";
import { useUniqueID } from "@library/utility/idUtils";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import ModalConfirm from "@library/modal/ModalConfirm";
import { debuglog } from "util";
import DropDownPaddedFrame from "@library/flyouts/items/DropDownPaddedFrame";
import { Devices, IDeviceProps, useDevice } from "@library/layout/DeviceContext";
import ReactDOM from "react-dom";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import { editorFormClasses } from "@knowledge/modules/editor/editorFormStyles";
import { getIDForQuill } from "@rich-editor/quill/utility";
import { embedMenuClasses } from "@library/embeddedContent/menus/embedMenuStyles";

interface IProps extends IImageMeta {
    saveImageMeta?: () => void;
    initialAlt?: string;
    elementToFocusOnClose: RefObject<HTMLDivElement> | HTMLDivElement | null;
}

export interface IImageMeta {
    alt?: string;
    isFocused: boolean;
}

/**
 * A class for rendering Giphy embeds.
 */
export function ImageEmbedMenu(props: IProps) {
    const classesDropDown = dropDownClasses();
    const classes = embedMenuClasses();
    const classesEditorForm = editorFormClasses();
    const icon = accessibleImageMenu();

    const [disable, setDisable] = useState(false);
    const [saved, setSaved] = useState(false);
    const [alt, setAlt] = useState("");
    const [portalLocation, setPortalLocation] = useState();

    const { saveImageMeta, initialAlt = "", elementToFocusOnClose, isFocused } = props;
    const id = useUniqueID("imageEmbedMenu");
    let textInput = useRef();
    const divRef = useRef<HTMLDivElement>(null);

    const device = useDevice();

    const onVisibilityChange = useCallback(isVisible => {
        setAlt(initialAlt);
    }, []);

    const onChange = useCallback(event => {}, []);

    const onCancelClose = useCallback(event => {
        if (event) {
            setSaved(false);
            setAlt(initialAlt);
        }
    }, []);

    const onSaveClose = useCallback(event => {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
            setSaved(true);
            if (elementToFocusOnClose) {
                if ("current" in elementToFocusOnClose) {
                    elementToFocusOnClose.current!.focus();
                } else {
                    elementToFocusOnClose.focus();
                }
            }
        }
    }, []);

    const handleTextChange = useCallback(event => {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
            setAlt(event.target.value || "");
        }
    }, []);

    return (
        <div
            className={classNames(classes.root, "u-excludeFromPointerEvents")}
            style={{ position: "absolute", top: 0, left: 0, transform: "translateX(-100%)", zIndex: 1 }}
        >
            {/*{showModal && (*/}
            {/*    <ModalConfirm*/}
            {/*        title={t("Are you sure you want to ")}*/}
            {/*        onCancel={onCancelClose}*/}
            {/*        onConfirm={onSaveClose}*/}
            {/*        elementToFocusOnExit={elementToFocusOnClose.current as HTMLElement}*/}
            {/*    >*/}
            {/*        {t("This is a destructive action. You will not be able to restore your draft.")}*/}
            {/*    </ModalConfirm>*/}
            {/*)}*/}
            <DropDown
                title={t("Alt Text")}
                buttonContents={icon}
                className={classNames("u-excludeFromPointerEvents")}
                onVisibilityChange={onVisibilityChange}
                size={FlyoutSizes.MEDIUM}
                openAsModal={device === Devices.MOBILE || device === Devices.XS}
            >
                <DropDownPaddedFrame>
                    <form
                        className={classes.form}
                        onSubmit={e => {
                            e.preventDefault();
                        }}
                    >
                        <ScreenReaderContent>
                            {t("Edit the image's meta data to make it more SEO friendly and accessible!")}
                        </ScreenReaderContent>
                        <InputTextBlock
                            label={t("Alternative text helps users with accessibility concerns and improves SEO.")}
                            inputProps={{
                                required: true,
                                value: alt || "",
                                onChange: handleTextChange,
                                disabled: !disable,
                                ref: textInput,
                            }}
                        />
                        <ButtonSubmit>{t("Insert")}</ButtonSubmit>
                    </form>
                </DropDownPaddedFrame>
            </DropDown>
        </div>
    );
}
