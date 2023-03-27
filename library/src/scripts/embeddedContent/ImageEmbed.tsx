/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import { EmbedContainerSize } from "@library/embeddedContent/components/EmbedContainerSize";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { useEmbedContext } from "@library/embeddedContent/IEmbedContext";
import { ImageEmbedModal } from "@library/embeddedContent/ImageEmbedModal";
import { AccessibleImageMenuIcon } from "@library/icons/common";
import {
    AlignCenterIcon,
    FloatLeftIcon,
    FloatRightIcon,
    ResizeLargeIcon,
    ResizeMediumIcon,
    ResizeSmallIcon,
} from "@library/icons/editorIcons";
import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { MenuBarSubMenuItem } from "@library/MenuBar/MenuBarSubMenuItem";
import { MenuBarSubMenuItemGroup } from "@library/MenuBar/MenuBarSubMenuItemGroup";
import { accessibleLabel } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";
import React, { useRef, useState } from "react";
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
                        <MenuBarItem accessibleLabel={floatOptions[float].label} icon={floatOptions[float].icon}>
                            <MenuBarSubMenuItemGroup>
                                {Object.entries(floatOptions).map(([value, option]) => (
                                    <MenuBarSubMenuItem
                                        key={value}
                                        icon={option.icon}
                                        active={float === value}
                                        onActivate={() =>
                                            setValue({
                                                float: value,
                                                displaySize:
                                                    displaySize === "large" && value !== "none"
                                                        ? "medium"
                                                        : displaySize,
                                            })
                                        }
                                    >
                                        {option.label}
                                    </MenuBarSubMenuItem>
                                ))}
                            </MenuBarSubMenuItemGroup>
                        </MenuBarItem>

                        <MenuBarItem
                            icon={displayOptions[displaySize].icon}
                            accessibleLabel={displayOptions[displaySize].label}
                        >
                            <MenuBarSubMenuItemGroup>
                                {Object.entries(displayOptions).map(([value, option]) => (
                                    <MenuBarSubMenuItem
                                        key={value}
                                        icon={option.icon}
                                        active={displaySize === value}
                                        onActivate={() => {
                                            setValue({
                                                displaySize: value,
                                                float: float !== "none" && value === "large" ? "none" : float,
                                            });
                                        }}
                                    >
                                        {option.label}
                                    </MenuBarSubMenuItem>
                                ))}
                            </MenuBarSubMenuItemGroup>
                        </MenuBarItem>
                        <MenuBarItem
                            accessibleLabel={t("Accessibility")}
                            onActivate={() => setIsOpen(true)}
                            icon={<AccessibleImageMenuIcon />}
                        />
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
                        loading="lazy"
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
                    setTimeout(() => {
                        props.selectSelf?.();
                    }, 0);
                }}
            />
        </EmbedContainer>
    );
}
