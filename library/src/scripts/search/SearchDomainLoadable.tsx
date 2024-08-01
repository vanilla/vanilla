/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type SearchDomain from "@library/search/SearchDomain";
import type {
    IAdditionalFilterSchemaField,
    IExtendableSearchDomain,
    ISearchSubType,
} from "@library/search/SearchDomain";

export class SearchDomainLoadable implements IExtendableSearchDomain {
    private additionalFilterSchema: IAdditionalFilterSchemaField[] = [];
    private _subTypes: ISearchSubType[] = [];
    private promise: Promise<SearchDomain> | null = null;
    public loadedDomain: SearchDomain | null = null;

    public constructor(
        public key: string,
        private loadable: () => Promise<SearchDomain<any> | { default: SearchDomain }>,
    ) {}

    addFieldToFilterSchema(field: IAdditionalFilterSchemaField): void {
        if (this.loadedDomain) {
            this.loadedDomain.addFieldToFilterSchema(field);
        } else {
            this.additionalFilterSchema.push(field);
        }
    }

    addSubType(subType: ISearchSubType): void {
        if (this.loadedDomain) {
            this.loadedDomain.addSubType(subType);
        } else {
            this._subTypes.push(subType);
        }
    }

    get subTypes(): ISearchSubType[] {
        return this.loadedDomain?.subTypes ?? this._subTypes;
    }

    public async load(): Promise<SearchDomain> {
        if (this.loadedDomain) {
            return this.loadedDomain;
        }

        if (!this.promise) {
            this.promise = this.loadable().then((result) => {
                if ("default" in result) {
                    return result.default;
                } else {
                    return result;
                }
            });
        }

        let result = await this.promise;

        this.subTypes.forEach((subType) => result.addSubType(subType));
        this.additionalFilterSchema.forEach((field) => result.addFieldToFilterSchema(field));

        this.loadedDomain = result;
        return this.loadedDomain;
    }
}
