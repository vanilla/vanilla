/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject, useCallback, useMemo, useRef, useState } from "react";
import { t } from "@library/utility/appUtils";
import DropDown from "@library/flyouts/DropDown";
import classNames from "classnames";
import { accessibleImageMenu } from "@library/icons/common";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { imageEmbedMenuClasses } from "@library/embeddedContent/menus/ImageEmbedMenuStyles";
import Paragraph from "@library/layout/Paragraph";
import InputBlock from "@library/forms/InputBlock";
import InputTextBlock from "@library/forms/InputTextBlock";
import { getFieldErrors } from "@library/apiv2";
import ButtonSubmit from "@library/forms/ButtonSubmit";
import { getRequiredID, uniqueIDFromPrefix, useUniqueID } from "@library/utility/idUtils";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import ModalConfirm from "@library/modal/ModalConfirm";
import { LoadStatus } from "@library/@types/api/core";
import { debuglog } from "util";
import { element } from "prop-types";

interface IProps extends IImageMeta {
    saveImageMeta?: () => void;
    initialAlt?: string;
    elementToFocusOnClose: RefObject<HTMLDivElement>;
}

export interface IImageMeta {
    alt?: string;
}

interface IState extends IImageMeta {
    disable?: boolean;
    saved: boolean;
    showModal: boolean;
}

/**
 * A class for rendering Giphy embeds.
 */
export function ImageEmbedMenu(props: IProps, state: IState): JSX.Element {
    const classesDropDown = dropDownClasses();
    const classes = imageEmbedMenuClasses();
    const icon = accessibleImageMenu();

    const [disable, setDisable] = useState(false);
    const [saved, setSaved] = useState(false);
    const [showModal, setShowModal] = useState(false);
    const [alt, setAlt] = useState("");

    const { saveImageMeta, initialAlt = "", elementToFocusOnClose } = props;
    const id = useUniqueID("imageEmbedMenu");
    let textInput = useRef();

    const onVisibilityChange = useCallback(event => {
        if (!saved) {
            event.preventDefault();
            event.stopPropagation();
            if (state.alt !== initialAlt && initialAlt !== "") {
                // Don't care if they never set anything
                setShowModal(true);
            } else {
                // do submit
                debuglog("Submitting with alt text: " + alt);
            }
        }
    }, []);

    const onChange = useCallback(event => {}, []);

    const onCancelClose = useCallback(event => {
        if (event) {
            setShowModal(false);
            setSaved(false);
            setAlt(initialAlt);
        }
    }, []);

    const onSaveClose = useCallback(event => {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
            setSaved(true);
            setShowModal(false);
            if (elementToFocusOnClose && elementToFocusOnClose.current) {
                elementToFocusOnClose.current.focus();
            }
        }
    }, []);

    const handleTextChange = useCallback(event => {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
    }, []);

    return (
        <div className={classes.root}>
            {showModal && (
                <ModalConfirm
                    title={t("Are you sure you want to ")}
                    onCancel={onCancelClose}
                    onConfirm={onSaveClose}
                    elementToFocusOnExit={elementToFocusOnClose.current as HTMLElement}
                >
                    {t("This is a destructive action. You will not be able to restore your draft.")}
                </ModalConfirm>
            )}
            <DropDown title={t("Alt Text")} buttonContents={icon} onVisibilityChange={onVisibilityChange}>
                <div className={classNames("dropDownItem-verticalPadding", classesDropDown.verticalPadding)}>
                    <form
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
                                value: state.alt || "",
                                onChange: handleTextChange,
                                disabled: !disable,
                                ref: textInput,
                            }}
                        />
                        <ButtonSubmit>{t("Insert")}</ButtonSubmit>
                    </form>
                </div>
            </DropDown>
        </div>
    );
}
