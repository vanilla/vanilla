/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject, useCallback, useRef, useState } from "react";
import { t } from "@library/utility/appUtils";
import DropDown, { FlyoutSizes } from "@library/flyouts/DropDown";
import classNames from "classnames";
import { accessibleImageMenu } from "@library/icons/common";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { useUniqueID } from "@library/utility/idUtils";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { editorFormClasses } from "@knowledge/modules/editor/editorFormStyles";
import { embedMenuClasses } from "@library/embeddedContent/menus/embedMenuStyles";
import Button from "@library/forms/Button";
import { ButtonTypes, buttonUtilityClasses } from "@library/forms/buttonStyles";
import ButtonSubmit from "@library/forms/ButtonSubmit";
import FrameFooter from "@library/layout/frame/FrameFooter";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import Paragraph from "@library/layout/Paragraph";
import InputTextBlock from "@library/forms/InputTextBlock";

interface IProps extends IImageMeta {
    saveImageMeta?: () => void;
    initialAlt?: string;
    elementToFocusOnClose: RefObject<HTMLDivElement>;
    setIsOpen: (isOpen: boolean) => void;
    className?: string;
}

export interface IImageMeta {
    alt?: string;
}

/**
 * A class for rendering Giphy embeds.
 */
export function ImageEmbedMenu(props: IProps) {
    const classesDropDown = dropDownClasses();
    const classes = embedMenuClasses();
    const classesEditorForm = editorFormClasses();
    const icon = accessibleImageMenu();

    const [disabled, setDisabled] = useState(false);
    const [saved, setSaved] = useState(false);
    const [alt, setAlt] = useState("");
    const [portalLocation, setPortalLocation] = useState();

    const { saveImageMeta, initialAlt = "", elementToFocusOnClose, setIsOpen } = props;
    // const id = useUniqueID("imageEmbedMenu");
    // const divRef = useRef<HTMLDivElement>(null);

    const device = useDevice();

    const onVisibilityChange = useCallback(isVisible => {
        if (isVisible) {
            setIsOpen(true);
        } else {
            setIsOpen(false);
            if (elementToFocusOnClose && elementToFocusOnClose.current) {
                elementToFocusOnClose.current.focus();
            }
        }
    }, []);

    // const onChange = useCallback(event => {}, []);
    //
    // const onCancelClose = useCallback(event => {
    //     if (event) {
    //         setSaved(false);
    //         setAlt(initialAlt);
    //     }
    // }, []);
    //
    // const onSaveClose = useCallback(event => {
    //     if (event) {
    //         event.stopPropagation();
    //         event.preventDefault();
    //         setSaved(true);
    //         if (elementToFocusOnClose) {
    //             if ("current" in elementToFocusOnClose) {
    //                 elementToFocusOnClose.current!.focus();
    //             } else {
    //                 elementToFocusOnClose.focus();
    //             }
    //         }
    //     }
    // }, []);

    const handleTextChange = useCallback(event => {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
            setAlt(event.target.value || "");
        }
    }, []);

    return (
        <div
            className={classNames(
                classes.root,
                "u-excludeFromPointerEvents",
                classesEditorForm.embedMetaDataMenu,
                props.className,
            )}
        >
            <DropDown
                title={t("Alt Text")}
                buttonContents={icon}
                className={classNames("u-excludeFromPointerEvents")}
                onVisibilityChange={onVisibilityChange}
                openAsModal={device === Devices.MOBILE || device === Devices.XS}
                selfPadded={true}
                isNotList={true}
            >
                <form
                    className={classes.form}
                    onSubmit={e => {
                        e.preventDefault();
                    }}
                >
                    <FrameBody className={classes.verticalPadding}>
                        {/*<Paragraph className={classes.paragraph}>*/}
                        {/*    {t("Alternative text helps users with accessibility concerns and improves SEO.")}*/}
                        {/*</Paragraph>*/}
                        <InputTextBlock
                            label={t("Alternative text helps users with accessibility concerns and improves SEO.")}
                            labelClass={classes.paragraph}
                            inputProps={{
                                required: true,
                                value: alt || initialAlt,
                                onChange: handleTextChange,
                                disabled: true,
                                placeholder: t("(Image description)"),
                            }}
                        />
                    </FrameBody>
                    <FrameFooter justifyRight={true}>
                        <ButtonSubmit baseClass={ButtonTypes.TEXT_PRIMARY}>{t("Insert")}</ButtonSubmit>
                    </FrameFooter>
                </form>
            </DropDown>
        </div>
    );
}
