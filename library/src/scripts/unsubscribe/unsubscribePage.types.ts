/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc
 * @license Proprietary
 */

import { IUserFragment } from "@library/@types/api/users";
import { ReactElement } from "react";

export type IUnsubscribeToken = "string";

export interface IActivityData {
    category?: string;
    reasons?: string[];
}

export interface IUnsubscribePreference {
    preferenceName: string;
    preferenceRaw: string;
    enabled: boolean;
    label: ReactElement;
    optionID: string;
}

export interface IUnsubscribeCategory extends IUnsubscribePreference {
    categoryID: number;
    categoryName: string;
}

export interface IDecodedToken {
    activityID: number;
    activityTypes: string[];
    activityData: IActivityData;
    user: {
        userID: IUserFragment["userID"];
        name: IUserFragment["name"];
        email: string;
        photoUrl?: string;
    };
}

export interface IUnsubscribeData extends IDecodedToken {
    preferences: IUnsubscribePreference[];
    hasMultiple?: boolean;
    isAllProcessed?: boolean;
    isEmailDigest?: boolean;
    isUnfollowCategory?: boolean;
    followedCategory?: IUnsubscribeCategory;
}

// The data type structure of the data returned from each API call
interface IPreferenceResult {
    preference: string;
    enabled: string | boolean;
}

interface IFollowCategoryResult extends IPreferenceResult {
    categoryID: number;
    name: string;
}

export interface IUnsubscribeResult {
    preferences: IPreferenceResult[];
    followCategory?: IFollowCategoryResult | any[];
}
