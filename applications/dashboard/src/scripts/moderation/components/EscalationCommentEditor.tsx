/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useEscalationCommentMutation } from "@dashboard/moderation/CommunityManagement.hooks";
import { IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import { MyValue } from "@library/vanilla-editor/typescript";
import { EMPTY_DRAFT } from "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset";
import { NewCommentEditor } from "@vanilla/addon-vanilla/thread/components/NewCommentEditor";
import { useState } from "react";

interface IProps {
    escalationID: IEscalation["escalationID"];
}

export function EscalationCommentEditor(props: IProps) {
    const [editorKey, setEditorKey] = useState(0);
    const [value, setValue] = useState<MyValue | undefined>();

    const post = useEscalationCommentMutation(props.escalationID);

    const resetState = () => {
        setValue(EMPTY_DRAFT);
        setEditorKey((existing) => existing + 1);
    };

    return (
        <NewCommentEditor
            title={<></>}
            editorKey={editorKey}
            value={value}
            onValueChange={setValue}
            onPublish={async (value) => {
                await post.mutateAsync(JSON.stringify(value));
                await resetState();
            }}
            publishLoading={post.isLoading}
        />
    );
}
