/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import {
    fileExcel,
    fileWord,
    filePDF,
    fileGeneric,
    filePowerPoint,
    fileImage,
    fileZip,
} from "@library/components/icons/fileTypes";
import Paragraph from "@library/components/Paragraph";
import Translate from "@library/components/translation/Translate";

export enum AttachmentType {
    FILE = "file",
    PPT = "ppt",
    PDF = "pdf",
    EXCEL = "excel",
    WORD = "word",
    IMAGE = "image",
    ZIP = "zip",
}

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
                    {this.getAttachmentIcon(this.props.type)}
                </div>
            </li>
        );
    }

    private getAttachmentIcon(type: AttachmentType, className?: string) {
        switch (type) {
            case AttachmentType.EXCEL:
                return fileExcel(className);
            case AttachmentType.PDF:
                return filePDF(className);
            case AttachmentType.WORD:
                return fileWord(className);
            case AttachmentType.PPT:
                return filePowerPoint(className);
            case AttachmentType.ZIP:
                return fileZip(className);
            case AttachmentType.IMAGE:
                return fileImage(className);
            default:
                return fileGeneric(className);
        }
    }
}
