/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { filterBlockClasses } from "@dashboard/moderation/components/FilterBlock.classes";

import CheckBox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { ILookupApi } from "@library/forms/select/SelectLookup";
import { t } from "@vanilla/i18n";
import { AutoComplete, AutoCompleteLookupOptions } from "@vanilla/ui/src/forms/autoComplete";
import { ReactNode, useCallback, useEffect, useState } from "react";
import throttle from "lodash-es/throttle";
import omit from "lodash-es/omit";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import { DynamicOptionPill } from "@dashboard/moderation/components/DynamicOptionPill";
import apiv2 from "@library/apiv2";
import { notEmpty, stableObjectHash } from "@vanilla/utils";

export interface IFilterLookupApi extends ILookupApi {
    userIconPath?: string;
    optionOverride?: ISelectBoxItem[];
}

export type FilterBlockProps = {
    apiName: string;
    label: ReactNode;
    initialFilters?: string[];
    onFilterChange: (value: Record<string, string[] | string | undefined>) => void;
} & (
    | { staticOptions: ISelectBoxItem[]; dynamicOptionApi?: never }
    | { staticOptions?: never; dynamicOptionApi: IFilterLookupApi }
);

export function FilterBlock(props: FilterBlockProps) {
    const classes = filterBlockClasses();
    const [filters, setFilters] = useState<Record<string, boolean>>({});
    const [dynamicOptions, setDynamicOptions] = useState<ISelectBoxItem[]>([]);
    const [selectedOptions, setSelectedOptions] = useState<ISelectBoxItem[]>([]);
    const [showDynamicInput, setShowDynamicInput] = useState(false);

    /**
     * Options override for dynamic options,
     * if you want a specific option always available in the drop down
     */
    useEffect(() => {
        if (props.dynamicOptionApi?.optionOverride) {
            setDynamicOptions(props.dynamicOptionApi.optionOverride);
        }
    }, [props.dynamicOptionApi]);

    /**
     * State sync
     */
    useEffect(() => {
        setFilters((prevFilters) => {
            const initial = props.initialFilters
                ? Object.fromEntries(props.initialFilters.map((filter) => [filter, true]))
                : {};
            return { ...prevFilters, ...initial };
        });
        if (props.initialFilters && props.dynamicOptionApi) {
            void setInitialDynamicState(props.initialFilters);
        }
    }, []);

    useEffect(() => {
        const selectedFilters = Object.entries(filters)
            .filter(([, checked]) => checked)
            .map(([filter]) => filter);
        props.onFilterChange({ [props.apiName]: selectedFilters });
    }, [filters]);

    const debouncedCacheResults = useCallback(
        throttle((results: any) => {
            const { valueKey, labelKey } = props.dynamicOptionApi ?? { valueKey: "value", labelKey: "name" };
            const cached = results
                .map(({ data }) => data)
                .map((option: any) => ({ value: option[valueKey], name: option[labelKey], data: option }));
            setDynamicOptions((prev) => [...prev, ...cached]);
        }, 1000 / 60),
        [],
    );

    const handleDynamicOptionSelect = (id: string) => {
        const option = dynamicOptions.find((option) => option.value === id);
        if (option) {
            const isSelected = !!selectedOptions.find((option) => option.value === id);

            setSelectedOptions((prev) => {
                if (isSelected) {
                    return prev.filter((option) => option.value !== id);
                }
                return [...prev, option];
            });

            setFilters((prevFilters) => {
                if (isSelected) {
                    return omit(prevFilters, option.value);
                }
                return { ...prevFilters, [option.value]: true };
            });
        }
        setShowDynamicInput(false);
    };

    const handleInitialDynamicLookup = async (id: string): Promise<ISelectBoxItem[]> => {
        const { singleUrl, valueKey, labelKey } = props?.dynamicOptionApi ?? {};

        // Look up overrides before making an API call
        if (props.dynamicOptionApi?.optionOverride) {
            const override = props.dynamicOptionApi.optionOverride.find((option) => option.value === id);
            if (override) {
                return [override];
            }
        }

        if (singleUrl) {
            const url = singleUrl.replace("%s", id);
            const response = await apiv2.get(url);
            return Array.isArray(response.data)
                ? response.data.map((resp) => {
                      return {
                          value: resp[`${valueKey}`],
                          name: resp[`${labelKey}`],
                          data: {
                              ...resp,
                              ...(props.dynamicOptionApi?.userIconPath && {
                                  icon: resp,
                              }),
                          },
                      };
                  })
                : [
                      {
                          value: response.data[`${valueKey}`],
                          name: response.data[`${labelKey}`],
                          data: {
                              ...response.data,
                              ...(props.dynamicOptionApi?.userIconPath && {
                                  icon: response.data,
                              }),
                          },
                      },
                  ];
        }
        return [];
    };

    const setInitialDynamicState = async (ids: string[]) => {
        const stuff = await Promise.all(
            ids.map(async (id) => {
                const hasOption = dynamicOptions.find((option) => option.value === id);
                if (!hasOption) {
                    const options = await handleInitialDynamicLookup(id);
                    return options;
                }
            }),
        );
        const cleaned = stuff.flat().filter(notEmpty);
        setDynamicOptions((prev) => [...prev, ...cleaned]);
        setSelectedOptions((prev) => {
            return [...prev, ...cleaned];
        });
    };

    return (
        <div className={classes.root}>
            <span className={classes.title}>{props.label}</span>
            {props.staticOptions && (
                <CheckboxGroup>
                    {props.staticOptions.map((option) => {
                        return (
                            <CheckBox
                                key={`${option.name}`}
                                className={classes.checkbox}
                                label={t(`${option.name}`, { optional: true })}
                                labelBold={false}
                                checked={filters[option.value] ?? false}
                                onChange={(e) => setFilters((prev) => ({ ...prev, [option.value]: e.target.checked }))}
                            />
                        );
                    })}
                </CheckboxGroup>
            )}
            {props.dynamicOptionApi && (
                <>
                    {selectedOptions.length > 0 && (
                        <div className={classes.spacingContainer}>
                            <>
                                {selectedOptions.map((option) => {
                                    return (
                                        <DynamicOptionPill
                                            key={stableObjectHash(option)}
                                            {...option}
                                            isActive={filters[option.value] ?? false}
                                            onChange={(value) => {
                                                setFilters((prev) => ({
                                                    ...prev,
                                                    [option.value]: value,
                                                }));
                                            }}
                                            removeOption={() => {
                                                setSelectedOptions((prev) =>
                                                    prev.filter((opt) => opt.value !== option.value),
                                                );
                                                setFilters((prev) => {
                                                    return omit(prev, option.value);
                                                });
                                            }}
                                            userIconPath={props.dynamicOptionApi?.userIconPath}
                                        />
                                    );
                                })}
                            </>
                        </div>
                    )}

                    {showDynamicInput ? (
                        <AutoComplete
                            id={`${props.apiName}-filter`}
                            className={classes.dynamicInput}
                            value={""}
                            onChange={handleDynamicOptionSelect}
                            optionProvider={
                                <AutoCompleteLookupOptions
                                    api={apiv2}
                                    lookup={props.dynamicOptionApi}
                                    handleLookupResults={debouncedCacheResults}
                                />
                            }
                            options={
                                props.dynamicOptionApi?.optionOverride &&
                                props.dynamicOptionApi?.optionOverride.map((option) => ({
                                    ...option,
                                    label: `${option.name}`,
                                }))
                            }
                            autoFocus={true}
                        />
                    ) : (
                        <Button
                            className={classes.dynamicFilterButton}
                            buttonType={ButtonTypes.TEXT}
                            onClick={() => setShowDynamicInput(true)}
                        >
                            <Icon icon="filter-add" /> {t("Add Filter")}
                        </Button>
                    )}
                </>
            )}
        </div>
    );
}
