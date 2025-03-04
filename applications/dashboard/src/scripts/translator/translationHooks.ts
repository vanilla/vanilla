/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useDispatch, useSelector } from "react-redux";
import { TranslationActions } from "./TranslationActions";
import apiv2 from "@library/apiv2";
import { ITranslationsGlobalStoreState } from "./translationReducer";
import { useMemo } from "react";

export function useTranslationActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => {
        return new TranslationActions(dispatch, apiv2);
    }, []);
    return actions;
}

export function useTranslationData() {
    return useSelector((state: ITranslationsGlobalStoreState) => state.translations);
}
