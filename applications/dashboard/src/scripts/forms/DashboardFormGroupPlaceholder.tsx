/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import React from "react";

interface IProps {
    descriptionLines?: 1 | 2;
}

export function DashboardFormGroupPlaceholder(props: IProps) {
    return (
        <div className="form-group">
            <div className="label-wrap">
                <LoadingRectangle width="35%" height={14} />
                <LoadingSpacer height={6} />
                <LoadingRectangle width="80%" height={10} />
                {props.descriptionLines === 2 && (
                    <>
                        <LoadingSpacer height={4} />
                        <LoadingRectangle width="56%" height={10} />
                    </>
                )}
            </div>
            <div className="input-wrap">
                <input className="form-control" disabled aria-hidden tabIndex={-1} style={{ background: "#fff" }} />
            </div>
        </div>
    );
}
