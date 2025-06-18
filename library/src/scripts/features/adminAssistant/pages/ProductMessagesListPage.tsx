/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { StaffAdminLayout } from "@dashboard/components/navigation/StaffAdminLayout";
import { ProductMessagesAddEditRoute } from "@dashboard/developer/getVanillaStaffRoutes";
import { EmptyState } from "@dashboard/moderation/components/EmptyState";
import { css } from "@emotion/css";
import { ProductMessageItem } from "@library/features/adminAssistant/ProductMessageItem";
import { ProductMessagesApi } from "@library/features/adminAssistant/ProductMessages.api";
import DropDown from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import Button from "@library/forms/Button";
import { PageBox } from "@library/layout/PageBox";
import { Row } from "@library/layout/Row";
import { QueryLoader } from "@library/loaders/QueryLoader";
import ModalConfirm from "@library/modal/ModalConfirm";
import LinkAsButton from "@library/routing/LinkAsButton";
import SmartLink from "@library/routing/links/SmartLink";
import { useState } from "react";

export default function ProductMessagesListPage() {
    const messagesQuery = ProductMessagesApi.useListMessagesQuery();

    return (
        <StaffAdminLayout
            rightPanel={
                <>
                    <h3>What are product messages?</h3>
                    <p>
                        Messages are a way to communicate important updates, changes, or announcements to our customers.
                        Use them to share news about new features, bug fixes, or other relevant product information.
                    </p>
                    <p>
                        To send a message to multiple customers, create it in{" "}
                        <SmartLink to="https://success.vanillaforums.com/categories/updates">
                            the Success Community
                        </SmartLink>{" "}
                        and apply filters to target specific sites.
                    </p>
                    <p>Messages created here will only be visible to this site&apos;s administrators.</p>
                </>
            }
            title={"Manage Product Messages"}
            titleBarActions={<LinkAsButton to={ProductMessagesAddEditRoute.url(undefined)}>Add Message</LinkAsButton>}
            content={
                <div>
                    <QueryLoader
                        query={messagesQuery}
                        success={(messages) => {
                            return (
                                <div className={classes.items}>
                                    {messages.length === 0 && <EmptyState />}
                                    {messages.map((message) => {
                                        return <ProductMessageRow message={message} key={message.productMessageID} />;
                                    })}
                                </div>
                            );
                        }}
                    />
                </div>
            }
        />
    );
}

function ProductMessageRow(props: { message: ProductMessagesApi.Message }) {
    const { message } = props;
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const deleteMutation = ProductMessagesApi.useDeleteMutation(message.productMessageID);

    return (
        <PageBox options={{ borderType: "border" }} key={message.productMessageID}>
            <Row align={"center"} gap={16} justify="space-between">
                <ProductMessageItem message={message} staffView={true} />
                <DropDown>
                    {message.productMessageType === "announcement" ? (
                        <>
                            <DropDownItemLink to={message.foreignUrl!}>View in Success Community</DropDownItemLink>
                        </>
                    ) : (
                        <>
                            <DropDownItemLink to={ProductMessagesAddEditRoute.url(message.productMessageID)}>
                                Edit
                            </DropDownItemLink>
                            <DropDownItemButton
                                onClick={() => {
                                    setShowDeleteConfirm(true);
                                }}
                            >
                                Delete
                            </DropDownItemButton>
                        </>
                    )}
                </DropDown>
            </Row>
            <ModalConfirm
                confirmTitle={"Delete"}
                isVisible={showDeleteConfirm}
                title={"Delete Product Message"}
                onCancel={() => setShowDeleteConfirm(false)}
                onConfirm={() => {
                    deleteMutation.mutate();
                }}
                isConfirmLoading={deleteMutation.isLoading}
                isConfirmDisabled={deleteMutation.isLoading}
            >
                <p>Are you sure you want to delete this message?</p>
            </ModalConfirm>
        </PageBox>
    );
}

const classes = {
    items: css({
        padding: 16,
    }),
};
