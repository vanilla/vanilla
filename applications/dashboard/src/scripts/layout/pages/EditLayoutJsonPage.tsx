/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ILayout, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { usePlaygroundSetup, usePlaygroundSpec } from "@dashboard/layout/hooks/layoutHooks";
import { css } from "@emotion/css";
import { extractServerError } from "@library/apiv2";
import { initCodeHighlighting } from "@library/content/code";
import UserContent from "@library/content/UserContent";
import { FormToggle } from "@library/forms/FormToggle";
import { ActionBar } from "@library/headers/ActionBar";
import { ErrorIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import TextEditor from "@library/textEditor/TextEditor";
import { getRelativeUrl, getSiteSection, siteUrl, t } from "@library/utility/appUtils";
import { escapeHTML } from "@vanilla/dom-utils";
import { useAsyncFn, useLastValue } from "@vanilla/react-utils";
import { useApiContext } from "@vanilla/ui";
import React, { useEffect, useMemo, useState } from "react";
import { RouteComponentProps } from "react-router-dom";
import AdminEditTitleBar from "@dashboard/components/AdminEditTitleBar";
import { LayoutOverviewRoute, LegacyLayoutsRoute } from "@dashboard/appearance/routes/pageRoutes";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { useLayoutJsonDraft, useLayoutJsonDraftActions } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { PageLoadStatus } from "@library/loaders/PageLoadStatus";

export default function EditLayoutJsonPage(
    props: RouteComponentProps<{
        layoutViewType: LayoutViewType;
        layoutID?: string;
    }> & {
        draftID?: ILayout["layoutID"];
    },
) {
    const { layoutViewType = "home", layoutID } = props.match.params;
    const { history } = props;
    const isNewDraft = !!props.draftID;
    const draftID = (props.draftID ?? layoutID)!;
    const draftLoadable = useLayoutJsonDraft(props.draftID, props.match.params.layoutID, layoutViewType);
    const draft = draftLoadable.data;

    const { save: saveDraft, update: updateDraft } = useLayoutJsonDraftActions(draftID);

    const device = useTitleBarDevice();
    const isCompact = device === TitleBarDevices.COMPACT;

    useEffect(() => {
        initCodeHighlighting();
    }, [draft]);

    const [isSaving, setIsSaving] = useState(false);
    async function handleSave() {
        setIsSaving(true);
        const layout = await saveDraft();
        setIsSaving(false);
        history.push(getRelativeUrl(LayoutOverviewRoute.url({ ...layout, layoutViewType })));
    }

    const jsonString = draft
        ? JSON.stringify({ ...draft, layoutID: undefined, name: undefined, layoutViewType: undefined }, null, 4)
        : ``;

    function handleTextEditorChange(val: string) {
        updateDraft({
            ...JSON.parse(val),
            layoutID: draft!.layoutID,
            name: draft!.name,
            layoutViewType,
        });
    }

    return draft ? (
        <PageLoadStatus loadable={draftLoadable}>
            <Modal size={ModalSizes.FULL_SCREEN} isVisible>
                <AdminEditTitleBar
                    title={draft?.name || t("Untitled")}
                    cancelPath={
                        isNewDraft
                            ? LegacyLayoutsRoute.url(layoutViewType)
                            : LayoutOverviewRoute.url({
                                  name: draft.name,
                                  layoutID: draft.layoutID,
                                  layoutViewType,
                              } as ILayout)
                    }
                    disableSave={isSaving}
                    onSave={handleSave}
                    onTitleChange={(newTitle) => {
                        updateDraft({ ...draft, name: newTitle });
                    }}
                    autoFocusTitleInput={!!isNewDraft}
                    isCompact={isCompact}
                />
                <TextEditor
                    value={jsonString}
                    onChange={(_e, value) => {
                        if (value) {
                            handleTextEditorChange(value);
                        }
                    }}
                    language="json"
                    jsonSchemaUri={siteUrl(`/api/v2/layouts/schema?layoutViewType=${layoutViewType}`)}
                />
            </Modal>
        </PageLoadStatus>
    ) : (
        <></>
    );
}
