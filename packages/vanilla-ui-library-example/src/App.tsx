import * as VanillaUI from "@vanilla/ui-library";
import { useState } from "react";

function App() {
    const [formValue, setFormValue] = useState<any>({});
    const [tabValue, setTabValue] = useState("Components");
    const [theme, setTheme] = useState("dark" as "dark" | "light");

    return (
        <VanillaUI.Theme theme={theme}>
            <VanillaUI.Gutters>
                <VanillaUI.EditorTabs.Root value={tabValue} onValueChange={setTabValue}>
                    <VanillaUI.EditorTabs.List
                        style={{ paddingTop: "16px", paddingBottom: "16px" }}
                        overflowBehaviour={"scroll"}
                    >
                        <VanillaUI.EditorTabs.Trigger value="Components">Components</VanillaUI.EditorTabs.Trigger>
                        <VanillaUI.EditorTabs.Trigger value="Forms">Schema Form</VanillaUI.EditorTabs.Trigger>
                        <VanillaUI.EditorTabs.Trigger value="Pagers">Pagers</VanillaUI.EditorTabs.Trigger>

                        <span style={{ flex: 1 }} />
                        <VanillaUI.Select
                            inline
                            compact
                            label={"Theme: "}
                            value={theme}
                            onChange={setTheme as any}
                            options={[
                                { label: "Dark", value: "dark" },
                                { label: "Light", value: "light" },
                            ]}
                        />
                    </VanillaUI.EditorTabs.List>

                    <VanillaUI.EditorTabs.Content value="Components">
                        <ComponentsTab />
                    </VanillaUI.EditorTabs.Content>
                    <VanillaUI.EditorTabs.Content value="Forms">
                        <VanillaUI.SchemaForm instance={formValue} onChange={setFormValue} schema={schema} />
                    </VanillaUI.EditorTabs.Content>
                    <VanillaUI.EditorTabs.Content value="Pagers">
                        <Pagers />
                    </VanillaUI.EditorTabs.Content>
                </VanillaUI.EditorTabs.Root>
            </VanillaUI.Gutters>
        </VanillaUI.Theme>
    );
}

function ComponentsTab() {
    const [showModal, setShowModal] = useState(false);
    const [tokens, setTokens] = useState<string[]>(["Token 1", "Token 2", "Token 3", "Token 4"]);
    const toast = VanillaUI.useToast();
    return (
        <>
            <h2>Buttons</h2>
            <VanillaUI.Row gap={16} wrap={true} style={{ padding: "16px 0" }}>
                <VanillaUI.Button>Standard Button</VanillaUI.Button>
                <VanillaUI.Button buttonType="primary">Primary Button</VanillaUI.Button>
                <VanillaUI.Button buttonType="text">Text</VanillaUI.Button>
                <VanillaUI.Button buttonType="textPrimary">Text Primary</VanillaUI.Button>
                <VanillaUI.Button buttonType="input">Input Primary</VanillaUI.Button>
            </VanillaUI.Row>
            <h2>Meta Items</h2>
            <VanillaUI.Meta.Root>
                <VanillaUI.Meta.Item>Meta Item</VanillaUI.Meta.Item>
                <VanillaUI.Meta.Link to={"#hash-url"}>Meta Item Link</VanillaUI.Meta.Link>
                <VanillaUI.Meta.Tag tagPreset={"colored"}>Meta Item Tag</VanillaUI.Meta.Tag>
                <VanillaUI.Meta.Item>
                    Date: <VanillaUI.DateTime date={new Date()}></VanillaUI.DateTime>
                </VanillaUI.Meta.Item>
            </VanillaUI.Meta.Root>
            <VanillaUI.Row gap={6} style={{ padding: "16px 0" }}>
                {tokens.map((token, i) => (
                    <VanillaUI.TokenItem
                        key={i}
                        onRemove={() => {
                            setTokens(tokens.filter((_, j) => i !== j));
                        }}
                    >
                        {token}
                    </VanillaUI.TokenItem>
                ))}
                <VanillaUI.TokenItem>Not Deletable</VanillaUI.TokenItem>
            </VanillaUI.Row>

            <h2>Popovers</h2>
            <VanillaUI.Row gap={16} wrap={true} style={{ padding: "16px 0" }}>
                <VanillaUI.DropDown buttonContents={"Open DropDown"} buttonType={"input"}>
                    <VanillaUI.DropDown.ItemButton onClick={() => {}}>Item Button</VanillaUI.DropDown.ItemButton>
                    <VanillaUI.DropDown.ItemLink to={"#hash-url"}>Item Link</VanillaUI.DropDown.ItemLink>
                    <VanillaUI.DropDown.ItemSeparator />
                    <VanillaUI.DropDown.ItemLink to={"#hash-url"}>Item Link</VanillaUI.DropDown.ItemLink>
                    <VanillaUI.DropDown.Section title={"Section Title"}>
                        <VanillaUI.ToolTip label={"Hello tooltip"}>
                            <div>
                                <VanillaUI.DropDown.ItemButton onClick={() => {}}>
                                    I have a tooltip
                                </VanillaUI.DropDown.ItemButton>
                            </div>
                        </VanillaUI.ToolTip>
                        <VanillaUI.DropDown.ItemLink to={"#hash-url"}>Item Link</VanillaUI.DropDown.ItemLink>
                    </VanillaUI.DropDown.Section>
                </VanillaUI.DropDown>
                <VanillaUI.Button onClick={() => setShowModal(true)}>
                    Open Modal
                    {showModal && (
                        <VanillaUI.Modal.Framed
                            title={"This is a modal"}
                            padding={"all"}
                            onClose={() => setShowModal(false)}
                            onFormSubmit={() => {
                                toast.addToast({ body: "Form submitted!", autoDismiss: true });
                                // Causes the frame to be wrapped in a form.
                                setShowModal(false);
                            }}
                            footer={
                                <>
                                    <VanillaUI.Button buttonType={"text"} onClick={() => setShowModal(false)}>
                                        Cancel
                                    </VanillaUI.Button>
                                    <VanillaUI.Button buttonType="textPrimary" type={"submit"}>
                                        Save
                                    </VanillaUI.Button>
                                </>
                            }
                        >
                            Hello modal contents
                        </VanillaUI.Modal.Framed>
                    )}
                </VanillaUI.Button>
                <VanillaUI.Button
                    onClick={() => {
                        toast.addToast({ body: "Hello toast!", dismissible: true });
                    }}
                >
                    Create Toast
                </VanillaUI.Button>
                <VanillaUI.ToolTip label={"Hello tooltip"}>
                    <VanillaUI.Button>I have a tooltip</VanillaUI.Button>
                </VanillaUI.ToolTip>
            </VanillaUI.Row>
        </>
    );
}

function Pagers() {
    const [currentPage, setCurrentPage] = useState(1);

    return (
        <>
            <h2>Numbered</h2>
            <h3>Standard</h3>
            <VanillaUI.NumberedPager
                currentPage={currentPage}
                onChange={setCurrentPage}
                totalResults={500}
                pageLimit={10}
            />
            <h3>No Next Button</h3>
            <VanillaUI.NumberedPager
                showNextButton={false}
                currentPage={currentPage}
                onChange={setCurrentPage}
                totalResults={500}
                pageLimit={10}
            />
            <h3>Range Only</h3>
            <VanillaUI.NumberedPager
                rangeOnly={true}
                currentPage={currentPage}
                onChange={setCurrentPage}
                totalResults={500}
                pageLimit={10}
            />
            <h3>Standard (mobile)</h3>
            <VanillaUI.NumberedPager
                isMobile
                currentPage={currentPage}
                onChange={setCurrentPage}
                totalResults={500}
                pageLimit={10}
            />
        </>
    );
}

const MOCK_SIMPLE_LIST: VanillaUI.Select.Option[] = [
    { value: "apple", label: "Apple" },
    { value: "orange", label: "Orange" },
    { value: "banana", label: "Banana" },
    { value: "grape", label: "Grape" },
    { value: "strawberry", label: "Strawberry" },
    { value: "kiwi", label: "Kiwi" },
    { value: "watermelon", label: "Watermelon" },
];

const STORY_COUNTRY_LOOKUP: VanillaUI.Select.OptionLookup = {
    searchUrl: "https://restcountries.com/v3.1/name/%s?fields=name,region,subregion",
    singleUrl: "https://restcountries.com/v3.1/name/%s?fullText=true&fields=name,region,subregion",
    defaultListUrl: "https://restcountries.com/v3.1/all?fields=name,region,subregion",
    labelKey: "name.official",
    valueKey: "name.common",
};

const schema = VanillaUI.SchemaFormBuilder.create()
    .subHeading("Hello subheading")
    .toggle("toggle", "This is a toggle", "This is a toggle description.")
    .checkBoxRight(
        "check1",
        "This checkbox aligns to the right",
        "This is ideal for scenarios where you would want a toggle but your form has a submit",
    )
    .textBox("textWithNested", "Text Box", "This is a text box")
    .withoutBorder()
    .checkBox("check2", "This checkbox is nested.")
    .asNested()
    .checkBox("check3", "Check with description & tooltip")
    .withLabelType("none")
    .withDescription("Description here")
    .withTooltip("Hello tooltip")
    .radioGroup("radioGroup", "Radio Group", "Radio groups allow selecting one options", [
        {
            label: "Option 1",
            value: "1",
        },
        {
            label: "Option 2",
            description: "This one has a description",
            value: "2",
        },
        {
            label: "Option 3",
            value: "3",
            tooltip: "This one has a tooltip",
        },
    ])
    .withDefault("1")
    .radioPicker("radioPicker", "Radio Picker", "Radio pickers are like a group but place everything in a picker.", [
        {
            label: "Option 1",
            value: "1",
        },
        {
            label: "Option 2",
            description: "This one has a description",
            value: "2",
        },
        {
            label: "Option 3",
            value: "3",
            tooltip: "This one has a tooltip",
        },
    ])
    .withDefault("1")
    .subHeading("Text Inputs")
    .textBox("normalText", "Normal Text Box", "This is a normal text box")
    .textArea("textarea", "Multiline Text Box", "This is a multiline text box")
    .password("password", "Password", "This is a password box")
    .ratio("ratio", "Ratio", "This one is a number as a denominator of 1.")
    .currency("currency", "Currency", "This one is form inputting currency.")
    .subHeading("Dates")
    .datePicker("singleDate", "Single Date", "This is a single date picker.")
    .dateRange(
        "dateRange",
        "Date Range",
        "This is a date range picker. Notably the output is an object with `start` and `end` properties.",
    )
    .timeDuration("timeDuration", "Time Duration", "This is a time duration picker.")
    .subHeading("Selects")
    .selectStatic("selectSelect", "Select", "This is a select that holds a single value.", MOCK_SIMPLE_LIST)
    .selectStatic(
        "selectMulti",
        "Select (Multiple)",
        "This is a select that holds multiple values.",
        MOCK_SIMPLE_LIST,
        true,
    )
    .selectLookup("selectSingleLookup", "Select Lookup", "This is a select that looks up values.", STORY_COUNTRY_LOOKUP)

    .selectLookup(
        "selectMultiLookup",
        "Select Lookup (Multiple)",
        "This is a select that looks up values.",
        STORY_COUNTRY_LOOKUP,
        true,
    )
    .getSchema();

export default App;
