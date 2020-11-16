/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { debug } from "@vanilla/utils";
import ErrorMessages from "@library/forms/ErrorMessages";
import { userContentClasses } from "@library/content/userContentStyles";

interface IDetailedError {
    code: string;
    field: string;
    index: number;
    message: string;
    status: number;
}

type IDetailedErrors = Record<string, IDetailedError[]>;

export function DetailedErrors(props: { detailedErrors?: IDetailedErrors }) {
    const { detailedErrors } = props;
    if (!detailedErrors || Object.keys(props).length === 0) {
        return <></>;
    }
    return (
        <div className={userContentClasses().root}>
            <h3>Debug Errors</h3>
            {Object.entries(detailedErrors).map(([fieldName, errors], i) => {
                return (
                    <label key={i}>
                        <strong>{fieldName}</strong>
                        <ErrorMessages errors={errors} />
                    </label>
                );
            })}
        </div>
    );
}
