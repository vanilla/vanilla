/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import classNames from "classnames";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import MentionBlot from "../Quill/Blots/Embeds/MentionBlot";
import MentionSuggestion, { IMentionData, MentionSuggestionNotFound } from "./MentionSuggestion";
import { t } from "@core/application";
import { getMentionRange } from "../Quill/utility";
import QuillFlyoutBounds from "./QuillFlyoutBounds";
import { RangeStatic } from "quill/core";

interface IProps extends IEditorContextProps {
    mentionData: IMentionData[];
    matchedString: string;
    id: string;
    noResultsID: string;
    isVisible: boolean;
    activeItemId: string | null;
    onItemClick: React.MouseEventHandler<any>;
}

interface IState {
    flyoutWidth?: number | null;
    flyoutHeight?: number | null;
}

class MentionList extends React.PureComponent<IProps, IState> {
    public state = {
        flyoutWidth: null,
        flyoutHeight: null,
    };
    private flyoutRef: React.RefObject<any> = React.createRef();

    public componentDidMount() {
        this.setState({
            flyoutWidth: this.flyoutRef.current ? this.flyoutRef.current.offsetWidth : null,
            flyoutHeight: this.flyoutRef.current ? this.flyoutRef.current.offsetHeight : null,
        });
    }

    public render() {
        const { activeItemId, id, onItemClick, matchedString, mentionData, noResultsID, isVisible } = this.props;

        const hasResults = mentionData.length > 0;
        const classes = classNames("atMentionList-items", "MenuItems");

        return (
            <QuillFlyoutBounds
                selectionTransformer={this.selectionTransformer}
                horizontalAlignment="start"
                verticalAlignment="below"
                flyoutWidth={this.state.flyoutWidth}
                flyoutHeight={this.state.flyoutHeight}
                isActive={isVisible}
            >
                {({ x, y }) => {
                    const offset = 3;
                    let style: React.CSSProperties = {
                        visibility: "hidden",
                        position: "absolute",
                        zIndex: -1,
                    };

                    if (x && y) {
                        style = {
                            position: "absolute",
                            top: y.position,
                            left: x.position,
                            zIndex: 1,
                            visibility: "visible",
                        };
                    }

                    return (
                        <span style={style} className="atMentionList" ref={this.flyoutRef}>
                            <ul
                                id={id}
                                aria-label={t("@mention user suggestions")}
                                className={classes + (hasResults ? "" : " isHidden")}
                                role="listbox"
                            >
                                {hasResults &&
                                    mentionData.map(mentionItem => {
                                        const isActive = mentionItem.uniqueID === activeItemId;
                                        return (
                                            <MentionSuggestion
                                                {...mentionItem}
                                                key={mentionItem.name}
                                                onClick={onItemClick}
                                                isActive={isActive}
                                                matchedString={matchedString}
                                            />
                                        );
                                    })}
                            </ul>
                            <div className={classes + (hasResults ? " isHidden" : "")}>
                                <MentionSuggestionNotFound id={noResultsID} />
                            </div>
                        </span>
                    );
                }}
            </QuillFlyoutBounds>
        );
    }

    private selectionTransformer = (range: RangeStatic) => {
        return getMentionRange(this.props.quill!, range.index);
    };
}

export default withEditor<IProps>(MentionList);
