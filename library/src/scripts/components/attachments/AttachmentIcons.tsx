/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import { t } from "@library/application";
import Translate from "@library/components/translation/Translate";
import AttachmentIcon, { IAttachmentIcon } from "@library/components/attachments/AttachmentIcon";

// Array of icon attachments
interface IProps {
    attachments: IAttachmentIcon[];
    maxCount?: number;
}

/**
 * Generates a list of attachment icons
 */
export default class AttachmentIcons extends React.Component<IProps> {
    private maxCount = 3;
    private id = uniqueIDFromPrefix("attachmentIcons-");

    constructor(props: IProps) {
        super(props);
        if (props.maxCount && props.maxCount > 0 && props.maxCount <= props.attachments.length) {
            this.maxCount = props.maxCount;
        }
    }

    public get titleID() {
        return this.id + "-title";
    }

    public render() {
        if (this.attachmentsCount < 1) {
            return null;
        }

        const attachments = this.renderAttachments();

        if (attachments) {
            return (
                <section className="attachmentsIcons">
                    <h3 id={this.titleID} className="sr-only">
                        {t("Attachments: ")}
                    </h3>
                    <ul aria-labelledby={this.titleID} className="attachmentsIcons-items">
                        {attachments}
                    </ul>
                </section>
            );
        } else {
            return null;
        }
    }

    /**
     * Calculate the total attachments to display.
     */
    private get attachmentsCount(): number {
        return this.props.attachments.length;
    }

    /**
     * Render out the visible attachments.
     */
    private renderAttachments() {
        return this.props.attachments.map((attachment, i) => {
            const index = i + 1;
            const extraCount = this.attachmentsCount - index;
            if (i < this.maxCount) {
                return <AttachmentIcon name={attachment.name} type={attachment.type} key={index} />;
            } else if (i === this.maxCount && extraCount > 0) {
                return this.renderMorePlacholder(extraCount, index);
            } else {
                return null;
            }
        });
    }

    /**
     * Render a placeholder indicating that there are more unshown attachments.
     */
    private renderMorePlacholder(remainingCount: number, index: number): React.ReactNode {
        const message = <Translate source="+ <0/> more" c0={remainingCount} />;

        return (
            <li className="attachmentsIcons-item" key={index}>
                <span className={"attachmentsIcons-more metaStyle"}>{message}</span>
            </li>
        );
    }
}
