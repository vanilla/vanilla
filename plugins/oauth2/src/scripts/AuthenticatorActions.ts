/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IAuthenticatorState } from "@dashboard/@types/state";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import {
    IAuthenticator,
    IAuthenticatorList,
    IGetAllAuthenticatorsParams,
    INITIAL_AUTHENTICATOR_FORM_STATE,
} from "@oauth2/AuthenticatorTypes";
import { useMemo } from "react";
import { useDispatch } from "react-redux";
import actionCreatorFactory from "typescript-fsa";

const actionCreator = actionCreatorFactory("@@authenticators");

export class AuthenticatorActions extends ReduxActions {
    public static readonly getAllAuthenticatorACs = actionCreator.async<
        IGetAllAuthenticatorsParams,
        IAuthenticatorList,
        IApiError
    >("GET_ALL");

    public static readonly getEditAuthenticatorAC = actionCreator.async<
        { authenticatorID: number },
        IAuthenticator,
        IApiError
    >("GET_EDIT");

    public static updateFormAC = actionCreator<Partial<IAuthenticator>>("UPDATE_FORM");
    public static postFormACs = actionCreator.async<IAuthenticator, IAuthenticator, IApiError>("POST");
    public static patchFormACs = actionCreator.async<Partial<IAuthenticator>, IAuthenticator, IApiError>("PATCH");
    public static deleteAuthenticatorACs = actionCreator.async<number, void, IApiError>("DELETE");
    public static clearFormAC = actionCreator("CLEAR_FORM");
    public clearForm = this.bindDispatch(AuthenticatorActions.clearFormAC);

    public updateForm = this.bindDispatch(AuthenticatorActions.updateFormAC);

    public getAll = (params: IGetAllAuthenticatorsParams) => {
        const { page, limit = 10, type = "oauth2" } = params;

        const thunk = bindThunkAction(AuthenticatorActions.getAllAuthenticatorACs, async () => {
            const response = await this.api.get(`/authenticators?page=${page}&limit=${limit}&type=${type}`, {});
            const pagination = SimplePagerModel.parseLinkHeader(response.headers["link"], "page");
            const result: IAuthenticatorList = {
                items: response.data,
                pagination,
            };
            return result;
        })(params);

        return this.dispatch(thunk);
    };

    public getEdit = (authenticatorID: number): Promise<IAuthenticator> => {
        const thunk = bindThunkAction(AuthenticatorActions.getEditAuthenticatorAC, async () => {
            const response = await this.api.get(`/authenticators/${authenticatorID}/oauth2`, {});
            return response.data;
        })({ authenticatorID });
        return this.dispatch(thunk);
    };

    public initForm = async (authenticatorID?: number) => {
        this.updateForm(INITIAL_AUTHENTICATOR_FORM_STATE.data);
        if (authenticatorID) {
            const payload = await this.getEdit(authenticatorID);
            this.updateForm(payload);
        }
    };

    public saveForm = async (form: IAuthenticator): Promise<IAuthenticator> => {
        if (form.authenticatorID) {
            return await this.patchForm(form);
        } else {
            return await this.postForm(form);
        }
    };

    public postForm(form: IAuthenticator): Promise<IAuthenticator> {
        const thunk = bindThunkAction(AuthenticatorActions.postFormACs, async (state) => {
            const response = await this.api.post(`/authenticators/oauth2`, form);
            return response.data;
        })(form);

        return this.dispatch(thunk);
    }

    public deleteAuthenticator(authenticatorID: number): Promise<IAuthenticator> {
        const thunk = bindThunkAction(AuthenticatorActions.deleteAuthenticatorACs, async (state) => {
            const response = await this.api.delete(`/authenticators/${authenticatorID}`);
            return response.data;
        })(authenticatorID);

        return this.dispatch(thunk);
    }

    public patchForm(form: Partial<IAuthenticator>): Promise<IAuthenticator> {
        const { authenticatorID, ...params } = form;

        const thunk = bindThunkAction(AuthenticatorActions.patchFormACs, async () => {
            const response = await this.api.patch(`/authenticators/${authenticatorID}`, params);
            switch (response.data.type) {
                case "oauth2": {
                    const oauth2Response = await this.api.patch(`/authenticators/${authenticatorID}/oauth2`, params);
                    return oauth2Response.data;
                }
            }
            return response.data;
        })(form);

        return this.dispatch(thunk);
    }

    public setActive = (authenticatorID: number, active: boolean): Promise<IAuthenticator> => {
        const data = {
            authenticatorID,
            active,
        };

        const thunk = bindThunkAction(AuthenticatorActions.patchFormACs, async () => {
            const response = await this.api.patch(`/authenticators/${authenticatorID}`, data);
            return response.data;
        })(data);

        return this.dispatch(thunk);
    };
}

export function useAuthenticatorActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new AuthenticatorActions(dispatch, apiv2), [dispatch]);
    return actions;
}
