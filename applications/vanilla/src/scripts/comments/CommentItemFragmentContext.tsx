/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PropsWithChildren, createContext, useContext, useState } from "react";

import { ICommentFragmentImplProps } from "@vanilla/addon-vanilla/comments/CommentItem";

const CommentFragmentContext = createContext<ICommentFragmentImplProps>({} as ICommentFragmentImplProps);

/**
 * This context will provide all the comment props editing state
 * so that the comment item injectables can be used without requiring
 * the props to be passed down from the parent component.
 */
export function useCommentItemFragmentContext() {
    return useContext(CommentFragmentContext);
}

export function CommentItemFragmentContextProvider(props: PropsWithChildren<Partial<ICommentFragmentImplProps>>) {
    const [isEditing, setIsEditing] = useState(false);
    const { children, ...rest } = props;

    return (
        <CommentFragmentContext.Provider
            value={
                {
                    ...rest,
                    isEditing,
                    setIsEditing,
                } as ICommentFragmentImplProps
            }
        >
            {props.children}
        </CommentFragmentContext.Provider>
    );
}
