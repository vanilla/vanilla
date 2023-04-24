/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

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
    createIframe: (options: {
        url: string;
        width: HTMLIFrameElement["style"]["width"];
        height: HTMLIFrameElement["style"]["height"];
    }) => void;
    isVisible?: boolean; //for storybook purposes
}

const defaultFrameWidth = "900px";
const defaultFrameHeight = "1600px";

export default function EmbedFlyout(props: IProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const ID = useUniqueID("embedPopover");
    const handleID = ID + "-handle";
    const contentID = ID + "-contents";
    const descriptionID = ID + "-description";

    const [isFrame, setIsFrame] = useState(false);
    const [frameHeight, setFrameHeight] = useState<HTMLIFrameElement["style"]["height"]>(defaultFrameWidth);
    const [frameWidth, setFrameWidth] = useState<HTMLIFrameElement["style"]["width"]>(defaultFrameHeight);
    const [url, setUrl] = useState<string | null>(null);
    const [inputValue, setInputValue] = useState("");
    const isInputValid = !!url;
    const [ownIsVisible, setOwnIsVisible] = useState(false);
    const isVisible = props.isVisible ?? ownIsVisible;

    const clearState = () => {
        setIsFrame(false);
        setFrameHeight(defaultFrameWidth);
        setFrameWidth(defaultFrameHeight);
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
        setOwnIsVisible(false);

        if (isFrame) {
            props.createIframe({ url, width: frameWidth, height: frameHeight });
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
            const height = iframe.getAttribute("height") ?? defaultFrameWidth;
            const width = iframe.getAttribute("width") ?? defaultFrameHeight;
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
        //only if value changed, avoid other triggers
        if (value !== inputValue) {
            setInputValue(value);
            parseInput(value);
        }
    };

    const classesInsertMedia = insertMediaClasses();

    function handleVisibilityChange(newVisibility: boolean) {
        if (newVisibility) {
            setOwnIsVisible(true);
            inputRef.current && inputRef.current.focus();
        } else {
            setOwnIsVisible(false);
        }
    }

    const title = t("Insert Media");

    const labelNote = `${t("Paste the URL of the media you want.")}${
        supportsFrames() ? t("You can also use an <iframe /> embed code here.") : ""
    }`;

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
                isVisible={isVisible}
                preventFocusOnVisible
            >
                <Frame
                    body={
                        <FrameBody>
                            <InputTextBlock
                                className={classNames(classesInsertMedia.insert)}
                                descriptionID={descriptionID}
                                multiLineProps={{
                                    rows: 1,
                                    maxRows: 5,
                                }}
                                inputProps={{
                                    "aria-label": title,
                                    "aria-describedby": descriptionID,
                                    onKeyPress: buttonKeyDownHandler,
                                    inputRef,
                                    multiline: supportsFrames(),
                                    value: inputValue,
                                    onChange: inputChangeHandler,
                                }}
                                label={t("URL")}
                                labelNote={labelNote}
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
