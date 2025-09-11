/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Vanilla
 * @license gpl-2.0-only
 */

import { useCurrentUser } from "@library/features/users/userHooks";
import type { WidgetImageType } from "@library/homeWidget/WidgetItemOptions";
import { Metas } from "@library/metas/Metas";
import { getMeta } from "@library/utility/appUtils";
import { useCategoryItemFragmentContext } from "@library/widget-fragments/CategoryItemFragment.context";
import type CategoriesWidgetItem from "@library/widgets/CategoriesWidget.Item";
import { CategoriesWidgetItemMeta } from "@library/widgets/CategoriesWidget.ItemMeta";
import CategoryFollowDropDown from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";

function FollowMenu(props: { className?: string; displayMode?: "icon" | "icon-and-label" }) {
    const currentUser = useCurrentUser();
    const context = useCategoryItemFragmentContext();
    const { categoryItem } = context;
    const displayMode = props.displayMode ?? "icon";

    return (
        <CategoryFollowDropDown
            recordID={categoryItem.categoryID}
            userID={currentUser?.userID}
            name={categoryItem.name}
            notificationPreferences={categoryItem.preferences}
            emailDigestEnabled={getMeta("emails.digest", false)}
            iconOnly={displayMode === "icon"}
            className={props.className}
            onPreferencesChange={context.onCategoryFollowChange}
            viewRecordUrl={categoryItem.url as string}
            preview={context.isPreview}
        />
    );
}

function Meta(props: { className?: string; extraBefore?: React.ReactNode; extraAfter?: React.ReactNode }) {
    const context = useCategoryItemFragmentContext();
    const { categoryItem } = context;

    return (
        <Metas className={props.className}>
            {props.extraBefore}
            <CategoriesWidgetItemMeta category={categoryItem} categoryOptions={context.options} />
            {props.extraAfter}
        </Metas>
    );
}

const CategoryItemFragmentInjectable = {
    FollowMenu,
    Meta,
};
namespace CategoryItemFragmentInjectable {
    export interface CategoryItem extends CategoriesWidgetItem.Item {}

    export interface Props {
        categoryItem: CategoryItem;
        options: Omit<CategoriesWidgetItem.Options, "imagePlacement" | "contentType">;
        imageType: WidgetImageType;
    }
}

export default CategoryItemFragmentInjectable;
