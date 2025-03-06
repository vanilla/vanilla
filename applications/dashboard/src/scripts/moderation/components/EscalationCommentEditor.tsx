/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useEscalationCommentMutation } from "@dashboard/moderation/CommunityManagement.hooks";
import { IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import { MyValue } from "@library/vanilla-editor/typescript";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { CommentEditor } from "@vanilla/addon-vanilla/comments/CommentEditor";
import { useState } from "react";

interface IProps {
    escalationID: IEscalation["escalationID"];
}

export function EscalationCommentEditor(props: IProps) {
    const [editorKey, setEditorKey] = useState(0);
    const [value, setValue] = useState<MyValue | undefined>();

    const post = useEscalationCommentMutation(props.escalationID);

    const resetState = () => {
        setValue(EMPTY_RICH2_BODY);
        setEditorKey((existing) => existing + 1);
    };

    return (
        <CommentEditor
            title={<></>}
            editorKey={editorKey}
            value={value}
            onValueChange={setValue}
            format={"rich2"}
            onPublish={async (value) => {
                await post.mutateAsync(JSON.stringify(value));
                await resetState();
            }}
            publishLoading={post.isLoading}
            autoFocus
        />
    );
}
