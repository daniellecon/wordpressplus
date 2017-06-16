<?php
/**
 * This file help load typography automatically
 *
 * Auto add style for typography settings
 *
 * @see sovenco_typography_helper_auto_apply
 */
sovenco_typography_helper_auto_apply(
    'sovenco_typo_p', // customize setting ID
    'body, body p' // CSS selector
    /*
    array( // default value
        'font-family'     => 'Lato',
        'color'           => '#73ad21',
        'font-style'      => '300', // italic
        'font-weight'     => '700',
        'font-size'       => '18px',
        'line-height'     => '33px',
        'letter-spacing'  => '2px',
        'text-transform'  => 'lowercase',
        'text-decoration' => 'underline',
    )
    */
);

sovenco_typography_helper_auto_apply(
    'sovenco_typo_site_title', // customize setting ID
    '#page .site-branding .site-title, #page .site-branding .site-text-logo' // CSS selector
);

sovenco_typography_helper_auto_apply(
    'sovenco_typo_site_tagline', // customize setting ID
    '#page .site-branding .site-description' // CSS selector
);


sovenco_typography_helper_auto_apply(
    'sovenco_typo_menu', // customize setting ID
    '.sovenco-menu a' // CSS selector
);

sovenco_typography_helper_auto_apply(
    'sovenco_typo_heading', // customize setting ID
    'body h1, body h2, body h3, body h4, body h5, body h6,
    body .section-title-area .section-title, body .section-title-area .section-subtitle, body .hero-content-style1 h2' // CSS selector
);
