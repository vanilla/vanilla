/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useDebouncedInput } from "@dashboard/hooks";
import { IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { Tokens } from "@library/forms/select/Tokens";
import SelectOne from "@library/forms/select/SelectOne";
import { t } from "@vanilla/i18n";
import React, { useCallback, useMemo, useState } from "react";

interface IProps {
    multiple?: boolean;
    onChange: (tokens: IComboBoxOption[]) => void;
    value: IComboBoxOption[];
    label: string | null;
    labelNote?: string;
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    hideTitle?: boolean;
    maxHeight?: number;
    name?: string;
}

/**
 * This component renders a drop down menu where a user is able to search and select a member.
 */
export function CommunityMemberInput(props: IProps) {
    const [query, setQuery] = useState<string>("");
    const debouncedQuery = useDebouncedInput(query, 300);

    const { suggestions, isLoading } = useMember(debouncedQuery);

    // Kludge for multi input since the form expects a CSV of names
    const [multiValues, setMultiValues] = useState<IComboBoxOption[]>();
    const namesAsCSV = useMemo(() => {
        return (multiValues ?? []).map(({ value }) => value).join(",");
    }, [multiValues]);

    if (props.multiple) {
        return (
            // This span is needed so that the lazy component has a node to mount into
            <span>
                <Tokens
                    {...props}
                    placeholder={props.placeholder ?? t("Search...")}
                    isLoading={isLoading}
                    onInputChange={setQuery}
                    label={props.label ?? ""}
                    showIndicator
                    options={suggestions}
                    maxHeight={props.maxHeight}
                    onChange={(values) => setMultiValues(values)}
                />
                {/* Token inputs expose the entire IComboBoxOption where as the give badges form requires a CSV of names*/}
                <input value={namesAsCSV} aria-hidden type={"hidden"} tabIndex={-1} name={props.name} hidden />
            </span>
        );
    }

    return (
        <SelectOne
            {...props}
            placeholder={props.placeholder ?? t("Search...")}
            onInputChange={setQuery}
            isLoading={isLoading}
            onChange={(option) => {
                if (props.onChange) props.onChange([option]);
            }}
            options={suggestions}
            label={props.label ?? ""}
            value={(props.value ?? [])[0]}
            maxHeight={props.maxHeight}
        />
    );
}

/**
 * This hook will return Member suggestions for a given search term
 */
export function useMember(query: string) {
    const [isLoading, setLoading] = useState(false);
    const [resultsByQuery, setResultsByQuery] = useState({});
    const resultsCached = useMemo(() => Object.keys(resultsByQuery).includes(query), [resultsByQuery, query]);

    const fetchMemberByName = useCallback(async (name: string) => {
        setLoading(true);
        const result = await apiv2.get("/users/by-names", {
            params: {
                name: `${name}*`,
                order: "name",
                page: 1,
                limit: 5,
            },
        });
        setResultsByQuery((prevResults) => ({ ...prevResults, [name]: result.data }));
    }, []);

    const suggestions = useMemo(() => {
        if (resultsCached) {
            setLoading(false);
            return (
                resultsByQuery[query]?.map((user: IUserFragment) => ({
                    value: user.name,
                    label: user.name,
                    data: {
                        ...user,
                    },
                })) ?? []
            );
        } else {
            !!query && query.length > 2 && fetchMemberByName(query);
        }
    }, [resultsByQuery, query, resultsCached]);

    return {
        suggestions,
        isLoading,
    };
}
