<?php

/////////////////////////////////////////////////////////////////////
///// Game options description
/////

$game_options = array(

    100 => array(
        'name' => totranslate('Difficulty level'),
        'values' => array(
            1 => array('name' => totranslate('Apprentice Wizard'), 'tmdisplay' => totranslate('Apprentice Wizard')),
            2 => array('name' => totranslate('Magician Level'), 'tmdisplay' => totranslate('Magician Level'), 'nobeginner' => true),
            3 => array('name' => totranslate('Archmage Level'), 'tmdisplay' => totranslate('Archmage Level'), 'nobeginner' => true),
            4 => array('name' => totranslate('Archmage Level + Enchanted Kingdom expansion'), 'tmdisplay' => totranslate('Archmage Level + Enchanted Kingdom'), 'nobeginner' => true),
            5 => array('name' => totranslate('Archmage Level + Path of Destiny expansion'), 'tmdisplay' => totranslate('Archmage Level + Path of Destiny'), 'nobeginner' => true),
            6 => array('name' => totranslate('Archmage Level + Enchanted Kingdom & Path of Destiny expansions'), 'tmdisplay' => totranslate('Archmage Level + Enchanted Kingdom + Path of Destiny'), 'nobeginner' => true),
            7 => array('name' => totranslate('Official Tournament authorized cards'), 'tmdisplay' => totranslate('Official Tournament authorized cards'), 'nobeginner' => true),
            8 => array('name' => totranslate('Archmage Level + All expansions + Promo cards'), 'tmdisplay' => totranslate('Archmage Level + All expansions + Promo cards'), 'nobeginner' => true),
            9 => array('name' => totranslate('Official 2022 Tournament authorized cards'), 'tmdisplay' => totranslate('Official 2022 Tournament authorized cards'), 'nobeginner' => true)
        )
    )/*,
    101 => array(
                'name' => totranslate('Cards version'),
                'values' => array(
                            1 => array( 'name' => totranslate('Second edition') ),
                            0 => array( 'name' => totranslate('First edition') )
                        )
            ),*/
);

$game_preferences = array(
    '1' => array(
        'name' => totranslate('Show cards art in tooltips'),
        'needReload' => true,
        'values' => array(
            1 => array('name' => totranslate('Yes')),
            2 => array('name' => totranslate('No'), 'cssPref' => 'seasons_no_art_cards')
        )
    ),
    '2' => array(
        'name' => totranslate('Compact player board'),
        'needReload' => true,
        'values' => array(
            1 => array('name' => totranslate('Yes'), 'cssPref' => 'seasons_compact_player_board'),
            2 => array('name' => totranslate('No'))
        ),
        'default' => 2,
    ),
);
