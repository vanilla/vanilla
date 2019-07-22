/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject, useEffect, useRef, useState, useLayoutEffect } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IImageMeta, ImageEmbedMenu } from "@library/embeddedContent/menus/ImageEmbedMenu";
import { debuglog } from "util";
import { IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import { useFocusWatcher } from "@vanilla/react-utils";
import classNames from "classnames";
import { editorFormClasses } from "@knowledge/modules/editor/editorFormStyles";
import {MaxWidthProperty, TopProperty, WidthProperty} from "csstype";
import {TLength} from "typestyle/lib/types";

export interface IEmbedStyles {
    top: TopProperty<TLength>,
    width: WidthProperty<TLength>,
    maxWidth: MaxWidthProperty<TLength>,
}

interface IProps {
    positionStyles: IEmbedStyles | null;
    setPositionStyles: (styles: IEmbedStyles | null) => void;
    isVisible: boolean;
    setVisibility: (visible: boolean) => void;
}

export function EmbedMenu(props: IProps) {
    const classes = editorFormClasses();
    const embedClass = classes.embedMetaDataMenuPosition(props.positionStyles);
    return (
        <div
            id={"embedMetaDataMenu"}
            className={classNames("js-embedMetaDataMenu", classes.embedMetaDataMenu)}
        />
    );
}
