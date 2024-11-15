/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import DateTime from "@library/content/DateTime";
import { useToast } from "@library/features/toaster/ToastContext";
import { MetaButton, MetaLink } from "@library/metas/Metas";
import { t } from "@vanilla/i18n";
import { ThreadItemPermalinkClasses } from "@vanilla/addon-vanilla/thread/ThreadItemPermalink.classes";
import { useThreadItemContext } from "@vanilla/addon-vanilla/thread/ThreadItemContext";

export default function ThreadItemPermalink(props: { readOnly?: boolean }) {
    const toast = useToast();
    const classes = ThreadItemPermalinkClasses();

    const { recordUrl, timestamp, handleCopyUrl } = useThreadItemContext();

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
                    !props.readOnly && handleCopyAndUpdateUrl();
                }}
            >
                <DateTime timestamp={timestamp} />
            </MetaLink>

            {!props.readOnly && (
                <MetaButton
                    icon={"editor-link"}
                    className={classes.copyLinkButton}
                    title={t("Copy Link")}
                    aria-label={t("Copy Link")}
                    onClick={async () => {
                        handleCopyAndUpdateUrl();
                    }}
                />
            )}
        </>
    );
}
