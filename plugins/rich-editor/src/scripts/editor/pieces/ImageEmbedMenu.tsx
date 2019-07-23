/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject, useCallback, useRef, useState } from "react";
import { t } from "@library/utility/appUtils";
import DropDown from "@library/flyouts/DropDown";
import classNames from "classnames";
import { accessibleImageMenu } from "@library/icons/common";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { embedMenuClasses } from "@rich-editor/editor/pieces/embedMenuStyles";
import { ButtonTypes } from "@library/forms/buttonStyles";
import ButtonSubmit from "@library/forms/ButtonSubmit";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameBody from "@library/layout/frame/FrameBody";
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
    const classes = embedMenuClasses();
    const icon = accessibleImageMenu();
    const [alt, setAlt] = useState("");
    const { initialAlt = "", elementToFocusOnClose, setIsOpen } = props;
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
                classes.embedMetaDataMenu,
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
