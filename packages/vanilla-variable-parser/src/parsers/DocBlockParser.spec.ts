import { DocBlockParser } from "./DocBlockParser";
import { Validator } from "./Validator";

describe("DocBlockParser", () => {
    it("can parse an attribute", () => {
        const parser = DocBlockParser.create().addAttribute("title");

        const input = `
        /**
         * @title My Title
         */
        `;

        expect(parser.parse(input)?.value).toEqual({
            title: "My Title",
        });
    });

    it("can parse multiline attribute", () => {
        const parser = DocBlockParser.create()
            .addAttribute("desc1", { isMultiline: true })
            .addAttribute("desc2", { isMultiline: true })
            .addAttribute("desc3")
            .addAttribute("desc4", { isMultiline: true });

        const input = `
        /**
         * @desc1 Line 1
         * Line 2
         * Line 3
         * @desc2 Line 4
         * Line 5
         * @desc3 Line 6
         * Line Not
         * @desc4 Line 8
         */
        `;

        expect(parser.parse(input)?.value).toEqual({
            desc1: "Line 1\nLine 2\nLine 3",
            desc2: "Line 4\nLine 5",
            desc3: "Line 6",
            desc4: "Line 8",
        });
    });

    it("can parse with a leadingAttribute", () => {
        const parser = DocBlockParser.create().setLeadingAttribute("thing").addAttribute("foo");

        const input = `
        /**
         * Before
         * @foo Before
         * @thing ThingName
         * @foo After
         */
        `;

        expect(parser.parse(input)?.value).toEqual({
            thing: "ThingName",
            foo: "After",
        });

        const beforeLeading = `
        /**
         * Before
         * @foo Missing
         * @thing j
         */
        `;

        expect(parser.parse(beforeLeading)?.value).toEqual({ thing: "j" });

        const missingLeading = `
        /**
         * Before
         * @foo Missing
         */
        `;

        expect(parser.parse(missingLeading)).toEqual(null);
    });

    it("handles repeats of the same value", () => {
        const parser = DocBlockParser.create().addAttribute("foo");

        const input = `
        /**
        * @foo 1
        * @foo 2
        */
        `;

        expect(parser.parse(input)?.value).toEqual({ foo: "2" });
    });

    it("requires a space at the end of attributes", () => {
        const parser = DocBlockParser.create().addAttribute("thing").addAttribute("thingPrefix");

        const input = `
        /**
         * @thing foo
         * @thingPrefix bar
         */
        `;

        expect(parser.parse(input)?.value).toEqual({
            thing: "foo",
            thingPrefix: "bar",
        });
    });

    it("can validate successful types", () => {
        const parser = DocBlockParser.create().addIntAttribute("num").addArrayAttribute("arr").addAttribute("type", {
            validator: Validator.validateJsonSchemaType,
        });

        const input = `
        /**
         * @num 421
         * @arr [1, 5, "4a"]
         * @type string|null
         */
        `;

        expect(parser.parse(input)?.value).toEqual({
            num: 421,
            arr: [1, 5, "4a"],
            type: ["string", "null"],
        });
    });
});
