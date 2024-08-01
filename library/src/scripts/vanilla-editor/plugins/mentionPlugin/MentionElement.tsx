import { IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { MyMentionElement, MyValue } from "@library/vanilla-editor/typescript";
import { useQuery } from "@tanstack/react-query";
import {
    PlateRenderElementProps,
    findNodePath,
    getHandler,
    withoutNormalizing,
    withoutSavingHistory,
} from "@udecode/plate-common";
import { useEffect } from "react";

export interface MentionElementProps extends PlateRenderElementProps<MyValue, MyMentionElement> {
    prefix?: string;
    onClick?: (mentionNode: any) => void;
}

export const MentionElement = (props: MentionElementProps) => {
    const { attributes, nodeProps, element, prefix, onClick, children, editor } = props;

    const href = props.element.url;
    const userName = props.element.name;

    // the mention does not have an ID so it was likely from a paste, find the user info
    const { data } = useQuery<IUserFragment[]>({
        queryKey: ["searchUser", userName],
        queryFn: async ({ queryKey }) => {
            const [_, name] = queryKey;
            const response = await apiv2.get<IUserFragment[]>("/users/by-names", { params: { name } });
            return response.data;
        },
        enabled: !element.userID,
        refetchOnMount: false,
    });

    useEffect(() => {
        // the mention does have an ID so we need to see if it's an actual user
        if (!element.userID && data) {
            // get the path of the current node
            const path = findNodePath(editor, element);
            // If the user name matches, there will be only one result and so we need to update with user info
            if (data.length === 1) {
                withoutSavingHistory(editor, () => {
                    editor.setNodes({ ...element, ...data[0] }, { at: path });
                });
            }
            // the user name does not match, let's replace the node with a text node
            else {
                withoutSavingHistory(editor, () => {
                    editor.insertNodes({ text: prefix + userName }, { at: path });
                });
                withoutNormalizing(editor, () => {
                    editor.unsetNodes("type", { at: path });
                });
            }
        }
    }, [data, element]);

    return (
        <a {...attributes} {...nodeProps} href={href} className="atMention">
            <span contentEditable={false} onClick={getHandler(onClick, element)}>
                {prefix}
                {userName}
            </span>
            {children}
        </a>
    );
};
