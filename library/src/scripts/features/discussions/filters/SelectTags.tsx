/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forum Inc
 * @license Proprietary
 */

import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import {
    AutoComplete,
    AutoCompleteLookupOptions,
    FormGroup,
    FormGroupInput,
    FormGroupLabel,
    ILookupApi,
} from "@vanilla/ui";
import { t } from "@vanilla/i18n";

export interface ISelectTagsProps {
    value: string;
    onChange: (newValue: string) => void;
    label?: string;
}

export function SelectTags(props: ISelectTagsProps) {
    const { value: valueFromProps, onChange, label } = props;

    return (
        <FormGroup>
            <FormGroupLabel>{t(label ?? "Tags")}</FormGroupLabel>
            <FormGroupInput>
                <AutoComplete
                    value={valueFromProps && valueFromProps !== "" ? valueFromProps.split(",") : undefined}
                    onChange={(newValue) => onChange(Array.isArray(newValue) ? newValue.join(",") : newValue)}
                    optionProvider={
                        <AutoCompleteLookupOptions
                            lookup={{
                                searchUrl: "/api/v2/tags?type=User&limit=30&query=%s",
                                singleUrl: "/api/v2/tags/%s",
                                labelKey: "name",
                                valueKey: "tagID",
                                processOptions: (options: IComboBoxOption[]) => {
                                    return options.map((option) => {
                                        return {
                                            ...option,
                                            value: option.value.toString(),
                                        };
                                    });
                                },
                            }}
                        />
                    }
                />
            </FormGroupInput>
        </FormGroup>
    );
}
