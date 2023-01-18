/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { insertMediaClasses } from "@library/editor/flyouts/pieces/insertMediaClasses";
import { supportsFrames } from "@library/embeddedContent/IFrameEmbed";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import InputTextBlock from "@library/forms/InputTextBlock";
import { EmbedIcon } from "@library/icons/editorIcons";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { isURL, normalizeUrl, t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { forceInt } from "@vanilla/utils";
import classNames from "classnames";
import debounce from "lodash/debounce";
import React, { useCallback, useRef, useState } from "react";

interface IProps {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
    createEmbed: (url: string) => void;
    createIframe: (url: string, frameHeight: number, frameWidth: number) => void;
}

export default function EmbedFlyout(props: IProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const ID = useUniqueID("embedPopover");
    const handleID = ID + "-handle";
    const contentID = ID + "-contents";
    const descriptionID = ID + "-description";

    const [isFrame, setIsFrame] = useState(false);
    const [frameHeight, setFrameHeight] = useState<number>(900);
    const [frameWidth, setFrameWidth] = useState(1600);
    const [url, setUrl] = useState<string | null>(null);
    const [inputValue, setInputValue] = useState("");
    const isInputValid = !!url;

    const clearState = () => {
        setIsFrame(false);
        setFrameHeight(900);
        setFrameWidth(1600);
        setUrl(null);
    };

    const clearInput = () => {
        clearState();
        setInputValue("");
    };

    const submitUrl = () => {
        if (!url) {
            return;
        }

        clearInput();

        if (isFrame) {
            props.createIframe(url, frameHeight, frameWidth);
        } else {
            props.createEmbed(url);
        }
    };

    /**
     * Handle key-presses for the link toolbar.
     */
    const buttonKeyDownHandler = (event: React.KeyboardEvent<any>) => {
        if (event.key === "Enter") {
            event.preventDefault();
            event.stopPropagation();
            isInputValid && submitUrl();
        }
    };

    /**
     * Handle a submit button click..
     */
    const buttonClickHandler = (event: React.MouseEvent<any>) => {
        event.preventDefault();
        submitUrl();
    };

    const handleFrameHtml = (html: string) => {
        const container = document.createElement("div");
        container.innerHTML = html;
        const iframe = container.querySelector("iframe");
        if (!iframe) {
            clearState();
        } else {
            const src = iframe.getAttribute("src");

            if (!src) {
                clearState();
                return;
            }
            setUrl(normalizeUrl(src));

            // See if our height/width are relevant
            const height = forceInt(iframe.getAttribute("height"), 900);
            const width = forceInt(iframe.getAttribute("width"), 1600);
            setFrameHeight(height);
            setFrameWidth(width);
        }
        setIsFrame(true);
    };

    const parseInput = useCallback(
        debounce((value: string) => {
            const isFrame = /<iframe/.test(value) && supportsFrames();
            if (isFrame) {
                handleFrameHtml(value);
            } else {
                setIsFrame(false);
                const normalized = normalizeUrl(value);
                setUrl(isURL(normalized) ? normalized : null);
            }
        }, 100),
        [],
    );

    /**
     * Control the inputs value.
     */
    const inputChangeHandler = (event: React.ChangeEvent<any>) => {
        const { value } = event.target;
        setInputValue(value);
        parseInput(value);
    };

    const classesInsertMedia = insertMediaClasses();
    const placeholderText = supportsFrames() ? t(`Url or Embed Code`) : t(`Url`);

    function handleVisibilityChange(newVisibility: boolean) {
        if (newVisibility) {
            inputRef.current && inputRef.current.focus();
        }
    }

    const title = t("Insert Media");

    return (
        <>
            <DropDown
                handleID={handleID}
                contentID={contentID}
                title={title}
                name={title}
                onVisibilityChange={handleVisibilityChange}
                disabled={props.disabled}
                buttonContents={
                    <>
                        <ScreenReaderContent>{t("Insert Media")}</ScreenReaderContent>
                        <EmbedIcon />
                    </>
                }
                buttonType={ButtonTypes.ICON_MENUBAR}
                renderAbove={!!props.renderAbove}
                renderLeft={!!props.renderLeft}
                initialFocusElement={inputRef.current}
                flyoutType={FlyoutType.FRAME}
            >
                <Frame
                    body={
                        <FrameBody>
                            <p id={descriptionID} className={css({ marginTop: 12, marginBottom: 6 })}>
                                {t("Paste the URL of the media you want.")}
                                {supportsFrames() && <> {t("You can also use an <iframe /> embed code here.")}</>}
                            </p>
                            <InputTextBlock
                                className={classNames(
                                    classesInsertMedia.insert,
                                    isFrame && classesInsertMedia.insertCode,
                                )}
                                descriptionID={descriptionID}
                                multiLineProps={{
                                    rows: 1,
                                }}
                                inputProps={{
                                    "aria-label": title,
                                    "aria-describedby": descriptionID,
                                    onKeyPress: buttonKeyDownHandler,
                                    inputRef,
                                    placeholder: placeholderText,
                                    multiline: supportsFrames(),
                                    value: inputValue,
                                    onChange: inputChangeHandler,
                                }}
                            />
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter>
                            <Button
                                className={classNames("insertMedia-insert", classesInsertMedia.button)}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                disabled={!isInputValid}
                                onClick={buttonClickHandler}
                            >
                                {t("Insert")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </DropDown>
        </>
    );
}
