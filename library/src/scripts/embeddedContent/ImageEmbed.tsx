/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import { useEmbedContext } from "@library/embeddedContent/IEmbedContext";
import classNames from "classnames";
import React, { useRef, useState } from "react";
import { ImageEmbedModal } from "@rich-editor/editor/pieces/ImageEmbedModal";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import { EmbedContainerSize } from "@library/embeddedContent/components/EmbedContainerSize";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { AccessibleImageMenuIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n/src";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { accessibleLabel } from "@library/utility/appUtils";
import { EmbedDropdown } from "@library/embeddedContent/components/EmbedDropdown";
import {
    AlignCenterIcon,
    FloatLeftIcon,
    FloatRightIcon,
    ResizeLargeIcon,
    ResizeMediumIcon,
    ResizeSmallIcon,
} from "@library/icons/editorIcons";
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import { useUniqueID } from "@library/utility/idUtils";
interface IProps extends IBaseEmbedProps {
    type: string; // Mime type.
    size: number;
    dateInserted: string;
    name: string;
    width?: number;
    height?: number;
    displaySize?: "small" | "medium" | "large";
    float?: "left" | "right" | "none";
}

/**
 * An embed class for quoted user content on the same site.
 */
export function ImageEmbed(props: IProps) {
    const { displaySize = "large", float = "none", embedType } = props;
    const contentRef = useRef<HTMLDivElement>(null);
    const [isOpen, setIsOpen] = useState(false);
    const { descriptionID } = useEmbedContext();

    function setValue(value) {
        if (props.syncBackEmbedValue) props.syncBackEmbedValue(value);
    }

    const floatOptions = {
        left: { icon: <FloatLeftIcon />, label: t("Float Left") },
        none: { icon: <AlignCenterIcon />, label: t("Centered") },
        right: { icon: <FloatRightIcon />, label: t("Float Right") },
    };

    const displayOptions = {
        small: { icon: <ResizeSmallIcon />, label: t("Small") },
        medium: { icon: <ResizeMediumIcon />, label: t("Medium") },
        large: { icon: <ResizeLargeIcon />, label: t("Large") },
    };

    return (
        <EmbedContainer
            ref={contentRef}
            size={EmbedContainerSize.FULL_WIDTH}
            className={classNames("embedImage", `display-${displaySize}`, `float-${float}`)}
        >
            <EmbedContent
                type={embedType}
                embedActions={
                    <>
                        <EmbedDropdown
                            name="placement"
                            value={float}
                            label={floatOptions[float].label}
                            Icon={() => floatOptions[float].icon}
                        >
                            {Object.entries(floatOptions).map(([value, option]) => (
                                <EmbedDropdown.Option
                                    key={value}
                                    Icon={() => option.icon}
                                    value={value}
                                    label={option.label}
                                    onClick={() =>
                                        setValue({
                                            float: value,
                                            displaySize:
                                                displaySize === "large" && value !== "none" ? "medium" : displaySize,
                                        })
                                    }
                                />
                            ))}
                        </EmbedDropdown>
                        <EmbedDropdown
                            name="displaySize"
                            value={displaySize}
                            label={displayOptions[displaySize].label}
                            Icon={() => displayOptions[displaySize].icon}
                        >
                            {Object.entries(displayOptions).map(([value, option]) => (
                                <EmbedDropdown.Option
                                    key={value}
                                    Icon={() => option.icon}
                                    value={value}
                                    label={option.label}
                                    onClick={() =>
                                        setValue({
                                            displaySize: value,
                                            float: float !== "none" && value === "large" ? "none" : float,
                                        })
                                    }
                                />
                            ))}
                        </EmbedDropdown>
                        <EmbedButton onClick={() => setIsOpen(true)}>
                            <ScreenReaderContent>{t("Accessibility")}</ScreenReaderContent>
                            <AccessibleImageMenuIcon />
                        </EmbedButton>
                    </>
                }
            >
                <div className="embedImage-link">
                    <img
                        aria-describedby={descriptionID}
                        className={classNames("embedImage-img", EMBED_FOCUS_CLASS)}
                        src={props.url}
                        alt={accessibleLabel(t(`User: "%s"`), [props.name])}
                        tabIndex={props.inEditor ? -1 : undefined}
                    />
                </div>
            </EmbedContent>
            <ImageEmbedModal
                isVisible={isOpen}
                onSave={(newValue) => {
                    setValue({
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
        </EmbedContainer>
    );
}
