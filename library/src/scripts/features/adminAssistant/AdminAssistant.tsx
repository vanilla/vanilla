/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Translate from "@library/content/Translate";
import { ProductMessageDetails } from "@library/features/adminAssistant/ProductMessageDetails";
import { ProductMessagesApi } from "@library/features/adminAssistant/ProductMessages.api";
import { ProductMessagesList } from "@library/features/adminAssistant/ProductMessagesList";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import FlexSpacer from "@library/layout/FlexSpacer";
import Notice from "@library/metas/Notice";
import { Icon } from "@vanilla/icons";
import { useSizeAnimator } from "@vanilla/react-utils";
import DropDownItemMetas from "@library/flyouts/items/DropDownItemMetas";
import { Row } from "@library/layout/Row";
import { Tag } from "@library/metas/Tags";
import { getMeta, t } from "@library/utility/appUtils";
import { CoreErrorMessages, DefaultError } from "@library/errorPages/CoreErrorMessages";
import {
    useAdminAssistantState,
    type AdminAssistantState,
} from "@library/features/adminAssistant/AdminAssistant.state";
import { AdminAssistantClasses as classes } from "@library/features/adminAssistant/AdminAssistant.classes";
import { MessageBox } from "@library/messageBox/messageBox";

interface IProps {
    initialState?: AdminAssistantState;
}

export function AdminAssistant(props: IProps) {
    const messagesQuery = ProductMessagesApi.useListMessagesQuery();
    const countNew = messagesQuery.data?.filter((message) => !message.isDismissed).length ?? 0;
    const animator = useSizeAnimator();

    const [displayState, setDisplayState] = useAdminAssistantState({
        initialState: props.initialState,
        messagesQuery,
    });

    // Handle state transitions with animations
    const changeDisplayState = (newState: AdminAssistantState) => {
        animator.runWithTransition(() => {
            setDisplayState(newState);
        });
    };

    const rootContents = (
        <>
            <DropDownItemMetas>
                <Row align={"center"}>
                    <Icon width={60} className={classes.buttonIcon} icon="vanilla-logo" />
                    <Tag className={classes.versionTag} preset={"greyscale"}>
                        {getMeta("context.version", "Unknown version")}
                    </Tag>
                </Row>
            </DropDownItemMetas>
            <DropDownItemSeparator />
            <DropDownItemButton
                onClick={() => {
                    changeDisplayState({ type: "messageInbox" });
                }}
            >
                <Icon icon={"admin-messages"} className={classes.buttonIcon} /> {t("Vanilla Messages")}
                <FlexSpacer actualSpacer />
                {countNew > 0 && (
                    <Notice>
                        <Translate source={"%s new"} c0={countNew} />
                    </Notice>
                )}
            </DropDownItemButton>
            <DropDownItemSeparator />
            <DropDownItemLink to="https://success.vanillaforums.com">
                <Icon icon={"admin-community"} className={classes.buttonIcon} /> {t("Success Community")}
            </DropDownItemLink>
            <DropDownItemLink to="https://success.vanillaforums.com/kb">
                <Icon icon={"admin-docs"} className={classes.buttonIcon} /> {t("Documentation")}
            </DropDownItemLink>
            <DropDownItemLink to="https://success.vanillaforums.com/events/category">
                <Icon icon={"admin-events"} className={classes.buttonIcon} /> {t("Upcoming Events")}
            </DropDownItemLink>
            <DropDownItemLink to="https://success.vanillaforums.com/categories/product-ideas">
                <Icon icon={"admin-ideas"} className={classes.buttonIcon} /> {t("Product Ideas")}
            </DropDownItemLink>
        </>
    );

    const messageInboxContents = (
        <ProductMessagesList
            onMessageClick={(message) => {
                changeDisplayState({
                    type: "messageDetails",
                    productMessageID: message.productMessageID,
                });
            }}
            onClose={() => {
                changeDisplayState({ type: "closed" });
            }}
        />
    );

    function MessageDetailsContents() {
        const message = messagesQuery.data?.find(
            (message) =>
                message.productMessageID ===
                (displayState as { type: "messageDetails"; productMessageID: string })?.productMessageID,
        );

        if (!message) {
            <CoreErrorMessages defaultError={DefaultError.NOT_FOUND} />;
            return null;
        }
        return (
            <ProductMessageDetails
                message={message}
                onClose={() => {
                    changeDisplayState({ type: "closed" });
                }}
                onBack={() => {
                    changeDisplayState({ type: "messageInbox" });
                }}
            />
        );
    }

    return (
        <MessageBox
            displayState={displayState}
            rootContents={rootContents}
            messageInboxContents={messageInboxContents}
            messageDetailsContents={<MessageDetailsContents />}
            dropDownTargetState={"root"}
            changeDisplayState={changeDisplayState}
            animator={animator}
        />
    );
}
