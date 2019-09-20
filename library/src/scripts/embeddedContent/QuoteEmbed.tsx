/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useCallback, useMemo, useEffect } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { IUserFragment } from "@library/@types/api/users";
import { useUniqueID } from "@library/utility/idUtils";
import classnames from "classnames";
import { makeProfileUrl, t } from "@library/utility/appUtils";
import SmartLink from "@library/routing/links/SmartLink";
import DateTime from "@library/content/DateTime";
import { CollapsableContent } from "@library/content/CollapsableContent";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { BottomChevronIcon, TopChevronIcon } from "@library/icons/common";
import UserContent from "@library/content/UserContent";
import { quoteEmbedClasses } from "@library/embeddedContent/quoteEmbedStyles";
import { metasClasses } from "@library/styles/metasStyles";
import classNames from "classnames";

interface IProps extends IBaseEmbedProps {
    body: string;
    dateInserted: string;
    insertUser: IUserFragment;
    expandByDefault?: boolean;
}

/**
 * An embed class for quoted user content on the same site.
 *
 * This is not an editable quote. Instead it an expandable/collapsable snapshot of the quoted/embedded comment/discussion.
 */
export function QuoteEmbed(props: IProps) {
    const { body, insertUser, name, url, dateInserted } = props;

    const classes = quoteEmbedClasses();
    const userUrl = makeProfileUrl(insertUser.name);
    const classesMeta = metasClasses();

    return (
        <EmbedContainer withPadding={false} className={classes.root}>
            <EmbedContent type="Quote" inEditor={props.inEditor}>
                <blockquote className={classes.body}>
                    <div className={classes.header}>
                        {name && (
                            <h2 className={classes.title}>
                                <SmartLink to={url} className={classes.titleLink}>
                                    {name}
                                </SmartLink>
                            </h2>
                        )}
                        <div className={classesMeta.root}>
                            <SmartLink to={userUrl} className={classNames(classesMeta.meta, classes.userName)}>
                                <span className="embedQuote-userName">{insertUser.name}</span>
                            </SmartLink>
                            <SmartLink to={url} className={classNames(classesMeta.meta)}>
                                <DateTime timestamp={dateInserted} />
                            </SmartLink>
                        </div>
                    </div>

                    <CollapsableContent
                        className={classes.content}
                        maxHeight={200}
                        isExpandedDefault={!!props.expandByDefault}
                    >
                        <UserContent content={body} />
                    </CollapsableContent>
                </blockquote>
            </EmbedContent>
        </EmbedContainer>
    );
}
