/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useEffect, useState } from "react";
import { StoryContent } from "@library/storybook/StoryContent";
//import { LinkEmbed } from "@library/embeddedContent/LinkEmbed";
import Message from "@library/messages/Message";
import { AttachmentErrorIcon } from "@library/icons/fileTypes";
import { messagesClasses } from "@library/messages/messageStyles";
import Translate from "@library/content/Translate";
import { WarningIcon } from "@library/icons/common";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import SmartLink from "@library/routing/links/SmartLink";

const story = storiesOf("Messages", module);

const shortMessage = `
Lorem ipsum dolor sit amet, consectetur adipiscing elit.
`;

const message = `Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce blandit lorem ac dui porta, scelerisque placerat felis finibus.`;
const longMessage = `
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce blandit lorem ac dui porta, scelerisque placerat felis finibus. Fusce vitae porttitor augue. Integer sagittis justo vitae nibh aliquet, a viverra ipsum laoreet. Interdum et malesuada fames ac ante ipsum primis in faucibus.
`;

story.add("Message", () => {
    const classesMessages = messagesClasses();
    const [shortMessageFlag, setShortMessageFlag] = useState(true);
    const [longMessageFlag, setLongMessageFlag] = useState(true);
    const [fixedMessageFlag, setFixedMessageFlag] = useState(true);
    const _shortMessage = shortMessageFlag && (
        <Message
            contents={
                <div className={classesMessages.content}>
                    <AttachmentErrorIcon
                        className={classNames(classesMessages.messageIcon, classesMessages.errorIcon)}
                    />
                    <div>
                        <Translate source={shortMessage} />
                    </div>
                </div>
            }
            onConfirm={() => {
                setShortMessageFlag(false);
            }}
            stringContents={t(shortMessage)}
        />
    );
    const _longMessage = longMessageFlag && (
        <Message
            contents={
                <div className={classesMessages.content}>
                    <WarningIcon className={classNames(classesMessages.messageIcon)} />
                    <div>
                        <Translate source={longMessage} />
                    </div>
                </div>
            }
            onConfirm={() => {
                setLongMessageFlag(false);
            }}
            confirmText={t("Cancel")}
            stringContents={t(longMessage)}
        />
    );

    const _messageWithLink = (
        <Message
            contents={
                <div className={classesMessages.content}>
                    <WarningIcon className={classNames(classesMessages.messageIcon)} />
                    <div>
                        <Translate
                            source="Lorem ipsum dolor sit amet, consectetur adipiscing elit, <0>visit site</0>."
                            c0={content => <SmartLink to="http://www.google.com">{content}</SmartLink>}
                        />
                    </div>
                </div>
            }
            onConfirm={() => {
                setLongMessageFlag(false);
            }}
            //confirmText={t("Cancel")}
            stringContents={t("Lorem ipsum dolor sit amet, consectetur adipiscing elit, visit site.")}
        />
    );
    const _fixedMessage = fixedMessageFlag && (
        <Message
            isFixed={true}
            contents={
                <div className={classesMessages.content}>
                    <AttachmentErrorIcon
                        className={classNames(classesMessages.messageIcon, classesMessages.errorIcon)}
                    />
                    <div>
                        <Translate source={message} />
                    </div>
                </div>
            }
            onConfirm={() => {
                setFixedMessageFlag(false);
            }}
            stringContents={t(message)}
        />
    );
    return (
        <>
            {_fixedMessage}
            <StoryContent>
                <div
                    style={{
                        height: 50,
                    }}
                ></div>
                <StoryHeading>Short message</StoryHeading>
                {_shortMessage}
                <StoryHeading>Long message</StoryHeading>
                {_longMessage}
                <StoryHeading>Message with link</StoryHeading>
                {_messageWithLink}
                <div
                    style={{
                        height: 450,
                    }}
                ></div>
            </StoryContent>
        </>
    );
});
