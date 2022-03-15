/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useDispatch, useSelector } from "react-redux";
import * as layoutPageActions from "@library/features/Layout/LayoutPage.actions";
import { useEffect, useMemo } from "react";
import { bindActionCreators } from "redux";
import { ILayoutPageStoreState } from "@library/features/Layout/LayoutPage.slice";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";

export function useLayoutPageActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => {
        return bindActionCreators(layoutPageActions, dispatch);
    }, [dispatch]);
    return actions;
}

// Currently hardcoded.
export function useLayoutSpec(query: ILayoutQuery) {
    const actions = useLayoutPageActions();
    const paramHash = stableObjectHash(query ?? {});

    const existingLayout = useSelector((state: ILayoutPageStoreState) => {
        return (
            state.layoutPage.layoutsByHash[paramHash] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    useEffect(() => {
        if (existingLayout.status === LoadStatus.PENDING) {
            actions.lookupLayout(query);
        }
    }, [actions, existingLayout, query]);

    return existingLayout;
}
