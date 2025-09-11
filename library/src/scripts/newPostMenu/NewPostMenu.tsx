/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getMeta } from "@library/utility/appUtils";
import { useSection } from "@library/layout/LayoutContext";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import NewPostMenuDropDown from "@library/newPostMenu/NewPostMenuDropdown";
import { DeepPartial } from "redux";
import type { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { createLoadableComponent } from "@vanilla/react-utils";
import AiFAB from "@library/aiConversations/AiFAB";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";

export enum PostTypes {
    LINK = "link",
    BUTTON = "button",
}
export interface IAddPost {
    id: string;
    action: (() => void) | string;
    type: PostTypes;
    className?: string;
    label: string;
    icon: string;
    asOwnButton?: boolean;
}
export interface INewPostMenuProps {
    title?: string;
    titleType?: string;
    items: IAddPost[];
    borderRadius?: number;
    containerOptions?: DeepPartial<IHomeWidgetContainerOptions>;
    asOwnButtons?: string[];
    excludedButtons?: string[];
    customLabels?: string[];
    forceDesktopOnly?: boolean; //for storybook purposes
    disableDropdownItemsClick?: boolean; //cases when we want dropdown to open but links there don't redirect when clicked
    postableDiscussionTypes?: string[];
}

const NewPostMenuFAB = createLoadableComponent({
    loadFunction: () => import("./NewPostMenuFAB.loadable"),
    fallback: () => null, // No fallback, just don't show the fab until it's loaded.
});

export default function NewPostMenu(props: INewPostMenuProps) {
    const { postableDiscussionTypes, items, ...rest } = props;
    const { hasPermission } = usePermissionsContext();
    const isCompact = !useSection().isFullWidth && !props.forceDesktopOnly;

    const filteredItems =
        postableDiscussionTypes && Array.isArray(postableDiscussionTypes)
            ? items.filter((item) => {
                  return postableDiscussionTypes?.findIndex((type) => item.id.includes(type)) > -1;
              })
            : items;

    if (!filteredItems || !filteredItems.length) {
        return <></>;
    }

    const isAiConversationEnabled = getMeta("featureFlags.aiConversation.Enabled", false);
    const hasAiConversationPermission = hasPermission("aiAssistedSearch.view");
    const isEnabled = isAiConversationEnabled && hasAiConversationPermission;

    const content = isCompact ? (
        <NewPostMenuFAB items={filteredItems} />
    ) : (
        <>
            <NewPostMenuDropDown items={filteredItems} {...rest} />
            {isEnabled && <AiFAB />}
        </>
    );

    return <LayoutWidget>{content}</LayoutWidget>;
}
