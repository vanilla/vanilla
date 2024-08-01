# Vanilla's use of Masonry

**jquery.masonry.js** has modifications to the file. Read below:

// Note, Vanilla upgraded to 1.10.2 late Jan2014. Masonry V2 was using
// an undocumented feature of jQuery, which has consequently been
// removed (event.handle). The latest version of Masonry (V3) is a
// complete rewrite, which would require a rewrite of the tile.js
// file to play nice, so instead, this simple fix keeps the plugin
// running as intended.
//
// This was modified Feb14, 2014.
//$.event.handle.apply( context, args );
`$.event.dispatch.apply( context, args );`