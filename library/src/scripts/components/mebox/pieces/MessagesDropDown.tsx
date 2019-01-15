/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import DropDown from "@library/components/dropdown/DropDown";
import { t } from "@library/application";
import MessagesContents, { IMessagesContentsProps } from "@library/components/mebox/pieces/MessagesContents";
import MessagesToggle from "@library/components/mebox/pieces/MessagesToggle";
import { connect } from "react-redux";
import { IConversationsStoreState } from "@library/conversations/ConversationsModel";
import { MeBoxItemType, IMeBoxMessageItem } from "@library/components/mebox/pieces/MeBoxDropDownItem";
import get from "lodash/get";
import { IConversation, IUserFragment, GetConversationsExpand } from "@library/@types/api";
import ConversationsActions from "@library/conversations/ConversationsActions";
import apiv2 from "@library/apiv2";

interface IProps extends IMessagesContentsProps {
    actions: ConversationsActions;
    buttonClassName?: string;
    className?: string;
    contentsClassName?: string;
    countUnread: number;
    toggleContentsClassName?: string;
}

interface IState {
    open: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export class MessagesDropDown extends React.Component<IProps, IState> {
    private id = uniqueIDFromPrefix("messagesDropDown");

    public state: IState = {
        open: false,
    };

    /**
     * Get the React component to added to the page.
     *
     * @returns A DropDown component, configured to display notifications.
     */
    public render() {
        return (
            <DropDown
                id={this.id}
                name={t("Messages")}
                buttonClassName={classNames("vanillaHeader-messages", this.props.buttonClassName)}
                renderLeft={true}
                contentsClassName={this.props.contentsClassName}
                toggleButtonClassName="vanillaHeader-button"
                buttonContents={
                    <MessagesToggle
                        open={this.state.open}
                        count={this.props.countUnread}
                        countClass={this.props.countClass}
                        className={this.props.toggleContentsClassName}
                    />
                }
                onVisibilityChange={this.setOpen}
            >
                <MessagesContents
                    data={this.props.data}
                    count={this.props.countUnread}
                    countClass={this.props.countClass}
                />
            </DropDown>
        );
    }

    /**
     * A method to be invoked immediately after a component is inserted into the tree.
     */
    public componentDidMount() {
        void this.props.actions.getConversations({ expand: GetConversationsExpand.ALL });
    }

    /**
     * Assign the open (visibile) state of this component.
     *
     * @param open Is this menu open and visible?
     */
    private setOpen = open => {
        this.setState({
            open,
        });
    };
}

/**
 * Create action creators on the component, bound to a Redux dispatch function.
 *
 * @param dispatch Redux dispatch function.
 */
function mapDispatchToProps(dispatch) {
    return {
        actions: new ConversationsActions(dispatch, apiv2),
    };
}

/**
 * Update the component state, based on changes to the Redux store.
 *
 * @param state Current Redux store state.
 */
function mapStateToProps(state: IConversationsStoreState) {
    let countUnread: number = 0;
    const data: IMeBoxMessageItem[] = [];
    const conversationsByID = get(state, "conversations.conversationsByID.data", false);

    if (conversationsByID) {
        // Tally the total unread messages. Massage rows into something that will fit into IMeBoxMessageItem.
        for (const conversation of Object.values(conversationsByID) as IConversation[]) {
            const authors: IUserFragment[] = [];
            const messageDoc = new DOMParser().parseFromString(conversation.body, "text/html");
            if (conversation.unread === true) {
                countUnread++;
            }
            conversation.participants.forEach(participant => {
                authors.push(participant.user);
            });
            data.push({
                authors,
                countMessages: conversation.countMessages,
                message: messageDoc.body.textContent || "",
                photo: conversation.lastMessage!.insertUser.photoUrl || null,
                to: conversation.url,
                recordID: conversation.conversationID,
                timestamp: conversation.lastMessage!.dateInserted,
                type: MeBoxItemType.MESSAGE,
                unread: conversation.unread,
            });
        }

        // Conversations are indexed by ID, which means they'll be sorted by when they were inserted, ascending. Adjust for that.
        data.sort((itemA: IMeBoxMessageItem, itemB: IMeBoxMessageItem) => {
            const timeA = new Date(itemA.timestamp).getTime();
            const timeB = new Date(itemB.timestamp).getTime();

            if (timeA < timeB) {
                return 1;
            } else if (timeA > timeB) {
                return -1;
            } else {
                return 0;
            }
        });
    }

    return {
        countUnread,
        data,
    };
}

// Connect Redux to the React component.
export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(MessagesDropDown);
