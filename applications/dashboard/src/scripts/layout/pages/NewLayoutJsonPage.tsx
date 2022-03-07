/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { RouteComponentProps } from "react-router-dom";
import { PageLoadStatus } from "@library/loaders/PageLoadStatus";
import { useLayoutJsonDraft } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import EditLayoutJsonPage from "@dashboard/layout/pages/EditLayoutJsonPage";
import { ILayout, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";

type IProps = RouteComponentProps<
    {
        layoutViewType: LayoutViewType;
    },
    {},
    { copyLayoutJsonID?: ILayout["layoutID"] }
>;

export default function NewLayoutJsonPage(props: IProps) {
    const { layoutViewType } = props.match.params;
    const copyLayoutJsonID = new URLSearchParams(props.history.location.search).get("copyLayoutJsonID") ?? undefined;
    const copy = !!copyLayoutJsonID;
    const layoutJsonDraft = useLayoutJsonDraft(undefined, undefined, layoutViewType, copy);

    return (
        <PageLoadStatus loadable={layoutJsonDraft}>
            <EditLayoutJsonPage {...props} draftID={layoutJsonDraft.data?.layoutID} />
        </PageLoadStatus>
    );
}
