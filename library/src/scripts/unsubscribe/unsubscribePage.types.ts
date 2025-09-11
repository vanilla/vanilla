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

export interface IUnsubscribeContent extends IUnsubscribePreference {
    contentID: number;
    contentName: string;
    contentType?: string;
    contentUrl?: string;
}

export interface IMutedContent {
    discussionID: number;
    label: string;
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
    isAlreadyProcessed?: boolean;
    isEmailDigest?: boolean;
    isUnfollowContent?: boolean;
    followedContent?: IUnsubscribeContent;
    mutedContent?: IMutedContent;
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

interface IFollowContentResult extends IPreferenceResult {
    contentType: string;
    contentID: number;
    name: string;
    userID?: number;
}

interface IMuteResult {
    discussionID: number;
    discussionName: string;
    mute: boolean;
    userID: number;
}

export interface IUnsubscribeResult {
    preferences: IPreferenceResult[];
    mute?: IMuteResult;
    followCategory?: IFollowCategoryResult | never[];
    followContent?: IFollowContentResult | never[];
}
