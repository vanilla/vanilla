/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Vanilla
 * @license gpl-2.0-only
 */

import type { IDiscussion } from "@dashboard/@types/api/discussion";
import DiscussionBookmarkToggle from "@library/features/discussions/DiscussionBookmarkToggle";
import type { IDiscussionItemOptions } from "@library/features/discussions/DiscussionList.variables";
import { DiscussionListItemMeta } from "@library/features/discussions/DiscussionListItemMeta";
import DiscussionOptionsMenu from "@library/features/discussions/DiscussionOptionsMenu";
import CheckBox from "@library/forms/Checkbox";
import { Metas } from "@library/metas/Metas";
import { ToolTip } from "@library/toolTip/ToolTip";
import { usePostItemFragmentContext } from "@library/widget-fragments/PostItemFragment.context";
import { useQueryClient } from "@tanstack/react-query";
import DiscussionVoteCounter from "@library/features/discussions/DiscussionVoteCounter";
import React from "react";

function Meta(props: { className?: string; extraBefore?: React.ReactNode; extraAfter?: React.ReactNode }) {
    const { discussion, options } = usePostItemFragmentContext();

    return (
        <Metas className={props.className}>
            {props.extraBefore}
            <DiscussionListItemMeta {...discussion} discussionOptions={options} />
            {props.extraAfter}
        </Metas>
    );
}

function OptionsMenu(props: { className?: string }) {
    const queryClient = useQueryClient();
    const { discussion } = usePostItemFragmentContext();

    return (
        <DiscussionOptionsMenu
            discussion={discussion}
            onMutateSuccess={async () => {
                await queryClient.invalidateQueries({ queryKey: ["discussion"] });
            }}
        />
    );
}

function BookmarkToggle(props: { className?: string }) {
    const queryClient = useQueryClient();
    const { discussion } = usePostItemFragmentContext();

    return (
        <DiscussionBookmarkToggle
            discussion={discussion}
            onSuccess={async () => {
                await queryClient.invalidateQueries({ queryKey: ["discussion"] });
            }}
            classNames={props.className}
        />
    );
}

function BulkActionCheckbox(props: { className?: string }) {
    const { className } = props;
    const { discussion, isCheckDisabled, isChecked, checkDisabledReason, onCheckboxChange, showCheckbox } =
        usePostItemFragmentContext();

    if (!showCheckbox) {
        return <></>;
    }

    let result = (
        <span className={className}>
            <CheckBox
                className={!checkDisabledReason ? className : undefined}
                checked={isChecked}
                label={`Select ${discussion.name}`}
                hideLabel={true}
                disabled={isCheckDisabled}
                onChange={(e) => {
                    onCheckboxChange(e.target.checked);
                }}
            />
        </span>
    );

    if (checkDisabledReason) {
        result = <ToolTip label={checkDisabledReason}>{result}</ToolTip>;
    }

    return result;
}

function VoteCounter(props: { className?: string; direction: "vertical" | "horizontal" }) {
    const { discussion } = usePostItemFragmentContext();

    return <DiscussionVoteCounter discussion={discussion} className={props.className} direction={props.direction} />;
}

const PostItemFragmentInjectable = {
    Meta,
    OptionsMenu,
    BookmarkToggle,
    BulkActionCheckbox,
    VoteCounter,
};
namespace PostItemFragmentInjectable {
    export interface Props {
        discussion: IDiscussion;
        options: IDiscussionItemOptions;
        isChecked?: boolean;
    }
}

export default PostItemFragmentInjectable;
