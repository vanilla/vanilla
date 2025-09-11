/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import Translate from "@library/content/Translate";
import { AdminAssistantClasses } from "@library/features/adminAssistant/AdminAssistant.classes";
import { ProductMessageItem } from "@library/features/adminAssistant/ProductMessageItem";
import { ProductMessagesApi } from "@library/features/adminAssistant/ProductMessages.api";
import { ProductMessagesHeader } from "@library/features/adminAssistant/ProductMessagesHeader";
import Button from "@library/forms/Button";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { QueryLoader } from "@library/loaders/QueryLoader";
import Notice from "@library/metas/Notice";
import { t } from "@vanilla/i18n";

export function ProductMessagesList(props: {
    onBack?: () => void;
    onClose: () => void;
    onMessageClick: (message: ProductMessagesApi.Message) => void;
}) {
    const messagesQuery = ProductMessagesApi.useListMessagesQuery();
    const dismissAllMutation = ProductMessagesApi.useDismissAllMutation();
    const countNew = messagesQuery.data?.filter((message) => !message.isDismissed).length ?? 0;

    return (
        <Frame
            header={
                <ProductMessagesHeader
                    onClose={props.onClose}
                    title={
                        <div>
                            <div>{t("Vanilla Messages")}</div>
                            <div>
                                {countNew > 0 && (
                                    <Notice>
                                        <Translate source={"%s new"} c0={countNew} />
                                    </Notice>
                                )}
                            </div>
                        </div>
                    }
                />
            }
            footer={
                <FrameFooter justifyRight>
                    <Button
                        onClick={() => {
                            dismissAllMutation.mutate();
                        }}
                        buttonType={"text"}
                        mutation={dismissAllMutation}
                    >
                        {t("Mark All Read")}
                    </Button>
                </FrameFooter>
            }
            bodyWrapClass={AdminAssistantClasses.frameBody}
            body={
                <FrameBody>
                    <QueryLoader
                        query={messagesQuery}
                        success={(messages) => {
                            return (
                                <>
                                    {messages.length === 0 && (
                                        <PageHeadingBox
                                            classNames={css({ marginTop: 16 })}
                                            title={"All Clear!"}
                                            description={"There are no messages for you right now."}
                                        />
                                    )}
                                    {messages.map((message) => {
                                        return (
                                            <ProductMessageItem
                                                onClick={() => {
                                                    props.onMessageClick(message);
                                                }}
                                                key={message.productMessageID}
                                                message={message}
                                            />
                                        );
                                    })}
                                </>
                            );
                        }}
                    />
                </FrameBody>
            }
        />
    );
}
