/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import DateTime from "@library/content/DateTime";
import { useToast } from "@library/features/toaster/ToastContext";
import { MetaButton, MetaItem, MetaLink } from "@library/metas/Metas";
import { t } from "@vanilla/i18n";
import Translate from "@library/content/Translate";
import ProfileLink from "@library/navigation/ProfileLink";
import { metasClasses } from "@library/metas/Metas.styles";
import { unknownUserFragment } from "@library/features/users/constants/userFragment";
import ContentItemClasses from "@vanilla/addon-vanilla/contentItem/ContentItem.classes";
import { useContentItemContext } from "@vanilla/addon-vanilla/contentItem/ContentItemContext";

interface IContentItemPermalinkProps {
    readOnly?: boolean;
}

export function ContentItemPermalink(props: IContentItemPermalinkProps) {
    const toast = useToast();
    const classes = ContentItemClasses();

    const { recordUrl, timestamp, dateUpdated, updateUser, handleCopyUrl } = useContentItemContext();

    async function handleCopyAndUpdateUrl() {
        const queryParams = new URLSearchParams(window.location.search);

        window.history.pushState({}, "", recordUrl);

        // Special handling for Q&A tabbed comment section
        if (queryParams.has("tab")) {
            await handleCopyUrl("tab", queryParams.get("tab"));
        } else {
            await handleCopyUrl();
        }

        toast.addToast({
            body: <>{t("Link copied to clipboard.")}</>,
            autoDismiss: true,
        });
    }

    return (
        <>
            <MetaLink
                to={recordUrl}
                onClick={(e) => {
                    e.preventDefault();
                    !props.readOnly && void handleCopyAndUpdateUrl();
                }}
            >
                <DateTime timestamp={timestamp} />
            </MetaLink>
            {dateUpdated && (
                <MetaItem>
                    <Translate
                        source="Updated <0/> by <1/>"
                        c0={<DateTime timestamp={dateUpdated} />}
                        c1={<ProfileLink asMeta userFragment={updateUser ?? unknownUserFragment()} />}
                    />
                </MetaItem>
            )}

            {!props.readOnly && (
                <MetaButton
                    icon={"copy-link"}
                    className={classes.copyLinkButton}
                    title={t("Copy Link")}
                    aria-label={t("Copy Link")}
                    onClick={() => {
                        void handleCopyAndUpdateUrl();
                    }}
                />
            )}
        </>
    );
}
