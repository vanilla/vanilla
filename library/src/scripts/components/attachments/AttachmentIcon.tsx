/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import Paragraph from "@library/components/Paragraph";
import Translate from "@library/components/translation/Translate";
import { AttachmentType, getAttachmentIcon } from "@library/components/attachments";

// Common to both attachment types
export interface IAttachmentIcon {
    name: string;
    type: AttachmentType;
}

// Attachment of type icon
interface IProps extends IAttachmentIcon {}

/**
 * Component representing 1 icon attachment.
 */
export default class AttachmentIcon extends React.Component<IProps> {
    public render() {
        return (
            <li className="attachmentsIcons-item">
                <div
                    className={classNames("attachmentsIcons-file", `attachmentsIcons-${this.props.type}`)}
                    title={t(this.props.type)}
                >
                    <span className="sr-only">
                        <Paragraph>
                            <Translate source="<0/> (Type: <1/>)" c0={this.props.name} c1={this.props.type} />
                        </Paragraph>
                    </span>
                    {getAttachmentIcon(this.props.type)}
                </div>
            </li>
        );
    }
}
