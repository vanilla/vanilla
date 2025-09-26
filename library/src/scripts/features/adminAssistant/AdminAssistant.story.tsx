/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useMockedApi } from "@library/__tests__/utility";
import { AdminAssistant } from "@library/features/adminAssistant/AdminAssistant";
import { ProductMessageFixture } from "@library/features/adminAssistant/ProductMessage.fixture";
import { STORY_IPSUM_MEDIUM } from "@library/storybook/storyData";
import { setMeta } from "@library/utility/appUtils";
import { blessStringAsSanitizedHtml } from "@vanilla/dom-utils";

export default {
    title: "Admin Assistant",
};

setMeta("context.version", "2025.Storybook");

const storyMessages = ProductMessageFixture.mockMessages();

export function Closed() {
    useMockedApi((mock) => {
        mock.onGet("/product-messages").reply(200, storyMessages);
    });
    return (
        <AdminAssistant
            initialState={{
                type: "closed",
            }}
        />
    );
}

export function Open() {
    useMockedApi((mock) => {
        mock.onGet("/product-messages").reply(200, storyMessages);
    });
    return (
        <AdminAssistant
            initialState={{
                type: "root",
            }}
        />
    );
}

export function Messages() {
    useMockedApi((mock) => {
        mock.onGet("/product-messages").reply(200, storyMessages);
    });
    return (
        <AdminAssistant
            initialState={{
                type: "messageInbox",
            }}
        />
    );
}

export function MessageDetails() {
    useMockedApi((mock) => {
        mock.onGet("/product-messages").reply(200, storyMessages);
    });
    return (
        <AdminAssistant
            initialState={{
                type: "messageDetails",
                productMessageID: storyMessages[3].productMessageID,
            }}
        />
    );
}
