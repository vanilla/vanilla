/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { ProductMessagesApi } from "@library/features/adminAssistant/ProductMessages.api";
import Button from "@library/forms/Button";
import { UserPhoto } from "@library/headers/mebox/pieces/UserPhoto";
import { RightChevronSmallIcon } from "@library/icons/common";
import FlexSpacer from "@library/layout/FlexSpacer";
import { Row } from "@library/layout/Row";
import { QueryLoader } from "@library/loaders/QueryLoader";
import { MetaItem, MetaProfile, Metas } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import SmartLink from "@library/routing/links/SmartLink";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ColorVar } from "@library/styles/CssVar";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";

export namespace ProductMessageItem {
    export interface Props {
        message: ProductMessagesApi.Message;
        onClick?: () => void;
        staffView?: boolean;
    }
}

export function ProductMessageItem(props: ProductMessageItem.Props) {
    const { message } = props;

    const content = (
        <Row align={"center"} gap={12}>
            <UserPhoto userInfo={message.foreignInsertUser} size={"medium"} />
            <div>
                <h3 className={classes.title}>{message.name}</h3>
                <Metas>
                    <MetaItem>
                        <Translate
                            source={"From <0/>"}
                            c0={
                                <SmartLink to={message.foreignInsertUser.url!} asMeta>
                                    {message.foreignInsertUser.name}
                                </SmartLink>
                            }
                        ></Translate>
                    </MetaItem>

                    <MetaItem>
                        <DateTime date={message.dateInserted} />
                    </MetaItem>
                    {!props.staffView && !message.isDismissed && (
                        <MetaItem>
                            <Notice>{t("New")}</Notice>
                        </MetaItem>
                    )}
                    {props.staffView && <StaffViewCountMeta message={message} />}
                </Metas>
            </div>
            {props.onClick && (
                <>
                    <FlexSpacer actualSpacer />
                    <RightChevronSmallIcon className={classes.chevron} />
                </>
            )}
        </Row>
    );

    if (!props.onClick) {
        return content;
    }

    return (
        <Button buttonType={"reset"} className={classes.root} onClick={props.onClick}>
            {content}
        </Button>
    );
}

function StaffViewCountMeta(props: { message: ProductMessagesApi.Message }) {
    const { message } = props;

    const content = (
        <MetaItem>
            {/* Not translating because it's for staff only. */}
            Viewed by {message.countViewers} {message.countViewers === 1 ? "user" : "users"}.
        </MetaItem>
    );
    if (message.countViewers === 0) {
        return content;
    }

    return <ToolTip label={<ViewerToolTipContent message={message} />}>{content}</ToolTip>;
}

function ViewerToolTipContent(props: { message: ProductMessagesApi.Message }) {
    const viewersQuery = ProductMessagesApi.useGetViewersQuery(props.message.productMessageID);

    return (
        <div>
            <QueryLoader
                query={viewersQuery}
                loader={"Loading..."}
                success={(viewers) => {
                    return viewers.map((viewer) => {
                        return (
                            <Row key={viewer.userID} align={"center"} gap={8}>
                                <MetaProfile user={viewer} />
                            </Row>
                        );
                    });
                }}
            />
        </div>
    );
}

const classes = {
    root: css({
        display: "block",
        paddingBottom: 0,
        padding: 12,
        borderBottom: singleBorder(),
        ...extendItemContainer(16),
        background: ColorsUtils.var(ColorVar.Background),
        "&:hover, &:focus": {
            background: ColorsUtils.var(ColorVar.HighlightBackground),
        },
        "&:last-child": {
            borderBottom: "none",
        },
    }),
    title: css({
        width: "100%",
        fontSize: 14,
        textWrap: "balance",
        fontWeight: 600,
    }),
    metas: css({
        display: "flex",
    }),
    userContent: css({
        paddingTop: 12,
        paddingBottom: 0,
        textWrap: "pretty",
    }),
    actionRow: css({
        padding: "8px 16px",
        marginTop: 8,
        borderTop: singleBorder(),
        ...extendItemContainer(16),
    }),
    emptyRow: css({
        marginTop: 8,
    }),
    chevron: css({
        height: 16,
        width: 16,
        color: ColorsUtils.var(ColorVar.Primary),
    }),
};
