import { IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { MyMentionElement, MyValue } from "@library/vanilla-editor/typescript";
import { useQuery } from "@tanstack/react-query";
import { PlateRenderElementProps, getHandler, withoutNormalizing, withoutSavingHistory } from "@udecode/plate-common";
import { useEffect, useState } from "react";
import { Node, Path } from "slate";
import { getSiteSection } from "@library/utility/appUtils";

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
            const siteSectionID = getSiteSection().sectionID;
            const response = await apiv2.get<IUserFragment[]>("/users/by-names", {
                params: { name, siteSectionID },
            });
            return response.data;
        },
        enabled: !element.userID,
        refetchOnMount: false,
    });

    function hasId(node: any): node is { id: string } {
        return typeof node.id === "string";
    }

    function findNodePathById(editor, id: string): Path | undefined {
        for (const [node, path] of Node.nodes(editor)) {
            if (hasId(node) && node.id === id) {
                return path;
            }
        }
        return undefined;
    }
    const [shouldUpdateMention, setShouldUpdateMention] = useState(false);

    useEffect(() => {
        if (!element.userID && data) {
            setShouldUpdateMention(true);
        }
    }, [data, element.userID]);

    useEffect(() => {
        if (shouldUpdateMention) {
            // get the path of the current node
            const path = typeof element.id === "string" ? findNodePathById(editor, element.id) : undefined;

            if (path && data) {
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
            setShouldUpdateMention(false);
        }
    }, [shouldUpdateMention]);

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
