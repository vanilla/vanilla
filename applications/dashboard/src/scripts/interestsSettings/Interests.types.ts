/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { RecordID } from "@vanilla/utils";

interface IInterestProfileField {
    apiName: string;
    label: string;
    mappedValue: string[];
    options?: string[];
}

interface IInterestTag {
    tagID: RecordID;
    fullName: string;
}

export interface IInterest {
    interestID: RecordID;
    apiName: string;
    name: string;
    profileFieldMapping?: Record<string, string[]>;
    categoryIDs?: RecordID[];
    tagIDs?: RecordID[];
    isDefault?: boolean;
    categories?: Array<Pick<ICategory, "categoryID" | "name">>;
    tags?: IInterestTag[];
    profileFields?: IInterestProfileField[];
}

export interface InterestFormValues {
    interestID?: RecordID;
    apiName: string;
    name: string;
    profileFields?: string[];
    categoryIDs?: RecordID[];
    tagIDs?: RecordID[];
    isDefault: boolean;
    [profileFieldApiName: string]: any;
}

export interface InterestFilters {
    name?: string;
    tagIDs?: RecordID[];
    categoryIDs?: RecordID[];
    profileFields?: string[];
    isDefault?: boolean;
}

export interface InterestQueryParams extends InterestFilters {}

export interface IInterestResponse {
    interestsList?: IInterest[];
    pagination?: ILinkPages;
}
