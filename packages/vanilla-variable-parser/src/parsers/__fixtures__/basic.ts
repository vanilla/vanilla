function makeThemeVars(name: string, value: any) {}

let thing: boolean = true;

/**
 * @varGroup banner.options
 * @title Banner - Options
 * @commonTitle Banner
 * @description Control different variants for the banner. These options can affect multiple parts of the banner at once.
 */
const options = makeThemeVars("options", {
    /**
     * @var banner.options.enabled
     * @title Enabled
     * @description When disabled the banner will not appear at all.
     * @type boolean
     */
    enabled: true,

    /**
     * @var banner.options.alignment
     * @title Alignment
     * @description Align the banner
     * @type string
     * @enum center | left | right
     */
    alignment: "center",

    /**
     * @var banner.options.mobileAlignment
     * @title Alignment (Mobile)
     * @description Align the banner on mobile. Defaults to match desktop alignment.
     * @type string
     * @enum center | left | right
     */
    mobileAlignment: "center",

    /**
     * @var banner.options.hideDescription
     * @type boolean
     */
    hideDescription: false,

    /**
     * @var banner.options.hideTitle
     * @type boolean
     */
    hideTitle: false,

    /**
     * @var banner.options.hideSearch
     * @title Hide SearchBar
     * @type boolean
     */
    hideSearch: false,

    /**
     * @var banner.options.searchPlacement
     * @title SearchBar Placement
     * @description Place the search bar in different parts of the banner.
     * @type string
     * @enum middle | bottom
     */
    searchPlacement: "middle",

    /**
     * @var banner.options.url
     * @title Title Url
     * @description When set turn the title into a link to this url.
     * @type string
     */
    url: "",

    overlayTitleBar: true,
});
