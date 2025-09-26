/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ProductMessageFixture } from "@library/features/adminAssistant/ProductMessage.fixture";
import ProductMessagesListPage from "@library/features/adminAssistant/pages/ProductMessagesListPage";
import { AdminPageStory } from "@library/storybook/StoryContext";

export default {
    title: "Product Messages",
};

export function ListPage() {
    return (
        <AdminPageStory
            apiMock={(mock) => {
                mock.onGet("/product-messages").reply(200, ProductMessageFixture.mockMessages());
            }}
        >
            <ProductMessagesListPage />
        </AdminPageStory>
    );
}
