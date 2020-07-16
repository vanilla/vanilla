/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBaseEmbedProps, FOCUS_CLASS, useEmbedContext } from "@library/embeddedContent/embedService";
import { embedMenuClasses } from "@rich-editor/editor/pieces/embedMenuStyles";
import classNames from "classnames";
import React, { useRef, useState } from "react";
import { ImageEmbedModal } from "@rich-editor/editor/pieces/ImageEmbedModal";
import { EmbedMenu } from "@rich-editor/editor/pieces/EmbedMenu";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { AccessibleImageMenuIcon, DeleteIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n/src";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { accessibleLabel } from "@library/utility/appUtils";

interface IProps extends IBaseEmbedProps {
    type: string; // Mime type.
    size: number;
    dateInserted: string;
    name: string;
    width?: number;
    height?: number;
}

/**
 * An embed class for quoted user content on the same site.
 */
export function ImageEmbed(props: IProps) {
    const contentRef = useRef<HTMLDivElement>(null);
    const [isOpen, setIsOpen] = useState(false);
    const { isSelected, inEditor, descriptionID } = useEmbedContext();

    return (
        <div ref={contentRef} className={classNames("embedImage", embedMenuClasses().imageContainer)}>
            <div className="embedImage-link">
                <img
                    aria-describedby={descriptionID}
                    className={classNames("embedImage-img", FOCUS_CLASS)}
                    src={props.url}
                    alt={accessibleLabel(t(`User: "%s"`), [props.name])}
                    tabIndex={props.inEditor ? -1 : undefined}
                />
            </div>
            {inEditor && (isSelected || isOpen) && (
                <EmbedMenu>
                    <Button
                        baseClass={ButtonTypes.ICON}
                        onClick={() => {
                            setIsOpen(true);
                        }}
                    >
                        <ScreenReaderContent>{t("Accessibility")}</ScreenReaderContent>
                        <AccessibleImageMenuIcon />
                    </Button>
                    <Button baseClass={ButtonTypes.ICON} onClick={props.deleteSelf}>
                        <ScreenReaderContent>{t("Delete")}</ScreenReaderContent>
                        <DeleteIcon />
                    </Button>
                </EmbedMenu>
            )}

            <ImageEmbedModal
                isVisible={isOpen}
                onSave={newValue => {
                    props.syncBackEmbedValue &&
                        props.syncBackEmbedValue({
                            name: newValue.alt,
                        });
                    props.selectSelf && props.selectSelf();
                }}
                initialAlt={props.name}
                onClose={() => {
                    setIsOpen(false);
                    setImmediate(() => {
                        props.selectSelf?.();
                    });
                }}
            />
        </div>
    );
}
