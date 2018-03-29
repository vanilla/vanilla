<?php
echo heading($this->data('Title').' <small class="meta">'.url('/api/v2', true).'</small>');

if (!function_exists('ul')) {
    function ul(array $items) {
        $r = '<ul><li>'.implode('</li><li>', $items).'</li></ul>';
        return $r;
    }
}

helpAsset(t('About the API'), t('This page lists the endpoints of your API.', 'This page lists the endpoints of your API. Click endpoints for more information. You can make live calls to the API from this page or externally using an access token.'));

helpAsset(t('See Also'), ul([
    anchor(t('Personal Access Tokens'), '/profile/tokens')
]));

helpAsset(t('Need More Help?'), ul([
    anchor(t('API Overview'), 'http://docs.vanillaforums.com/apiv2/', '', ['target' => '_blank']),
    anchor(t('Authentication'), 'http://docs.vanillaforums.com/apiv2/authentication', '', ['target' => '_blank'])
]));

?>

<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="position:absolute;width:0;height:0">
    <defs>
        <symbol viewBox="0 0 20 20" id="unlocked">
            <path d="M15.8 8H14V5.6C14 2.703 12.665 1 10 1 7.334 1 6 2.703 6 5.6V6h2v-.801C8 3.754 8.797 3 10 3c1.203 0 2 .754 2 2.199V8H4c-.553 0-1 .646-1 1.199V17c0 .549.428 1.139.951 1.307l1.197.387C5.672 18.861 6.55 19 7.1 19h5.8c.549 0 1.428-.139 1.951-.307l1.196-.387c.524-.167.953-.757.953-1.306V9.199C17 8.646 16.352 8 15.8 8z"></path>
        </symbol>

        <symbol viewBox="0 0 20 20" id="locked">
            <path d="M15.8 8H14V5.6C14 2.703 12.665 1 10 1 7.334 1 6 2.703 6 5.6V8H4c-.553 0-1 .646-1 1.199V17c0 .549.428 1.139.951 1.307l1.197.387C5.672 18.861 6.55 19 7.1 19h5.8c.549 0 1.428-.139 1.951-.307l1.196-.387c.524-.167.953-.757.953-1.306V9.199C17 8.646 16.352 8 15.8 8zM12 8H8V5.199C8 3.754 8.797 3 10 3c1.203 0 2 .754 2 2.199V8z"/>
        </symbol>

        <symbol viewBox="0 0 20 20" id="close">
            <path d="M14.348 14.849c-.469.469-1.229.469-1.697 0L10 11.819l-2.651 3.029c-.469.469-1.229.469-1.697 0-.469-.469-.469-1.229 0-1.697l2.758-3.15-2.759-3.152c-.469-.469-.469-1.228 0-1.697.469-.469 1.228-.469 1.697 0L10 8.183l2.651-3.031c.469-.469 1.228-.469 1.697 0 .469.469.469 1.229 0 1.697l-2.758 3.152 2.758 3.15c.469.469.469 1.229 0 1.698z"/>
        </symbol>

        <symbol viewBox="2 -1 16 16" id="large-arrow">
            <path fill="currentColor" d="M6.23983621,12.7814012 C5.93223788,12.4806996 5.908099,12.0620625 6.23983621,11.6944618 L8.82407598,8.99918314 L6.23983621,6.30390451 C5.908099,5.93630382 5.93223788,5.51697695 6.23983621,5.21834449 C6.54674486,4.91764299 7.06538598,4.93695409 7.35367321,5.21834449 C7.64196043,5.49835553 10.4586231,8.45640313 10.4586231,8.45640313 C10.6124223,8.6060642 10.6896667,8.80262367 10.6896667,8.99918314 C10.6896667,9.19574261 10.6124223,9.39230208 10.4586231,9.54334251 C10.4586231,9.54334251 7.64196043,12.5000108 7.35367321,12.7814012 C7.06538598,13.0634812 6.54674486,13.0821027 6.23983621,12.7814012 L6.23983621,12.7814012 L6.23983621,12.7814012 Z"/>
        </symbol>

        <symbol viewBox="2 -1 16 16" id="large-arrow-down">
            <path fill="currentColor" d="M4.21859885,7.23983621 C4.51930035,6.93223788 4.93793754,6.908099 5.30553823,7.23983621 L8.00081686,9.82407598 L10.6960955,7.23983621 C11.0636962,6.908099 11.483023,6.93223788 11.7816555,7.23983621 C12.082357,7.54674486 12.0630459,8.06538598 11.7816555,8.35367321 C11.5016445,8.64196043 8.54359687,11.4586231 8.54359687,11.4586231 C8.3939358,11.6124223 8.19737633,11.6896667 8.00081686,11.6896667 C7.80425739,11.6896667 7.60769792,11.6124223 7.45665749,11.4586231 C7.45665749,11.4586231 4.49998925,8.64196043 4.21859885,8.35367321 C3.93651877,8.06538598 3.91789734,7.54674486 4.21859885,7.23983621 L4.21859885,7.23983621 Z"/>
        </symbol>

        <symbol viewBox="0 0 24 24" id="jump-to">
            <path d="M19 7v4H5.83l3.58-3.59L8 6l-6 6 6 6 1.41-1.41L5.83 13H21V7z"/>
        </symbol>

        <symbol viewBox="0 0 24 24" id="expand">
            <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
        </symbol>

    </defs>
</svg>

<div id="swagger-ui"></div>

<?php
foreach ($this->data('js') as $js) {
    echo '<script src="'.htmlspecialchars($js).'" type="text/javascript"></script>'."\n";
}