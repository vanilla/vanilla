/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import classNames from "classnames";
import Quill, { RangeStatic, Blot } from "quill/core";
import MentionItem, { NoResultMentionItem, IMentionData } from "./MentionItem";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import MentionBlot from "../Quill/Blots/Embeds/MentionBlot";

interface IProps extends IEditorContextProps {
    mentionData: IMentionData[];
    matchedString: string;
    id: string;
    activeItemId: string | null;
    onItemClick: React.MouseEventHandler<any>;
}

export class MentionList extends React.Component<IProps> {
    private quill: Quill;

    constructor(props) {
        super(props);
        this.quill = props.quill;
    }

    public render() {
        const { activeItemId } = this.props;

        const hasResults = this.props.mentionData.length > 0;
        const classes = classNames("atMentionList-items", "MenuItems");

        const list = (
            <span className="atMentionList">
                <ul id={this.props.id} aria-label="{t('@mention user list')}" className={classes} role="listbox">
                    {hasResults ? (
                        this.props.mentionData.map(mentionItem => {
                            const isActive = mentionItem.uniqueID === activeItemId;
                            return (
                                <MentionItem
                                    {...mentionItem}
                                    key={mentionItem.name}
                                    onClick={this.props.onItemClick}
                                    isActive={isActive}
                                    matchedString={this.props.matchedString}
                                />
                            );
                        })
                    ) : (
                        <NoResultMentionItem />
                    )}
                </ul>
            </span>
        );

        return list;
    }
}

export default withEditor<IProps>(MentionList);
