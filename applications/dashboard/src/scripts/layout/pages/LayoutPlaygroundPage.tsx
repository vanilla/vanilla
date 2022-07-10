/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

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
import { useUrlSearchParams } from "@library/routing/QueryString";
import TextEditor from "@library/textEditor/TextEditor";
import { getSiteSection, siteUrl } from "@library/utility/appUtils";
import { escapeHTML } from "@vanilla/dom-utils";
import { useAsyncFn, useLastValue } from "@vanilla/react-utils";
import { useApiContext } from "@vanilla/ui";
import React, { useEffect, useMemo, useState } from "react";

export default function LayoutPlaygroundPage() {
    const { setLocalSpec: setSpec, localSpec: spec, updateDbSpec, isUpdating } = usePlaygroundSpec();
    const { isSetup, isSetupLoading, setIsSetup } = usePlaygroundSetup();
    const [isResultOpen, setIsResultOpen] = useState(false);
    const api = useApiContext();

    const params = useUrlSearchParams();
    const layoutViewType = params.get("layoutViewType") ?? "home";

    const [hydrateState, hydrate] = useAsyncFn(async (specString: string) => {
        const spec = JSON.parse(specString.trim());
        spec.params = {
            siteSectionID: getSiteSection().sectionID,
        };
        const response = await api.post("/layouts/hydrate", spec);
        return response.data;
    });

    const jsonResult = useMemo(() => {
        const body = hydrateState.data ?? extractServerError((hydrateState.error as any)?.response?.data);
        if (!body) {
            return null;
        }
        return escapeHTML(JSON.stringify(body, null, 4));
    }, [hydrateState.data, hydrateState.error]);

    useEffect(() => {
        initCodeHighlighting();
    }, [jsonResult]);

    const prevStatus = useLastValue(hydrateState.status);

    useEffect(() => {
        if (prevStatus === "loading" && hydrateState.status !== "loading") {
            setIsResultOpen(true);

            // If it was successful and we are setup save it.
            if (isSetup && hydrateState.status === "success") {
                updateDbSpec();
            }
        }
    }, [prevStatus, hydrateState.status]);

    const isLoading = hydrateState.status === "loading";

    const error = hydrateState.error ? extractServerError(hydrateState.error) : hydrateState.error;

    return (
        <Modal isVisible={true} size={ModalSizes.FULL_SCREEN}>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    hydrate(spec);
                }}
            >
                <ActionBar
                    isCallToActionDisabled={isLoading}
                    isCallToActionLoading={isLoading}
                    callToActionTitle="Hydrate"
                    noBackLink
                    title={"Layout Editor"}
                    additionalActions={
                        <FormToggle
                            slim
                            enabled={isSetup ?? false}
                            onChange={setIsSetup}
                            visibleLabel={"Enable custom homepage"}
                            indeterminate={isSetupLoading}
                            disabled={isSetupLoading}
                        />
                    }
                />
            </form>
            <TextEditor
                value={spec}
                onChange={(e, value) => {
                    setSpec(value ?? "");
                }}
                language="json"
                jsonSchemaUri={siteUrl(`/api/v2/layouts/schema?layoutViewType=${layoutViewType}`)}
            />
            <Modal
                size={ModalSizes.MODAL_AS_SIDE_PANEL_RIGHT_LARGE}
                exitHandler={() => {
                    setIsResultOpen(false);
                }}
                isVisible={isResultOpen}
            >
                <Frame
                    canGrow
                    className={css({
                        maxHeight: "initial",
                    })}
                    header={<FrameHeader closeFrame={() => setIsResultOpen(false)} title={"Result"} />}
                    body={
                        <FrameBody selfPadded>
                            {error && (
                                <Message
                                    icon={<ErrorIcon />}
                                    title={error.message}
                                    contents={error.description}
                                    stringContents={error.message}
                                />
                            )}
                            {jsonResult && <UserContent content={`<pre class="code codeBlock">${jsonResult}</pre>`} />}
                        </FrameBody>
                    }
                />
            </Modal>
        </Modal>
    );
}
