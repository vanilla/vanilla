/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

describe.only("ListBlot", () => {
    it("can be created with a simple true value");
    it("can be created with an object value");
    it("can be updated with a new list type");
    it("can be updated with a new depth value");
    it("can be updated from the old simple value to the new value type");
    it("can have its list formatting removed");
    it("nested list blots are joined together");
    it("lists can only gain depth if there are other list items before them");
    it("properly reports if nesting is possible");
    it("different lists of increasingly nested depths are joined together");
    it("different lists of the same depth do NOT get joined together");
});
