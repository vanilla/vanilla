/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState } from "react";
import classNames from "classnames";
import { Icon } from "@vanilla/icons";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { newPostMenuFABClasses } from "@library/newPostMenu/NewPostMenuFAB.styles";
import { AiConversationsApiProvider } from "@library/aiConversations/AiConversations.context";
import { AiChatInterfaceModal } from "@library/aiConversations/AiChatInterface";
import { aiChatStyles } from "@library/aiConversations/AiChatInterface.styles";

export default function AiFAB() {
    const [isModalVisible, setIsModalVisible] = useState(false);

    return (
        <AiConversationsApiProvider>
            <AiChatInterfaceModal isVisible={isModalVisible} onClose={() => setIsModalVisible(false)} />

            <div className={classNames(newPostMenuFABClasses().root)}>
                <Button
                    buttonType={ButtonTypes.CUSTOM}
                    className={classNames(newPostMenuFABClasses().fab, aiChatStyles().fab)}
                    onClick={() => setIsModalVisible(true)}
                >
                    <Icon icon="ai-indicator" />
                </Button>
            </div>
        </AiConversationsApiProvider>
    );
}
