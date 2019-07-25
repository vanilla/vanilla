/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";

interface IProps {
    icon: JSX.Element;
    className?: string;
}

export class IconForButtonWrap extends React.PureComponent<IProps> {
    public render() {
        const classesRichEditor = richEditorClasses(false);
        return (
            <>
                <span className={classesRichEditor.iconWrap} />
                {this.props.icon}
            </>
        );
    }
}
