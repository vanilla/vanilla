/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState } from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import Message from "@library/messages/Message";
import { AttachmentErrorIcon } from "@library/icons/fileTypes";
import { messagesClasses } from "@library/messages/messageStyles";
import Translate from "@library/content/Translate";
import { WarningIcon } from "@library/icons/common";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import SmartLink from "@library/routing/links/SmartLink";

const story = storiesOf("Messages", module);

const shortMessage = `Lorem ipsum dolor sit amet, consectetur adipiscing elit.`;
const message = `Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce blandit lorem ac dui porta, scelerisque placerat felis finibus.`;
const longMessage = `Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce blandit lorem ac dui porta, scelerisque placerat felis finibus. Fusce vitae porttitor augue. Integer sagittis justo vitae nibh aliquet, a viverra ipsum laoreet. Interdum et malesuada fames ac ante ipsum primis in faucibus.
`;

story.add("Message", () => {
    const classesMessages = messagesClasses();
    const [shortMessageFlag, setShortMessageFlag] = useState(true);
    const [messageWithTitleFlag, setMessageWithTitleFlag] = useState(true);
    const [longMessageFlag, setLongMessageFlag] = useState(true);
    const [fixedMessageFlag, setFixedMessageFlag] = useState(true);
    const [iconMessageFlag, setIconMessageFlag] = useState(true);
    const _warningIcon = <WarningIcon className={classNames(classesMessages.messageIcon)} />;
    const _attachmentErrorIcon = (
        <AttachmentErrorIcon className={classNames(classesMessages.messageIcon, classesMessages.errorIcon)} />
    );
    const renderMessage = (val: string, icon: React.ReactNode, setFlag) => {
        return (
            <Message
                contents={
                    <div className={classesMessages.content}>
                        <div>
                            <Translate source={val} />
                        </div>
                    </div>
                }
                onConfirm={() => {
                    setFlag(false);
                }}
                stringContents={t(val)}
            />
        );
    };

    const _messageWithLink = (
        <Message
            contents={
                <div className={classesMessages.content}>
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
            stringContents={t("Lorem ipsum dolor sit amet, consectetur adipiscing elit, visit site.")}
        />
    );
    const _messageWithIcon = iconMessageFlag && (
        <Message
            icon={<WarningIcon className={classNames(classesMessages.messageIcon)} />}
            contents={
                <div className={classesMessages.content}>
                    <div>
                        <Translate source={message} />
                    </div>
                </div>
            }
            onConfirm={() => {
                setIconMessageFlag(false);
            }}
            stringContents={t("Lorem ipsum dolor sit amet, consectetur adipiscing elit, visit site.")}
        />
    );
    const _message_longTitle = messageWithTitleFlag && (
        <Message
            title="How do posts get sent to the Spam & Moderation queues How do posts get sent to the Spam & Moderation queues??"
            // icon={<AttachmentErrorIcon className={classNames(classesMessages.messageIcon)} />}
            contents={
                <div className={classesMessages.content}>
                    <Translate source={message} />
                </div>
            }
            onConfirm={() => {
                setMessageWithTitleFlag(false);
            }}
            confirmText={t("Cancel")}
            stringContents={t(message)}
        />
    );
    const _message_Title_Icon = messageWithTitleFlag && (
        <Message
            title="Vanilla Forums"
            icon={<AttachmentErrorIcon className={classNames(classesMessages.messageIcon)} />}
            contents={
                <div className={classesMessages.content}>
                    <Translate source={message} />
                </div>
            }
            onConfirm={() => {
                setMessageWithTitleFlag(false);
            }}
            confirmText={t("Cancel")}
            stringContents={t(message)}
        />
    );
    const _message_Title_noIcon = messageWithTitleFlag && (
        <Message
            title="Vanilla Forums"
            icon={false}
            contents={
                <div className={classesMessages.content}>
                    <Translate source={message} />
                </div>
            }
            onConfirm={() => {
                setMessageWithTitleFlag(false);
            }}
            confirmText={t("Cancel")}
            stringContents={t(message)}
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
                {shortMessageFlag && renderMessage(shortMessage, null, setShortMessageFlag)}

                <StoryHeading>Long message</StoryHeading>
                {longMessageFlag && renderMessage(longMessage, null, setLongMessageFlag)}

                <StoryHeading>Message with link</StoryHeading>
                {_messageWithLink}

                <StoryHeading>Message with icon</StoryHeading>
                {_messageWithIcon}

                <StoryHeading>Message with long title</StoryHeading>
                {_message_longTitle}

                <StoryHeading>Message with title and icon</StoryHeading>
                {_message_Title_Icon}

                <StoryHeading>Message with title and no icon</StoryHeading>
                {_message_Title_noIcon}

                <div
                    style={{
                        height: 450,
                    }}
                ></div>
            </StoryContent>
        </>
    );
});
