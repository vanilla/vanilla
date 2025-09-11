/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AiConversationsApiProvider } from "@library/aiConversations/AiConversations.context";
import { AiChatInterfaceMessageBox } from "@library/aiConversations/AiChatInterface";
import ReactDOM from "react-dom";

export default function AiFAB() {
    const portalContainer = document.getElementById("portals") || document.body;
    return ReactDOM.createPortal(
        <AiConversationsApiProvider>
            <AiChatInterfaceMessageBox />
        </AiConversationsApiProvider>,
        portalContainer,
    );
}
