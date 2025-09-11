/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState } from "react";
import { Icon, IconType } from "@vanilla/icons";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { IMessage, IReference } from "./AiConversations.types";
import { MetaTag } from "@library/metas/Metas";
import { TagPreset } from "@library/metas/Tags.variables";
import { MetaItem } from "@library/metas/Metas";
import Translate from "@library/content/Translate";
import { BottomChevronIcon, TopChevronIcon } from "@library/icons/common";
import { cx } from "@emotion/css";
import { messageSourcesStyles } from "@library/aiConversations/MessageSources.styles";
import DateTime from "@library/content/DateTime";
import { IconMap } from "@library/aiConversations/AiConversations.fixtures";

interface ITrayProps {
    buttonLabel: string | React.ReactNode;
    className?: string;
    children: React.ReactNode;
}

function Tray(props: ITrayProps) {
    const { buttonLabel, children, className } = props;

    const [showContents, setShowContents] = useState(false);

    const classes = messageSourcesStyles();

    return (
        <div className={cx(classes.trayContainer, className)}>
            <Button
                buttonType={ButtonTypes.TEXT}
                onClick={() => setShowContents(!showContents)}
                className={classes.trayButton}
            >
                {showContents ? <TopChevronIcon /> : <BottomChevronIcon />}

                {buttonLabel}
            </Button>

            {showContents && <div className={classes.trayContentsContainer}>{children}</div>}
        </div>
    );
}

interface IMessageSourcesListProps {
    sources: IReference[];
    currentModel?: string;
}

export function MessageSourcesList(props: IMessageSourcesListProps) {
    const { sources, currentModel } = props;

    const classes = messageSourcesStyles();

    // Assumes the model is either OPENAIVNRAGBOT or WATSONXVNRAGBOT
    const urlParams =
        currentModel === "WATSONXVNRAGBOT"
            ? `?utm_source=ai-assistant&utm_medium=chat&utm_campaign=watsonX`
            : `?utm_source=ai-assistant&utm_medium=chat&utm_campaign=openAI`;

    return (
        <ol className={classes.messageSourcesList}>
            {sources.map((source, index) => {
                const icon = IconMap[source.recordType as keyof typeof IconMap] ?? "meta-discussions";
                return (
                    <li
                        className={cx(classes.messageSourcesListItem, { largeNumber: index >= 9 })}
                        key={source.recordID}
                    >
                        <div>
                            <a href={`${source.url}${urlParams}`}>
                                <h4>{source.name}</h4>
                            </a>

                            {source.dateUpdated && (
                                <>
                                    <MetaItem>
                                        <MetaItem>
                                            <Translate
                                                source="Updated <0/>"
                                                c0={<DateTime date={new Date(source.dateUpdated)} />}
                                            />
                                        </MetaItem>
                                    </MetaItem>
                                </>
                            )}

                            <MetaTag tagPreset={TagPreset.GREYSCALE} className={classes.recordTypeTag}>
                                <Icon icon={icon as IconType} size={"compact"} />

                                <MetaItem>{source.recordType}</MetaItem>
                            </MetaTag>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}

interface IMessageSourcesProps {
    message: IMessage;
    currentModel?: string;
}

export default function MessageSources(props: IMessageSourcesProps) {
    const { message, currentModel } = props;
    const sources = message.references;

    const classes = messageSourcesStyles();

    if (!sources) {
        return null;
    }

    return (
        <Tray buttonLabel={<Translate source="<0/> Sources" c0={sources.length} />}>
            <h3>
                {t("Sources")} <span className={classes.titleSourcesNumber}>{sources.length}</span>
            </h3>

            <MessageSourcesList sources={sources} currentModel={currentModel} />
        </Tray>
    );
}
