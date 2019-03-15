/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getAttachmentIcon } from "@library/content/attachments/attachmentUtils";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";
import { t } from "@library/utility/appUtils";
import Translate from "@library/content/Translate";
import { attachmentIconClasses } from "@library/content/attachments/attachmentIconsStyles";
import Paragraph from "@library/layout/Paragraph";
import classNames from "classnames";

// Common to both attachment types
export interface IAttachmentIcon {
    name: string;
    type: AttachmentType;
}

// Attachment of type icon
interface IProps extends IAttachmentIcon {
    classes: {
        item?: string;
    };
}

/**
 * Component representing 1 icon attachment.
 */
export default class AttachmentIcon extends React.Component<IProps> {
    public render() {
        const classes = attachmentIconClasses();
        return (
            <li className={classNames("attachmentsIcons-item", this.props.classes.item, classes.root)}>
                <div
                    className={classNames("attachmentsIcons-file", `attachmentsIcons-${this.props.type}`)}
                    title={t(this.props.type)}
                >
                    <span className="sr-only">
                        <Paragraph>
                            <Translate source="<0/> (Type: <1/>)" c0={this.props.name} c1={this.props.type} />
                        </Paragraph>
                    </span>
                    {getAttachmentIcon(this.props.type, classes.root)}
                </div>
            </li>
        );
    }
}
