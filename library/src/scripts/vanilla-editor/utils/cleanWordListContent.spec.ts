/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import cleanWordListContent, { isWordListContent } from "@library/vanilla-editor/utils/cleanWordListContent";

describe("wordListConverter", () => {
    describe("isWordListContent", () => {
        it("detects Word list content with MsoListParagraph class", () => {
            const html = '<p class="MsoListParagraph">Test item</p>';
            expect(isWordListContent(html)).toBe(true);
        });

        it("detects Word list content with mso-list style", () => {
            const html = '<p style="mso-list: l0 level1 lfo1">Test item</p>';
            expect(isWordListContent(html)).toBe(true);
        });

        it("returns false for non-Word list content", () => {
            const html = "<ul><li>Regular list item</li></ul>";
            expect(isWordListContent(html)).toBe(false);
        });
    });

    describe("cleanWordListContent", () => {
        it("returns original HTML if no Word list content is found", () => {
            const html = "<p>Regular content</p>";
            expect(cleanWordListContent(html)).toBe(html);
        });

        it("converts Word unordered list to HTML list", () => {
            const wordHtml = `<body><!--StartFragment--><p class="MsoNormal">
                <span style="font-family:&quot;Lucida Sans&quot;,sans-serif">My test</span></p>
                <p class="MsoListParagraphCxSpFirst" style="text-indent:-.25in;mso-list:l0 level1 lfo1">
                <!--[if !supportLists]--><span style="font-family:Symbol;mso-fareast-font-family:Symbol;mso-bidi-font-family:
                Symbol"><span style="mso-list:Ignore">·<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">Hello</span></p>
                <p class="MsoListParagraphCxSpLast" style="text-indent:-.25in;mso-list:l0 level1 lfo1">
                <!--[if !supportLists]--><span style="font-family:Symbol;mso-fareast-font-family:Symbol;mso-bidi-font-family:Symbol">
                <span style="mso-list:Ignore">·<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">world</span></p><!--EndFragment--></body>`;

            const result = cleanWordListContent(wordHtml);

            // Create a temporary element to parse and check the HTML structure
            const container = document.createElement("div");
            container.innerHTML = result;

            const ul = container.querySelector("ul");
            const items = container.querySelectorAll("li");

            expect(ul).toBeTruthy();
            expect(items).toHaveLength(2);
            expect(items[0].textContent?.trim()).toBe("Hello");
            expect(items[1].textContent?.trim()).toBe("world");
        });

        it("converts Word ordered list to HTML list", () => {
            const wordHtml = `<body><!--StartFragment--><p class="FirstParagraph"><b>Ordered List:</b> </p><p class="FirstParagraph" style="margin-left:.5in;text-indent:-.25in;mso-list:
                l0 level1 lfo1"><!--[if !supportLists]--><b><span style="mso-bidi-font-family:Aptos;
                mso-bidi-theme-font:minor-latin">
                <span style="mso-list:Ignore">1.<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span></b>
                <!--[endif]--><b>First</b></p><p class="MsoBodyText" style="margin-left:.5in;text-indent:-.25in;mso-list:l0 level1 lfo1">
                <!--[if !supportLists]--><span style="mso-bidi-font-family:Aptos;mso-bidi-theme-font:minor-latin">
                <span style="mso-list:Ignore">2.<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><b>Second</b></p><p class="MsoBodyText" style="margin-left:.5in;text-indent:-.25in;mso-list:l0 level1 lfo1">
                <!--[if !supportLists]--><b><span style="mso-bidi-font-family:Aptos;mso-bidi-theme-font:minor-latin">
                <span style="mso-list:Ignore">3.<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span></b>
                <!--[endif]--><b>Third</b></p><!--EndFragment--></body>`;

            const result = cleanWordListContent(wordHtml);

            const container = document.createElement("div");
            container.innerHTML = result;

            const ol = container.querySelector("ol");
            const items = container.querySelectorAll("li");

            expect(ol).toBeTruthy();
            expect(items).toHaveLength(3);
            expect(items[0].textContent?.trim()).toBe("First");
            expect(items[1].textContent?.trim()).toBe("Second");
            expect(items[2].textContent?.trim()).toBe("Third");
        });

        it("preserves non-list content", () => {
            const wordHtml = `<body><!--StartFragment--><p class="MsoNormal">
                <span style="font-family:&quot;Lucida Sans&quot;,sans-serif">Some paragraph text</span></p>
                <p class="MsoListParagraphCxSpFirst" style="text-indent:-.25in;mso-list:l0 level1 lfo1"><!--[if !supportLists]-->
                <span style="font-family:Symbol;mso-fareast-font-family:Symbol;mso-bidi-font-family:Symbol">
                <span style="mso-list:Ignore">·<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">Hello</span></p>
                <p class="MsoListParagraphCxSpLast" style="text-indent:-.25in;mso-list:l0 level1 lfo1"><!--[if !supportLists]-->
                <span style="font-family:Symbol;mso-fareast-font-family:Symbol;mso-bidi-font-family:Symbol">
                <span style="mso-list:Ignore">·<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">World</span></p><p class="MsoNormal">
                <span style="font-family:&quot;Lucida Sans&quot;,sans-serif">More text after list</span></p><!--EndFragment--></body>`;

            const result = cleanWordListContent(wordHtml);

            const container = document.createElement("div");
            container.innerHTML = result;

            const paragraphs = container.querySelectorAll("p");
            const ul = container.querySelector("ul");
            const items = container.querySelectorAll("li");

            expect(paragraphs).toHaveLength(2);
            expect(ul).toBeTruthy();
            expect(items).toHaveLength(2);
            expect(paragraphs[0].textContent?.trim()).toBe("Some paragraph text");
            expect(items[0].textContent?.trim()).toBe("Hello");
            expect(items[1].textContent?.trim()).toBe("World");
            expect(paragraphs[1].textContent?.trim()).toBe("More text after list");
        });

        it("preserves links in list items", () => {
            const wordHtml = `<body><!--StartFragment--><p class="MsoNormal">
            <span style="font-family:&quot;Lucida Sans&quot;,sans-serif">Text before</span></p>
            <p class="MsoListParagraphCxSpFirst" style="text-indent:-.25in;mso-list:l0 level1 lfo1">
            <!--[if !supportLists]--><span style="font-family:Symbol;mso-fareast-font-family:Symbol;mso-bidi-font-family:Symbol">
            <span style="mso-list:Ignore">·<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
            <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">Item one</span></p>
            <p class="MsoListParagraphCxSpLast" style="text-indent:-.25in;mso-list:l0 level1 lfo1"><!--[if !supportLists]-->
            <span style="font-family:Symbol;mso-fareast-font-family:Symbol;mso-bidi-font-family:
            Symbol"><span style="mso-list:Ignore">·<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
            <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif"><a href="https://www.higherlogic.com/">Second item as link</a></span></p>
            // <p class="MsoNormal"></p><!--EndFragment--></body>`;

            const result = cleanWordListContent(wordHtml);

            const container = document.createElement("div");
            container.innerHTML = result;

            const items = container.querySelectorAll("li");
            expect(items[0].textContent?.trim()).toBe("Item one");
            expect(items[1].querySelector("a")).toBeTruthy();
            expect(items[1].querySelector("a")?.getAttribute("href")).toBe("https://www.higherlogic.com/");
            expect(items[1].querySelector("a")?.textContent?.trim()).toBe("Second item as link");
        });

        it("preserves numbers in list items", () => {
            const wordHtml = `<body><!--StartFragment--><p class="MsoListParagraphCxSpFirst" style="text-indent:-.25in;mso-list:l0 level1 lfo1"><!--[if !supportLists]-->
                <span style="font-family:&quot;Lucida Sans&quot;,sans-serif;mso-fareast-font-family:&quot;Lucida Sans&quot;;
                mso-bidi-font-family:&quot;Lucida Sans&quot;">
                <span style="mso-list:Ignore">-<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">Hey</span></p><p class="MsoListParagraphCxSpMiddle" style="text-indent:-.25in;mso-list:l0 level1 lfo1">
                <!--[if !supportLists]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif;mso-fareast-font-family:&quot;Lucida Sans&quot;;
                mso-bidi-font-family:&quot;Lucida Sans&quot;"><span style="mso-list:Ignore">-<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">123</span></p><p class="MsoListParagraphCxSpMiddle" style="text-indent:-.25in;mso-list:l0 level1 lfo1"><!--[if !supportLists]-->
                <span style="font-family:&quot;Lucida Sans&quot;,sans-serif;mso-fareast-font-family:&quot;Lucida Sans&quot;;
                mso-bidi-font-family:&quot;Lucida Sans&quot;"><span style="mso-list:Ignore">-<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">Hi 123</span></p>
                <p class="MsoListParagraphCxSpLast" style="text-indent:-.25in;mso-list:l0 level1 lfo1"><!--[if !supportLists]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif;mso-fareast-font-family:&quot;Lucida Sans&quot;;
                mso-bidi-font-family:&quot;Lucida Sans&quot;"><span style="mso-list:Ignore">-<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">123 hi</span></p><!--EndFragment--></body>`;

            const result = cleanWordListContent(wordHtml);

            const container = document.createElement("div");
            container.innerHTML = result;

            const items = container.querySelectorAll("li");
            expect(items[0].textContent?.trim()).toBe("Hey");
            expect(items[1].textContent?.trim()).toBe("123");
            expect(items[2].textContent?.trim()).toBe("Hi 123");
            expect(items[3].textContent?.trim()).toBe("123 hi");
        });

        it("preserves nested ordered lists", () => {
            const wordHtml = `<body><!--StartFragment--><p class="MsoListParagraphCxSpFirst" style="text-indent:-.25in;mso-list:l0 level1 lfo1"><!--[if !supportLists]-->
                <span style="font-family:&quot;Lucida Sans&quot;,sans-serif;mso-fareast-font-family:&quot;Lucida Sans&quot;;
                mso-bidi-font-family:&quot;Lucida Sans&quot;"><span style="mso-list:Ignore">1.<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">First item</span></p><p class="MsoListParagraphCxSpMiddle" style="margin-left:1.0in;mso-add-space:
                auto;text-indent:-.25in;mso-list:l0 level2 lfo1"><!--[if !supportLists]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif;mso-fareast-font-family:&quot;Lucida Sans&quot;;
                mso-bidi-font-family:&quot;Lucida Sans&quot;"><span style="mso-list:Ignore">a.<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp; </span></span></span>
                <!--[endif]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif">Nested item 1</span></p><p class="MsoListParagraphCxSpMiddle" style="margin-left:1.0in;mso-add-space:
                auto;text-indent:-.25in;mso-list:l0 level2 lfo1"><!--[if !supportLists]--><span style="font-family:&quot;Lucida Sans&quot;,sans-serif;mso-fareast-font-family:&quot;Lucida Sans&quot;;
                mso-bidi-font-family:&quot;Lucida Sans&quot;"><span style="mso-list:Ignore">b.<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]-->
                <span style="font-family:&quot;Lucida Sans&quot;,sans-serif">Nested item 2</span></p><p class="MsoListParagraphCxSpLast" style="text-indent:-.25in;mso-list:l0 level1 lfo1"><!--[if !supportLists]-->
                <span style="font-family:&quot;Lucida Sans&quot;,sans-serif;mso-fareast-font-family:&quot;Lucida Sans&quot;;
                mso-bidi-font-family:&quot;Lucida Sans&quot;"><span style="mso-list:Ignore">2.<span style="font:7.0pt &quot;Times New Roman&quot;">&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]-->
                <span style="font-family:&quot;Lucida Sans&quot;,sans-serif">Second top level item</span></p><!--EndFragment--></body>`;

            const result = cleanWordListContent(wordHtml);

            const container = document.createElement("div");
            container.innerHTML = result;

            const items = container.querySelectorAll("li");

            const nestedItems = items[0].querySelectorAll("li");
            expect(nestedItems).toHaveLength(2);
            expect(nestedItems[0].textContent?.trim()).toBe("Nested item 1");
            expect(nestedItems[1].textContent?.trim()).toBe("Nested item 2");
        });
    });
});
