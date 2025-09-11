/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import { EmbedContainerSize } from "@library/embeddedContent/components/EmbedContainerSize";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService.register";
import { useEmbedContext } from "@library/embeddedContent/IEmbedContext";
import { ImageEmbedModal } from "@library/embeddedContent/ImageEmbedModal";
import { AccessibleImageMenuIcon } from "@library/icons/common";
import { useToast } from "@library/features/toaster/ToastContext";
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
import { accessibleLabel, buildUrl, formatUrl } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";
import { useRef, useState } from "react";
import { Icon } from "@vanilla/icons";
import { iconClasses } from "@library/icons/iconStyles";
import { css } from "@emotion/css";

interface IProps extends IBaseEmbedProps {
    type: string; // Mime type.
    size: number;
    dateInserted: string;
    name: string;
    width?: number;
    height?: number;
    displaySize?: "small" | "medium" | "large" | "inline";
    float?: "left" | "right" | "none";
    targetUrl?: string;
}

/**
 * An embed class for quoted user content on the same site.
 */
export function ImageEmbed(props: IProps) {
    const { displaySize = "large", float = "none", embedType } = props;
    const contentRef = useRef<HTMLDivElement>(null);
    const [isAltTextModalOpen, setIsAltTextModalOpen] = useState(false);
    const [isCustomUrlModalOpen, setIsCustomUrlModalOpen] = useState(false);
    const { descriptionID } = useEmbedContext();
    const toast = useToast();

    function setValue(value) {
        if (props.syncBackEmbedValue) props.syncBackEmbedValue(value);
    }

    const floatOptions = {
        left: { icon: <FloatLeftIcon />, label: t("Float Left") },
        none: { icon: <AlignCenterIcon />, label: t("Centered") },
        right: { icon: <FloatRightIcon />, label: t("Float Right") },
    };

    const classesRichEditor = iconClasses();

    const displayOptions = {
        inline: {
            icon: <Icon icon="editor-resize-inline" className={classesRichEditor.standard} />,
            label: t("Inline"),
        },
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
                ref={contentRef}
                positionBelow={displaySize === "inline"}
                type={embedType}
                embedActions={
                    <>
                        <MenuBarItem accessibleLabel={floatOptions[float].label} icon={floatOptions[float].icon}>
                            <MenuBarSubMenuItemGroup>
                                {displaySize === "inline" ? (
                                    <div className={classes.inlineImagePositionMessage}>
                                        {t(
                                            "Inline images can't be positioned. Change the size to see position options here.",
                                        )}
                                    </div>
                                ) : (
                                    Object.entries(floatOptions).map(([value, option]) => (
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
                                    ))
                                )}
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
                            accessibleLabel={t("Link Image to URL")}
                            onActivate={() => setIsCustomUrlModalOpen(true)}
                            icon={<Icon icon="copy-link" />}
                        />
                        <MenuBarItem
                            accessibleLabel={t("Copy Image")}
                            onActivate={async () => {
                                const imageToPaste = `<img src="${props.url}" alt="${
                                    props.name || ""
                                }" data-display-size="${props.displaySize}" data-float="${props.float}" />`;

                                try {
                                    if (navigator.clipboard && navigator.clipboard.write) {
                                        const clipboardItem = new ClipboardItem({
                                            "text/html": new Blob([imageToPaste], { type: "text/html" }),
                                            "text/plain": new Blob([imageToPaste], { type: "text/plain" }),
                                        });
                                        await navigator.clipboard.write([clipboardItem]);
                                        toast.addToast({
                                            autoDismiss: true,
                                            body: <>{t("Image copied to clipboard.")}</>,
                                        });
                                    }
                                } catch (error) {
                                    console.error("Failed to copy image to clipboard:", error);
                                }
                            }}
                            icon={<Icon icon="copy" />}
                        />
                        <MenuBarItem
                            accessibleLabel={t("Accessibility")}
                            onActivate={() => setIsAltTextModalOpen(true)}
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
                isVisible={isAltTextModalOpen}
                onSave={(newValue) => {
                    setValue({
                        name: newValue.alt,
                    });
                    props.selectSelf && props.selectSelf();
                }}
                initialAlt={props.name}
                onClose={() => {
                    setIsAltTextModalOpen(false);
                    setTimeout(() => {
                        props.selectSelf?.();
                    }, 0);
                }}
            />
            <ImageEmbedModal
                isVisible={isCustomUrlModalOpen}
                onSave={(newValue) => {
                    setValue({
                        targetUrl:
                            newValue.targetUrl && newValue.targetUrl !== ""
                                ? buildUrl(newValue.targetUrl, true)
                                : props.url,
                    });
                    props.selectSelf && props.selectSelf();
                }}
                initialTargetUrl={props.targetUrl ?? props.url}
                onClose={() => {
                    setIsCustomUrlModalOpen(false);
                    setTimeout(() => {
                        props.selectSelf?.();
                    }, 0);
                }}
            />
        </EmbedContainer>
    );
}

const classes = {
    inlineImagePositionMessage: css({ maxWidth: 140, padding: 8 }),
};
