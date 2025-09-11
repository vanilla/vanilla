import { CategoryItemFragmentContext } from "@library/widget-fragments/CategoryItemFragment.context";
import type CategoryItem from "@vanilla/injectables/CategoryItemFragment";

export default function CategoryItemFragmentPreview(props: {
    previewData: CategoryItem.Props;
    children?: React.ReactNode;
}) {
    return (
        <CategoryItemFragmentContext.Provider value={{ ...props.previewData, isPreview: true }}>
            {props.children}
        </CategoryItemFragmentContext.Provider>
    );
}
