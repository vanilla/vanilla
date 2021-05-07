/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";

interface IProps {
    imgSrc?: string;
    title: string;
    info: string;
    className?: string;
}

export function DashboardMediaItem(props: IProps) {
    return (
        <div className="media-sm">
            {props.imgSrc !== undefined && (
                <div className="media-left">
                    <div className="media-image-wrap">
                        <img src={props.imgSrc} loading="lazy" />
                    </div>
                </div>
            )}
            <div className={classNames("media-body", props.className, props.className)}>
                <div className="media-title">{props.title}</div>
                <div className="info user-email">{props.info}</div>
            </div>
        </div>
    );
}
