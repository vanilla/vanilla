/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { useTagSearch } from "@library/features/tags/TagsHooks";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { LoadStatus } from "@library/@types/api/core";
import { Tokens } from "@library/forms/select/Tokens";

interface IProps extends Omit<React.ComponentProps<typeof Tokens>, "options" | "isLoading" | "value" | "onChange"> {
    value?: IComboBoxOption[];
    onChange: (tokens: IComboBoxOption[]) => void;
    menuPlacement?: string;
    type?: string;
}

export function TagsInput(props: IProps) {
    const [text, setText] = useState("");

    const { status = LoadStatus.LOADING, data = [] } = useTagSearch(text) || {};
    const tags = data.filter((tag) => {
        return props.type === undefined || tag.type === props.type;
    });
    return (
        <Tokens
            {...props}
            value={!props.value ? [] : props.value}
            onInputChange={setText}
            onChange={(options) => {
                props.onChange(options);
            }}
            options={tags.map((tag) => {
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
