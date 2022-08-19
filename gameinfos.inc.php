<?php

$gameinfos = array( 


// Game designer (or game designers, separated by commas)
'designer' => 'Régis Bonnessée',       

// Game artist (or game artists, separated by commas)
'artist' => 'Xavier Gueniffey Durin',         

// Year of FIRST publication of this game. Can be negative.
'year' => 2012,                 

// Game publisher
'publisher' => 'Libellud',                     

// Url of game publisher website
'publisher_website' => 'http://www.libellud.com/',   

// Board Game Geek ID of the publisher
'publisher_bgg_id' => 9051,

// Board game geek if of the game
'bgg_id' => 108745,


// Players configuration that can be played (ex: 2 to 4 players)
'players' => array( 2,3,4 ),    

// Suggest players to play with this number of players. Must be null if there is no such advice, or if there is only one possible player configuration.
'suggest_player_number' => null,

// Discourage players to play with this number of players. Must be null if there is no such advice.
'not_recommend_player_number' => array( ),

'tie_breaker_description' => totranslate( "Cards summoned" ),


// Estimated game duration, in minutes (used only for the launch, afterward the real duration is computed)
'estimated_duration' => 26,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = fast)
'fast_additional_time' => 75,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = medium)
'medium_additional_time' => 110,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = slow)
'slow_additional_time' => 130,           


// Game is "beta". A game MUST set is_beta=1 when published on BGA for the first time, and must remains like this until all bugs are fixed.
'is_beta' => 1,                     

// Is this game cooperative (all players wins together or loose together)
'is_coop' => 0, 


// Complexity of the game, from 0 (extremely simple) to 5 (extremely complex)
'complexity' => 3,    

// Luck of the game, from 0 (absolutely no luck in this game) to 5 (totally luck driven)
'luck' => 2,    

// Strategy of the game, from 0 (no strategy can be setup) to 5 (totally based on strategy)
'strategy' => 4,    

// Diplomacy of the game, from 0 (no interaction in this game) to 5 (totally based on interaction and discussion between players)
'diplomacy' => 1,    

// Favorite colors support : if set to "true", support attribution of favorite colors based on player's preferences (see reattributeColorsBasedOnPreferences PHP method)
'favorite_colors_support' => true,


// Game interface width range (pixels)
// Note: game interface = space on the left side, without the column on the right
'game_interface_width' => array(

    // Minimum width
    //  default: 740
    //  maximum possible value: 740 (ie: your game interface should fit with a 740px width (correspond to a 1024px screen)
    //  minimum possible value: 320 (the lowest value you specify, the better the display is on mobile)
    'min' => 550,

    // Maximum width
    //  default: null (ie: no limit, the game interface is as big as the player's screen allows it).
    //  maximum possible value: unlimited
    //  minimum possible value: 740
    'max' => null
),


// Game presentation
// Short game presentation text (6-9 lines) that will appear on the game description page, structured as an array of paragraphs.
// Each paragraph must be wrapped with totranslate() for translation and should not contain html (plain text without formatting).
// A good length for this text is between 100 and 150 words (about 6 to 9 lines on a standard display)
// Example:
// 'presentation' => array(
//    totranslate("This wonderful game is about geometric shapes!"),
//    totranslate("It was awarded best triangle game of the year in 2005 and nominated for the Spiel des Jahres."),
//    ...
// ),
'presentation' => array(
    totranslate("Seasons is a tactical card game based on draft, combo and resource management."),
    totranslate("With slight randomness, Seasons enables players to manage their hand to combine the powers of their cards."),
    totranslate("Gather energy, summon familiars and magic items, amass enough crystals, symbols of prestige, and become the kingdom’s most illustrious mage.")
),





// Games categories
//  You can attribute any number of "tags" to your game.
//  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
'tags' => array( 4, 200, 100, 201 )
);
