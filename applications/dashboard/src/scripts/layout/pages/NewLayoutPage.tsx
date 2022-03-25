/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { RouteComponentProps } from "react-router-dom";
import { PageLoadStatus } from "@library/loaders/PageLoadStatus";
import { useLayoutJsonDraft } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayout, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import EditLayoutPage from "@dashboard/layout/pages/EditLayoutPage";

type IProps = RouteComponentProps<
    {
        layoutViewType: LayoutViewType;
    },
    {},
    { copyLayoutJsonID?: ILayout["layoutID"] }
>;

export default function NewLayoutPage(props: IProps) {
    const { layoutViewType } = props.match.params;
    const copyLayoutJsonID = new URLSearchParams(props.history.location.search).get("copyLayoutJsonID") ?? undefined;
    const copy = !!copyLayoutJsonID;
    const layoutJsonDraft = useLayoutJsonDraft(undefined, undefined, layoutViewType, copy);

    return <EditLayoutPage {...props} draftID={layoutJsonDraft.data?.layoutID} />;
}
