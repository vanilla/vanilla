/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { insertAt, RecordID, removeAt } from "@vanilla/utils";
import { useLayoutJsonDraft, useLayoutJsonDraftActions } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";

export const layoutEditorContextProvider = React.createContext({
    layoutID: -1 as RecordID,
    isEditMode: false,
    addSection: (i: number, sectionKey: string) => {},
    deleteSection: (i: number) => {},
    addWidgetHandler: () => {},
});

interface IProps {
    layoutID: RecordID;
    isEditMode: boolean;
    addWidgetHandler?: () => void;
    children?: React.ReactNode;
}

export function LayoutEditorContextProvider(props: IProps) {
    const { layoutID, children, isEditMode = true, addWidgetHandler = () => {} } = props;

    const draft = useLayoutJsonDraft(layoutID, layoutID);
    const { update } = useLayoutJsonDraftActions(draft!.data!.layoutID);

    const addSection = (nodeIndex: number, sectionKey: string) => {
        if (draft.data) {
            const section = {
                $hydrate: sectionKey,
            };
            const updatedLayout = insertAt(draft.data!.layout, section, nodeIndex);

            update({
                ...draft.data,
                layout: updatedLayout,
            });
        }
    };

    const deleteSection = (nodeIndex: number) => {
        if (draft.data) {
            const updatedLayout = removeAt(draft.data!.layout, nodeIndex);

            update({
                ...draft.data,
                layout: updatedLayout,
            });
        }
    };

    return (
        <layoutEditorContextProvider.Provider
            value={{
                layoutID: layoutID,
                isEditMode: isEditMode,
                addSection,
                deleteSection,
                addWidgetHandler,
            }}
        >
            {children}
        </layoutEditorContextProvider.Provider>
    );
}
