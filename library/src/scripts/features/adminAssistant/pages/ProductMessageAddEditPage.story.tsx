/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ProductMessageFixture } from "@library/features/adminAssistant/ProductMessage.fixture";
import ProductMessageAddEditPage from "@library/features/adminAssistant/pages/ProductMessageAddEditPage";
import { AdminPageStory } from "@library/storybook/StoryContext";
import { MemoryRouter } from "react-router";

export default {
    title: "Product Messages",
};

export function AddPage() {
    return (
        <AdminPageStory>
            <ProductMessageAddEditPage />
        </AdminPageStory>
    );
}

export function EditPage() {
    return (
        <AdminPageStory
            apiMock={(mock) => {
                mock.onGet("/product-messages/foreign-users").reply(200, [
                    {
                        userID: 5,
                        name: "Adam Charron",
                    },
                    {
                        userID: 6,
                        name: "Other User",
                    },
                ]);
                mock.onGet("/product-messages/test-message/edit").reply(
                    200,
                    ProductMessageFixture.messageEdit({
                        foreignInsertUserID: 5,
                    }),
                );
            }}
        >
            <ProductMessageAddEditPage productMessageID={"test-message"} />
        </AdminPageStory>
    );
}

export function EditPageNotFound() {
    return (
        <AdminPageStory>
            <ProductMessageAddEditPage productMessageID={"test-message"} />
        </AdminPageStory>
    );
}
