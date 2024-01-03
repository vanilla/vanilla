/**
 * @author Mihran Abrahamian <mabrahamian@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { ISearchForm, ISearchRequestQuery, ISearchResult } from "@library/search/searchTypes";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import Result, { IResult } from "@library/result/Result";
import { EMPTY_SCHEMA, JsonSchema, PartialSchemaDefinition } from "@vanilla/json-schema-forms";
import { PermissionChecker } from "@library/features/users/Permission";
import { createSourceSetValue } from "@library/utility/appUtils";

export interface ISearchSubType {
    icon: React.ReactNode;
    type: string;
    label: string;
}

export interface IAdditionalFilterSchemaField {
    fieldName: string;
    schema: JsonSchema | PartialSchemaDefinition;
}

export interface IExtendableSearchDomain {
    addSubType: (subType: ISearchSubType) => void;
    addFieldToFilterSchema(field: IAdditionalFilterSchemaField): void;
}

export default abstract class SearchDomain<
    ExtraFormValues extends object = {},
    ResultType extends ISearchResult = ISearchResult,
    ResultComponentProps extends IResult = IResult,
> implements IExtendableSearchDomain
{
    abstract get name(): string;
    abstract get key(): string;
    abstract get sort(): number;
    abstract get icon(): React.ReactNode;
    abstract get recordTypes(): string[];

    public ResultComponent: React.FunctionComponent<ResultComponentProps> = Result;

    defaultFormValues?: Partial<ISearchForm<ExtraFormValues>>;

    PanelComponent?: React.ComponentType<any>;

    public getAllowedFields(permissionChecker: PermissionChecker): string[] {
        return [];
    }

    public subTypes: ISearchSubType[] = [];

    public addSubType = (subType: ISearchSubType): void => {
        if (!this.subTypes.find((existingSubType) => existingSubType.type === subType.type)) {
            this.subTypes.push(subType);
        }
    };

    public getFilterSchema(permissionChecker: PermissionChecker): JsonSchema {
        return EMPTY_SCHEMA;
    }

    public additionalFilterSchemaFields: IAdditionalFilterSchemaField[] = [];

    public addFieldToFilterSchema(field: IAdditionalFilterSchemaField): void {
        this.additionalFilterSchemaFields.push(field);
    }

    transformFormToQuery?(form: ISearchForm<ExtraFormValues>): ISearchRequestQuery<ExtraFormValues>;

    public isIsolatedType = false;

    public get sortValues(): ISelectBoxItem[] {
        return [];
    }

    public mapResultToProps(result: ResultType): ResultComponentProps {
        const icon = result.type
            ? this.subTypes.find((subType) => subType.type === result.type)?.icon ?? this.icon
            : this.icon ?? null;

        const sourceSet = {
            imageSet: createSourceSetValue(result?.image?.urlSrcSet ?? {}),
        };

        return {
            name: result.name,
            url: result.url,
            excerpt: result.body,
            image: result.image?.url,
            highlight: result.highlight,
            icon,
            ...(sourceSet.imageSet.length > 0 ? sourceSet : {}),
        } as any;
    }

    ResultWrapper?: React.ComponentType<any>;
    MetaComponent?: React.ComponentType<any>;

    getSpecificRecordID?(form: ISearchForm<ExtraFormValues>): number | undefined;

    SpecificRecordPanelComponent?: React.ComponentType<any>;
    SpecificRecordComponent?: React.ComponentType<any>;

    public showSpecificRecordCrumbs = false;
}
