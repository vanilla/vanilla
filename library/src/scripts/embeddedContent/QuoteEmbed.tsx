/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useCallback } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { IUserFragment } from "@library/@types/api/users";
import { useUniqueID } from "@library/utility/idUtils";
import classnames from "classnames";
import { makeProfileUrl, t } from "@library/utility/appUtils";
import SmartLink from "@library/routing/links/SmartLink";
import DateTime from "@library/content/DateTime";
import { chevronUp, bottomChevron } from "@library/icons/common";
import CollapsableUserContent from "@library/content/CollapsableContent";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";

interface IProps extends IBaseEmbedProps {
    body: string;
    dateInserted: string;
    insertUser: IUserFragment;
}

/**
 * An embed class for quoted user content on the same site.
 *
 * This is not an editable quote. Instead it an expandable/collapsable snapshot of the quoted/embedded comment/discussion.
 */
export function QuoteEmbed(props: IProps) {
    const [isCollapsed, setIsCollapsed] = useState(true);
    const [needsCollapseButton, setNeedsCollapseButton] = useState(false);
    const toggleCollapseState = useCallback(
        (event: React.MouseEvent<any>) => {
            event.preventDefault();
            setIsCollapsed(!isCollapsed);
        },
        [setIsCollapsed, isCollapsed],
    );

    const { body, insertUser, name, url, dateInserted } = props;
    const id = useUniqueID("collapsableContent-");

    const title = name ? (
        <h2 className="embedText-title embedQuote-title">
            <a href={url} className="embedText-titleLink">
                {name}
            </a>
        </h2>
    ) : null;

    const bodyClasses = classnames("embedText-body", "embedQuote-body", { isCollapsed });
    const userUrl = makeProfileUrl(insertUser.name);

    return (
        <EmbedContainer className="embedText embedQuote">
            <blockquote className={bodyClasses}>
                <div className="embedText-header embedQuote-header">
                    {title}
                    <SmartLink to={userUrl} className="embedQuote-userLink">
                        <span className="embedQuote-userName">{insertUser.name}</span>
                    </SmartLink>
                    <SmartLink to={url} className="embedQuote-metaLink">
                        <DateTime timestamp={dateInserted} className="embedText-dateTime embedQuote-dateTime meta" />
                    </SmartLink>

                    {needsCollapseButton && (
                        <button
                            type="button"
                            className="embedQuote-collapseButton"
                            aria-label={t("Toggle Quote")}
                            onClick={toggleCollapseState}
                            aria-pressed={isCollapsed}
                        >
                            {isCollapsed
                                ? bottomChevron("embedQuote-chevronDown")
                                : chevronUp("embedquote-chevronDown")}
                        </button>
                    )}
                </div>
                <div className="embedText-main embedQuote-main">
                    <div className="embedQuote-excerpt">
                        <CollapsableUserContent
                            setNeedsCollapser={setNeedsCollapseButton}
                            isCollapsed={isCollapsed}
                            id={id}
                            preferredMaxHeight={100}
                            dangerouslySetInnerHTML={{ __html: body }}
                        />
                    </div>
                </div>
            </blockquote>
        </EmbedContainer>
    );
}
