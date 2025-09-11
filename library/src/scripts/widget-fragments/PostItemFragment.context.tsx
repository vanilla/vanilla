/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Vanilla
 * @license gpl-2.0-only
 */

import type { IDiscussion } from "@dashboard/@types/api/discussion";
import type { IDiscussionItemOptions } from "@library/features/discussions/DiscussionList.variables";
import { createContext, useContext } from "react";

export interface IPostItemFragmentContext {
    discussion: IDiscussion;
    options: IDiscussionItemOptions;
    isChecked: boolean;
    showCheckbox: boolean;
    onCheckboxChange: (isChecked: boolean) => void;
    checkDisabledReason?: string;
    isCheckDisabled: boolean;
}

export const PostItemFragmentContext = createContext<IPostItemFragmentContext>({} as any);

export function usePostItemFragmentContext() {
    const value = useContext(PostItemFragmentContext);
    if (Object.keys(value).length === 0) {
        throw new Error("usePostItemFragmentContext() must be used within a PostItemFragmentContext provider");
    }
    return value;
}
