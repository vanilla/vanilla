import { ComboboxState, ComboboxStateById, ComboboxStoreById, NoData, TComboboxItem } from "@udecode/plate-combobox";

export interface ComboboxStyleProps<TData> extends ComboboxProps<TData> {
    highlighted?: boolean;
}

export interface ComboboxClassNames {
    listWrapperClassName: string;
    listClassName?: string;
    listItemClassName?: string;
    highlightedListItemClassName?: string;
}

export interface ComboboxItemProps<TData> {
    item: TComboboxItem<TData>;
    search: string;
}

export interface ComboboxProps<TData = NoData>
    extends Partial<Pick<ComboboxState<TData>, "items" | "floatingOptions">>,
        ComboboxStateById<TData>,
        ComboboxClassNames {
    /**
     * Render this component when the combobox is open (useful to inject hooks).
     */
    component?: React.FC<{ store: ComboboxStoreById }>;

    /**
     * Whether to hide the combobox.
     * @default !items.length
     */
    disabled?: boolean;

    /**
     * Render combobox item.
     * @default text
     */
    onRenderItem?: React.FC<ComboboxItemProps<TData>>;

    portalElement?: Element;
}
