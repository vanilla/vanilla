/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

interface IProps {
    imgSrc?: string;
    title: string;
    info: string;
}

export function DashboardMediaItem(props: IProps) {
    return (
        <div className="media-sm">
            {props.imgSrc && (
                <div className="media-left">
                    <div className="media-image-wrap">
                        <img src={props.imgSrc} />
                    </div>
                </div>
            )}
            <div className="media-body">
                <div className="media-title">{props.title}</div>
                <div className="info user-email">{props.info}</div>
            </div>
        </div>
    );
}
