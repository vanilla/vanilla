/*
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

module.exports = {
    convertToDashedCase: function (str) {
        if(str === undefined){
            console.error('Execute installation command followed by the theme-key and "Theme Name"');
            return;
        }
        return str.toLowerCase().replace(/-/g, '');
    },
    convertToPascalCase: function (str) {
        if(str === undefined){
            console.error('Execute installation command followed by the theme-key and "Theme Name"');
            return;
        }
        var splitStr = str.toLowerCase().split('-');
        for (var i = 0; i < splitStr.length; i++) {
            // assign it back to the array
            splitStr[i] = splitStr[i].charAt(0).toUpperCase() + splitStr[i].substring(1);
        }
        //remove special characters and return the joined string
        splitStr = splitStr.join('');
        return splitStr.replace(/[^\w\s]/gi, '');
    },
    validateArgs: function (themeKey, themeName) {
        var valid = true;

        if(themeKey === undefined || themeName === undefined){
          valid = false;
        }
        return valid;
    }
};

