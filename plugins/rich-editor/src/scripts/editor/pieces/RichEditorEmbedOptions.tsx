/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import { richEditorEmbedOptionsClasses } from "@rich-editor/editor/pieces/richEditorEmedOptionsStyles";

interface IProps {
    id: string;
    visible: boolean;
}

interface IState {
    isVisible: boolean;
}

/**
 * A class for rendering Giphy embeds.
 */
export function RichEditorEmbedOptions(props: IProps, state: IState) {
    const { id, visible } = props;
    const [isVisible, setVisible] = useState(false);

    useEffect(() => {
        if (state.isVisible === false && visible) {
            // Todo, position
            setVisible(visible);
        } else {
            setVisible(visible);
        }
    }, [visible]);

    const classes = richEditorEmbedOptionsClasses();
    if (props.visible) {
        return <div id={props.id} className={classes.root} />;
    } else {
        return null;
    }
}
