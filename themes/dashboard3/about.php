<?php

$ThemeInfo['dashboard3'] = array(
    'Name'          => "Dashboard v3",
    'Description'   => "A new dashboard design for Vanilla.",
    'Version'       => '1.0.0',
    'Author'        => "Becky Van Bussel",
    'AuthorEmail'   => 'beckyvanbussel@gmail.com',
    'AuthorUrl'     => 'https://vanillaforums.com',
    'License'       => 'MIT',
    'ControlStyle'  => 'bootstrap',
    'Options' => array(
        'Description' => 'This theme has alternative colour schemes for each of the sites.',
        'Styles' => array(
            'TheBump' => '%s_thebump',
            'TheKnot' => '%s_theknot',
            'WeddingChannel' => '%s_weddingchannel',
            'TheNest' => '%s_thenest'
        ),
        'Text' => array(
            'Custom&nbsp;Text' => array(
                'Description' => 'Custom text to be inserted in the theme.',
                'Type' => 'textbox'
            )
        )
    )
);
