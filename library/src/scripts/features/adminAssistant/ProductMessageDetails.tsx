/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import DateTime from "@library/content/DateTime";
import UserContent from "@library/content/UserContent";
import { AdminAssistantClasses } from "@library/features/adminAssistant/AdminAssistant.classes";
import { ProductMessageItem } from "@library/features/adminAssistant/ProductMessageItem";
import { ProductMessagesApi } from "@library/features/adminAssistant/ProductMessages.api";
import { ProductMessagesHeader } from "@library/features/adminAssistant/ProductMessagesHeader";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { Row } from "@library/layout/Row";
import { Metas, MetaLink, MetaIcon, MetaItem } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import { FramedModal } from "@library/modal/FramedModal";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useEffect } from "react";

interface IProps {
    message: ProductMessagesApi.Message;
    onClose: () => void;
    onBack?: () => void;
}

export function ProductMessageDetails(props: IProps) {
    const { message } = props;

    const dismissMutation = ProductMessagesApi.useDismissMutation(message.productMessageID);

    useEffect(() => {
        // Mark the message as read when the modal is opened
        if (!message.isDismissed) {
            dismissMutation.mutate();
        }
    }, []);

    const actions: React.ReactNode[] = [];
    if (message.foreignUrl && message.productMessageType === "announcement") {
        actions.push(
            <LinkAsButton to={message.foreignUrl} buttonType={"text"} target={"_blank"}>
                {t("View in Success Community")}
            </LinkAsButton>,
        );
    }

    if (message.ctaLabel && message.ctaUrl) {
        actions.push(
            <LinkAsButton to={message.ctaUrl} buttonType={"textPrimary"}>
                {t(message.ctaLabel)}
            </LinkAsButton>,
        );
    }
    return (
        <Frame
            header={
                <ProductMessagesHeader
                    onBack={props.onBack}
                    onClose={props.onClose}
                    title={<ProductMessageItem message={message} />}
                />
            }
            bodyWrapClass={AdminAssistantClasses.frameBody}
            body={
                <FrameBody hasVerticalPadding={true}>
                    <UserContent vanillaSanitizedHtml={message.body} />
                </FrameBody>
            }
            footer={actions.length > 0 && <FrameFooter justifyRight>{actions}</FrameFooter>}
        ></Frame>
    );
}
