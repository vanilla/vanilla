import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IUserFragment } from "@library/@types/api/users";
import UserContent from "@library/content/UserContent";
import DiscussionBookmarkToggle from "@library/features/discussions/DiscussionBookmarkToggle";
import DiscussionOptionsMenu from "@library/features/discussions/DiscussionOptionsMenu";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import Button from "@library/forms/Button";
import { ButtonType } from "@library/forms/buttonTypes";
import { getMeta, t } from "@library/utility/appUtils";
import { VanillaButtonProps } from "@library/widget-fragments/Components.injectable";
import { useQueryClient } from "@tanstack/react-query";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { ContentItemActions } from "@vanilla/addon-vanilla/contentItem/ContentItemActions";
import { useContentItemContext } from "@vanilla/addon-vanilla/contentItem/ContentItemContext";
import { ContentItemPermalink } from "@vanilla/addon-vanilla/contentItem/ContentItemPermalink";
import { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { ReportCountMeta } from "@vanilla/addon-vanilla/reporting/ReportCountMeta";
import { blessStringAsSanitizedHtml } from "@vanilla/dom-utils";

function UserSignature({ user, classNames }: { user: IUserFragment; classNames?: string }) {
    const signatureContent = user?.signature?.body;
    const hideMobileSignatures = getMeta("signatures.hideMobile", false);
    const showSignature = getMeta("signatures.enabled", false) && !hideMobileSignatures;

    return (
        <>
            {signatureContent && showSignature && (
                <div className={classNames}>
                    <UserContent vanillaSanitizedHtml={blessStringAsSanitizedHtml("signatureContent")} />
                </div>
            )}
        </>
    );
}

interface PostReplyButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    onReply?: () => void;
    buttonType?: VanillaButtonProps["buttonType"];
}

function PostReplyButton(props: PostReplyButtonProps) {
    const permissions = usePermissionsContext();

    const buttonPropsWithDefaults = {
        className: "originalPostFragment__replyButton",
        ...props,
        buttonType: props.buttonType ?? ButtonType.TEXT,
    };

    if (!permissions.hasPermission("comments.add")) {
        return null;
    }

    return (
        <Button onClick={() => props.onReply && props.onReply()} {...buttonPropsWithDefaults}>
            {t("Reply")}
        </Button>
    );
}

function PostBookmarkToggle({ discussion, classNames }: { discussion: IDiscussion; classNames?: string }) {
    const queryClient = useQueryClient();

    return (
        <DiscussionBookmarkToggle
            discussion={discussion}
            onSuccess={async () => {
                await queryClient.invalidateQueries({ queryKey: ["discussion"] });
            }}
            classNames={classNames}
        />
    );
}

function PostOptionsMenu({ discussion }: { discussion: IDiscussion }) {
    const queryClient = useQueryClient();

    return (
        <DiscussionOptionsMenu
            discussion={discussion}
            onMutateSuccess={async () => {
                await queryClient.invalidateQueries({ queryKey: ["discussion"] });
            }}
            onDiscussionPage
        />
    );
}

const OriginalPostFragmentInjectable = {
    useContentItemContext,
    ContentItemPermalink,
    ContentItemActions,
    UserSignature,
    PostReplyButton,
    ReportCountMeta,
    PostBookmarkToggle,
    PostOptionsMenu,
};

namespace OriginalPostFragmentInjectable {
    export interface Props {
        discussion: IDiscussion;
        discussionApiParams?: DiscussionsApi.GetParams;
        category: ICategoryFragment;
        title?: string;
        moderationContent?: React.ReactNode;
        onReply?: () => void;
    }
}

export default OriginalPostFragmentInjectable;
