/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RecordID } from "@vanilla/utils";
import { PropsWithChildren, createContext, useContext } from "react";

interface SuggestedAnswersContextProps {
    onMutateSuccess?: () => Promise<void>;
    discussionID: RecordID;
    toggleSuggestions?: (isVisible: boolean) => void;
}

const SuggestedAnswersContext = createContext<SuggestedAnswersContextProps>({
    discussionID: -1,
});

export function useSuggestedAnswerContext() {
    return useContext(SuggestedAnswersContext);
}

export function SuggestedAnswersProvider(props: PropsWithChildren<{ value: SuggestedAnswersContextProps }>) {
    const { value, children } = props;

    return <SuggestedAnswersContext.Provider value={value}>{children}</SuggestedAnswersContext.Provider>;
}
