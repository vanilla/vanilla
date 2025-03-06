/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { INTEGRATIONS_META_KEY } from "@library/features/discussions/integrations/Integrations.context";
import {
    FAKE_ATTACHMENT,
    FAKE_INTEGRATIONS_CATALOG,
    IntegrationsTestWrapper,
    mockApi,
    queryClient,
} from "@library/features/discussions/integrations/fixtures/Integrations.fixtures";
import { setMeta } from "@library/utility/appUtils";
import { RenderResult, act, render } from "@testing-library/react";
import PostAttachmentsAsset from "@vanilla/addon-vanilla/posts/PostAttachmentsAsset";
import { vitest } from "vitest";

const mockDiscussion: IDiscussion = { ...LayoutEditorPreviewData.discussion(), attachments: [FAKE_ATTACHMENT] };

beforeAll(() => {
    setMeta(INTEGRATIONS_META_KEY, FAKE_INTEGRATIONS_CATALOG);
});

afterEach(() => {
    vitest.clearAllMocks();
    queryClient.clear();
});

describe("PostAttachmentsAsset", () => {
    let result: RenderResult;

    describe("With attachments", () => {
        beforeEach(async () => {
            mockApi.refreshAttachments.mockClear();
            await act(async () => {
                result = render(
                    <IntegrationsTestWrapper>
                        <PostAttachmentsAsset discussion={mockDiscussion} />
                    </IntegrationsTestWrapper>,
                );
            });
        });
        it("calls the refresh attachments endpoint", async () => {
            expect(mockApi.refreshAttachments).toHaveBeenCalled();
        });
    });

    describe("Without attachments", () => {
        beforeEach(async () => {
            mockApi.refreshAttachments.mockClear();
            await act(async () => {
                result = render(
                    <IntegrationsTestWrapper>
                        <PostAttachmentsAsset discussion={{ ...mockDiscussion, attachments: undefined }} />
                    </IntegrationsTestWrapper>,
                );
            });
        });
        it("does not call the refresh endpoint", async () => {
            expect(mockApi.refreshAttachments).not.toHaveBeenCalled();
        });
    });
});
