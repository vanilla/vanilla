/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { ITokenProps } from "@library/forms/select/Tokens";
import { useTagSearch } from "@library/features/tags/TagsHooks";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { LoadStatus } from "@library/@types/api/core";
import { LazyTokens } from "@library/forms/select/LazyTokens";

interface IProps extends Omit<ITokenProps, "options" | "isLoading" | "value" | "onChange"> {
    value?: IComboBoxOption[];
    onChange: (tokens: IComboBoxOption[]) => void;
    menuPlacement?: string;
}

export function TagsInput(props: IProps) {
    const [text, setText] = useState("");

    const { status = LoadStatus.LOADING, data = [] } = useTagSearch(text) || {};

    return (
        <LazyTokens
            {...props}
            value={!props.value ? [] : props.value}
            onInputChange={setText}
            onChange={(options) => {
                props.onChange(options);
            }}
            options={data.map((tag) => {
                return {
                    value: tag.tagID,
                    label: tag.name,
                    tagCode: tag.urlcode,
                };
            })}
            isLoading={!!text || [LoadStatus.PENDING, LoadStatus.LOADING].includes(status)}
        />
    );
}
