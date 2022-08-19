<?php
/**
**
**  Seasons material
**
**
*/

$this->energies = array(
    1 => array( 'name' => clienttranslate('air'),
                'nametr' => self::_('air') ),
    2 => array( 'name' => clienttranslate('water'),
                'nametr' => self::_('water') ),
    3 => array( 'name' => clienttranslate('fire'),
                'nametr' => self::_('fire') ),
    4 => array( 'name' => clienttranslate('earth'),
                'nametr' => self::_('earth') )
);

$this->seasons = array(
    1 => array( 'name' => clienttranslate('winter'),
                'transmutation' => array(
                    1 => 1,
                    2 => 1,
                    3 => 2,
                    4 => 3
                ) ),
    2 => array( 'name' => clienttranslate('spring'),
                'transmutation' => array(
                    1 => 2,
                    2 => 1,
                    3 => 3,
                    4 => 1
                ) ),
    3 => array( 'name' => clienttranslate('summer'),
                'transmutation' => array(
                    1 => 3,
                    2 => 2,
                    3 => 1,
                    4 => 1
                ) ),
    4 => array( 'name' => clienttranslate('fall'),
                'transmutation' => array(
                    1 => 1,
                    2 => 3,
                    3 => 1,
                    4 => 2
                ) )
);

$this->bonus_cost = array(
    0 => 0,
    1 => -5,
    2 => -12,
    3 => -20
);

$this->prebuild_decks = array(
    1 => array( 1,2,7,17,18,20,26,29,30 ),
    2 => array( 3,5,9,14,15,21,23,25,28 ),
    3 => array( 4,6,7,9,12,16,22,24,30 ),
    4 => array( 1,2,3,11,13,15,18,25,27 )
);

$this->card_types = array(

     1 => array(
        'name' => clienttranslate("Amulet of Air"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Increase your summoning gauge by 2."),
        'points' => 6,
        'cost' => array(
            1 => 2
        )
     ),
     2 => array(
        'name' => clienttranslate("Amulet of Fire"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Draw 4 power cards: add one to your hand and discard the rest."),
        'points' => 6,
        'cost' => array(
            3 => 2
        )
     ),
     3 => array(
        'name' => clienttranslate("Amulet of Earth"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Gain 9 crystals."),
        'points' => 6,
        'cost' => array(
            4 => 2
        )
     ),
     4 => array(
        'name' => clienttranslate("Amulet of Water"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Gain 4 energy tokens and place them on the amulet of water. You can use these tokens during the game."),
        'points' => 6,
        'cost' => array(
            2 => 2
        )
     ),
     5 => array(
        'name' => clienttranslate("Balance of Ishtar"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Discard 4 identical energy tokens. Gain 12 crystals."),
        'points' => 4,
        'cost' => array(
            0 => 6
        )
     ),
     6 => array(
        'name' => clienttranslate("Staff of Spring"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Gain 3 crystals whenever you summon a power card."),
        'points' => 9,
        'cost' => array(
            4 => 3
        )
     ),
     7 => array(
        'name' => clienttranslate("Temporal Boots"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Move the season token forward or back 1 to 3 spaces."),
        'points' => 8,
        'cost' => array(
        )
     ),
     8 => array(
        'name' => clienttranslate("Purse of Io"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Gain 1 extra crystal for each energy you transmute."),
        'points' => 6,
        'cost' => array(
            1 => 1,
            3 => 1
        )
     ),
     9 => array(
        'name' => clienttranslate("Divine Chalice"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Draw 4 power cards; summon one for free. Discard the other cards."),
        'points' => 10,
        'cost' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        )
     ),
     10 => array(
        'name' => clienttranslate("Syllas the Faithful"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Each of your opponents sacrifices a power card."),
        'points' => 14,
        'cost' => array(
            3 => 3
        )
     ),

     11 => array(
        'name' => clienttranslate("Figrim the Avaricious"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Each change of season, each of your opponents must give you 1 crystal."),
        'points' => 7,
        'cost' => array(
            0 => array( 2 => 3, 3 => 6, 4 => 9 )
        )
     ),
     12 => array(
        'name' => clienttranslate("Naria the Prophetess"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Draw as many power cards as there are players. Put one of them in your hand and give the rest out to every other player."),
        'points' => 8,
        'cost' => array(
            0 => 3
        )
     ),
     13 => array(
        'name' => clienttranslate("Wondrous Chest"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("If you have 4 or more energy tokens in your reserve at the end of a round, gain 3 crystals."),
        'points' => 4,
        'cost' => array(
            2 => 1,
            3 => 1
        )
     ),
     14 => array(
        'name' => clienttranslate("Beggar’s Horn"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("If you have 1 energy token or less in your reserve at the end of a round, gain 1 energy token."),
        'points' => 8,
        'cost' => array(
            1 => 1,
            4 => 1
        )
     ),
     15 => array(
        'name' => clienttranslate("Die of Malice"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, instead of performing the action(s) of your season die, reroll it; perform the new action(s) of the die roll and gain 2 crystals."),
        'points' => 8,
        'cost' => array(
        )
     ),
     16 => array(
        'name' => clienttranslate("Kairn the Destroyer"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Discard 1 energy: each of your opponents loses 4 crystals."),
        'points' => 9,
        'cost' => array(
            1 => 3
        )
     ),
     17 => array(
        'name' => clienttranslate("Amsug Longneck"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Each player (you included) takes 1 of their magic items in play back into their hand."),
        'points' => 8,
        'cost' => array(
            2 => 1,
            1 => 1
        )
     ),
     18 => array(
        'name' => clienttranslate("Bespelled Grimoire"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("§§ Gain 2 energy tokens. øø You can now store up to 10 energy tokens. The extra energy is stored on the Bespelled Grimoire and are considered to be part of your reserve."),
        'points' => 8,
        'cost' => array(
            2 => 1,
            4 => 1
        )
     ),
     19 => array(
        'name' => clienttranslate("Ragfield’s Helm"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("At the end of the game, if you have more power cards in play than each of your opponents, gain 20 additional crystals."),
        'points' => 10,
        'cost' => array(
            1 => 3
        )
     ),
     20 => array(
        'name' => clienttranslate("Hand of Fortune"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("The summoning cost of your power cards is reduced by 1 energy from now on (not to be reduced below 1 energy)."),
        'points' => 9,
        'cost' => array(
            4 => 1,
            3 => 1,
            1 => 1,
            0 => 3
        )
     ),
     21 => array(
        'name' => clienttranslate("Lewis Greyface"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Choose 1 of your opponents: gain exactly the same amount and type of energy tokens as that opponent has in their reserve."),
        'points' => 6,
        'cost' => array(
            3 => 1,
            1 => 1
        )
     ),
     22 => array(
        'name' => clienttranslate("Runic Cube of Eolis"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => '',
        'points' => 30,
        'cost' => array(
            0 => 20
        )
     ),
     23 => array(
        'name' => clienttranslate("Potion of Power"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Sacrifice the Potion of Power to draw 1 power card and increase your summoning gauge by 2."),
        'points' => 0,
        'cost' => array(
            3 => 2
        )
     ),
     24 => array(
        'name' => clienttranslate("Potion of Dreams"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Sacrifice the Potion of Dreams and discard all of your energy to summon a power card for free."),
        'points' => 0,
        'cost' => array(
            1 => 2
        )
     ),
     25 => array(
        'name' => clienttranslate("Potion of Knowledge"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Sacrifice the Potion of Knowledge to gain 5 energy."),
        'points' => 0,
        'cost' => array(
            2 => 2
        )
     ),
     26 => array(
        'name' => clienttranslate("Potion of Life"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Sacrifice the Potion of Life to transmute each of the energy tokens in your reserve into 4 crystals."),
        'points' => 0,
        'cost' => array(
            4 => 2
        )
     ),
     27 => array(
        'name' => clienttranslate("Hourglass of Time"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Each time the season changes, gain 1 energy token of your choice."),
        'points' => 6,
        'cost' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        )
     ),
     28 => array(
        'name' => clienttranslate("Scepter of Greatness"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Gain 3 crystals for each magic item you have in play, with the exception of the Scepter of Greatness."),
        'points' => 8,
        'cost' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        )
     ),
     29 => array(
        'name' => clienttranslate("Olaf’s Blessed Statue"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Gain 20 crystals."),
        'points' => 0,
        'cost' => array(
            2 => 3
        )
     ),
     30 => array(
        'name' => clienttranslate("Yjang’s Forgotten Vase"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("When you summon a power card, gain 1 energy token."),
        'points' => 6,
        'cost' => array(
            2 => 1,
            3 => 1,
            4 => 1
        )
     ),
     31 => array(
        'name' => clienttranslate("Elemental Amulet"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("If you've used this type of energy: Water: Gain 2 energy tokens. Earth: Gain 5 crystals. Fire: Draw a power card. Air: Increase your summoning gauge by 1."),
        'points' => 2,
        'cost' => array(
            // Note: special...
        )
     ),
     32 => array(
        'name' => clienttranslate("Tree of Light"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Discard 3 crystals and gain 1 energy token. **, Discard 1 energy token and you can transmute this round."),
        'points' => 12,
        'cost' => array(
            4 => 2
        )
     ),
     33 => array(
        'name' => clienttranslate("Arcano Leech"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("In order to summon a power card, your opponents must give you 1 crystal first."),
        'points' => 8,
        'cost' => array(
            0 => array( 2 => 2, 3 => 5, 4 => 8 )
        )
     ),
     34 => array(
        'name' => clienttranslate("Crystal Orb"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Look at the first card of the draw pile: discard 4 energy tokens to summon it for free or put it back on top of the draw pile. **, Discard 3 crystals to put the first card of the draw pile into the discard pile."),
        'points' => 6,
        'cost' => array(
            4 => 1,
            3 => 1
        )
     ),
     35 => array(
        'name' => clienttranslate("Glutton Cauldron"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Place 1 energy token from your reserve on the Glutton Cauldron. øø, Sacrifice the Glutton Cauldron when it contains 7 energy tokens: put these 7 energy tokens into your reserve and gain 15 crystals."),
        'points' => 0,
        'cost' => array(
        )
     ),
     36 => array(
        'name' => clienttranslate("Vampiric Crown"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Discard or draw a power card: gain as many energy tokens as the number of prestige points of the discarded or drawn card."),
        'points' => 0,
        'cost' => array(
            2 => 1,
            1 => 1
        )
     ),
     37 => array(
        'name' => clienttranslate("Dragon Skull"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Sacrifice 3 power cards to gain 15 crystals."),
        'points' => 9,
        'cost' => array(
            2 => 1,
            4 => 1,
            3 => 1
        )
     ),
     38 => array(
        'name' => clienttranslate("Demon of Argos"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Reduce the summoning gauge of all your opponents by 1. Each of your opponents draws 1 power card."),
        'points' => 16,
        'cost' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        )
     ),
     39 => array(
        'name' => clienttranslate("Titus Deepgaze"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("At the end of the round, your opponents must give you 1 crystal. If an opponent doesn't have any crystals left to give you, sacrifice Titus Deepgaze."),
        'points' => 4,
        'cost' => array(
            3 => array( 2 => 1, 3 => 2, 4 => 3 )
        )
     ),
     40 => array(
        'name' => clienttranslate("Air Elemental"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("All the energy tokens present in the reserves of your opponents become air energy tokens."),
        'points' => 12,
        'cost' => array(
            1 => 3
        )
     ),
     41 => array(
        'name' => clienttranslate("Thieving Fairies"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Each time an opponent activates (**) one of their power cards, they must give you 1 crystal. Gain an extra 1 crystal at that moment."),
        'points' => 6,
        'cost' => array(
            0 => array( 2 => 0, 3 => 3, 4 => 6 )
        )
     ),
     42 => array(
        'name' => clienttranslate("Cursed Treatise of Arus"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Gain 2 energy tokens and 10 crystals. Increase your summoning gauge by 1. If the Cursed Treatise of Arus is sacrificed, discard all the energy tokens in your reserve."),
        'points' => -10,
        'cost' => array(
            2 => 1
        )
     ),
     43 => array(
        'name' => clienttranslate("Idol of the Familiar"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Gain 1 crystal for each of your familiars in play."),
        'points' => 0,
        'cost' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        )
     ),
     44 => array(
        'name' => clienttranslate("Necrotic Kriss"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Discard or sacrifice one of your familiars to gain 4 energy tokens."),
        'points' => 6,
        'cost' => array(
            2 => 1,
            1 => 2
        )
     ),
     45 => array(
        'name' => clienttranslate("Lantern of Xidit"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("At the end of the game, each energy token in your reserve earns you 3 additional crystals."),
        'points' => 24,
        'cost' => array(
            4 => 3,
            3 => 3
        )
     ),
     46 => array(
        'name' => clienttranslate("Sealed Chest of Urm"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("At the end of the game, if you only have magic items in play, gain 20 crystals."),
        'points' => 10,
        'cost' => array(
            2 => 2,
            4 => 1
        )
     ),
     47 => array(
        'name' => clienttranslate("Mirror of the Seasons"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Discard X crystals: transform X identical energy tokens from your reserve into X identical energy tokens of another type."),
        'points' => 8,
        'cost' => array(
            0 => 3
        )
     ),
     48 => array(
        'name' => clienttranslate("Pendant of Ragnor"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Gain 1 energy for each of your magic items in play, except for the Pendant of Ragnor."),
        'points' => 0,
        'cost' => array(
            2 => 1,
            3 => 1,
            1 => 1
        )
     ),
     49 => array(
        'name' => clienttranslate("Sid Nightshade"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("If you are the player with the most crystals, each opponent must give you 5 additional crystals."),
        'points' => 6,
        'cost' => array(
            4 => array( 2 => 1, 3 => 2, 4 => 3 )
        )
     ),
     50 => array(
        'name' => clienttranslate("Damned Soul of Onys"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("§§, Gain 10 crystals and 1 water energy. **, Discard a Water: Straighten the Damned Soul of Onys and pass it to the player on your left. øø, At the end of the round, lose 3 crystals."),
        'points' => -5,
        'cost' => array(
            2 => 1
        )
     ),
     
     
     //// Enchanted kingdom: 100 + card id
     101 => array(
        'name' => clienttranslate("Heart of Argos"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Once per turn, if you activate (**) one of your Power cards, place a Earth energy token on the Heart of Argos. At the end of the round, place this energy in your reserve."),
        'points' => 7,
        'cost' => array(
            4 => 2
        ),
        'imageindex' => 52     
     ),
     102 => array(
        'name' => clienttranslate("Horn of Plenty"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Discard 1 energy token at the end of the round: receive 5 crystals if the discarded energy is Earth."),
        'points' => 4,
        'cost' => array(
            2 => 1,
            4 => 1
        ),
        'imageindex' => 53     
     ),
     108 => array(
        'name' => clienttranslate("Familiar Catcher"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Reveal the cards in the draw pile until you reveal a familiar. Either add the card to your hand or discard it. If you discard it, you may repeat the effect of this card once, but only once."),
        'points' => 7,
        'cost' => array(
            1 => 1,
            3 => 1
        ) ,
        'imageindex' => 54    
     ),
     115 => array(
        'name' => clienttranslate("Amulet of Time"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Collect 2 energy tokens. Discard X Power cards and draw X Power cards."),
        'points' => 9,
        'cost' => array(
            2 => 2
        )   ,
        'imageindex' => 55  
     ),
     120 => array(
        'name' => clienttranslate("Ratty Nightshade"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Collect up to 2 energy tokens of your choice from each opponent's energy reserve, and place them in your own reserve."),
        'points' => 8,
        'cost' => array(
            0 => array( 2 => 2, 3 => 4, 4 => 6 )
        ),
        'imageindex' => 56     
     ),
     
     // Enchanted kingdom second part
     119 => array(
        'name' => clienttranslate("Warden of Argos"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Choose one of the following options: each player must discard 4 energy tokens from their reserve OR each player must discard a Power card."),
        'points' => 6,
        'cost' => array(
            1 => 1
        ),
        'imageindex' => 72     
     ),     
     110 => array(
        'name' => clienttranslate("Throne of Renewal"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Discard a Power card: draw a Power card and move your Sorcerer token back one space on the bonus track."),
        'points' => 10,
        'cost' => array(
            2 => 1,
            3 => 2
        ),
        'imageindex' => 63 
     ),     
     116 => array(
        'name' => clienttranslate("Arcane Telescope"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, discard 2 crystals: look at the first 3 cards in the draw pile and then place them on top of the draw pile in the order of your choice."),
        'points' => 8,
        'cost' => array(
        ),
        'imageindex' =>  69    
     ),     
     112 => array(
        'name' => clienttranslate("Jewel of the Ancients"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, take 3 identical energy tokens from your reserve: place one on the Jewel of the Ancients and discard the other two. øø, At the end of the game, receive 35 crystals if there are 3 or more energy tokens on the Jewel of the Ancients. Otherwise, lose 10 crystals."),
        'points' => 10,
        'cost' => array(
            3 => 2
        ),
        'imageindex' => 65    
     ),     
     114 => array(
        'name' => clienttranslate("Steadfast Die"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Instead of performing the actions indicated by your season die, you may choose one of the following actions: increase your summoning gauge by 1, receive 1 energy, or transmute during this turn."),
        'points' => 10,
        'cost' => array(
            1 => 1,
            4 => 1
        ),
        'imageindex' =>  67    
     ),
     
     // Enchanted kingdom last part (10 cards)
     103 => array(
        'name' => clienttranslate("Fairy Monolith"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, return any or all (but at least 1) of the energy tokens currently stored on the Fairy Monolith to your energy reserve. øø At the end of the round, you may place 1 energy token from your reserve on the Fairy Monolith."),
        'points' => 6,
        'cost' => array(
            4 => 2
        ),
        'imageindex' =>  57    
     ),     
     104 => array(
        'name' => clienttranslate("Selenia’s Codex"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Return a magic item (that is not a Selenia’s Codex) to your hand, on condition that the item's summoning cost includes one or more energy tokens."),
        'points' => 6,
        'cost' => array(
            1 => 1,
            2 => 1
        ),
        'imageindex' =>  58    
     ),     
     105 => array(
        'name' => clienttranslate("Scroll of Ishtar"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Name one of the four energy types and then reveal the cards in the draw pile one-by-one until a magic item for which the summoning cost includes the named energy is revealed. Either add the revealed card to your hand or discard it. If you discard it, you may repeat the effect of this card once, but only once."),
        'points' => 7,
        'cost' => array(
            3 => 2
        ),
        'imageindex' =>  59   
     ),     
     106 => array(
        'name' => clienttranslate("Mesodae’s Lantern"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Mesodae’s Lantern cannot be put into play via another Power card. Your energy reserve is decreased by 1. At the end of the round, receive 3 crystals."),
        'points' => 24,
        'cost' => array(
            1 => 3,
            2 => 3
        ),
        'imageindex' =>  60    
     ),     
     107 => array(
        'name' => clienttranslate("Statue of Eolis"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Your energy reserve is decreased by 1. Whenever the season changes, either collect 1 energy token OR receive 2 crystals and look at the top card of the draw pile."),
        'points' => 6,
        'cost' => array(
            2 => 1,
            3 => 1,
            4 => 1
        ),
        'imageindex' =>  61    
     ),     
     109 => array(
        'name' => clienttranslate("Io’s Transmuter"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("If your season die grants you crystals, you may transmute during your turn. At the end of the round, receive 2 crystals if you have used Io’s Transmuter to transmute energy tokens."),
        'points' => 6,
        'cost' => array(
            2 => 1,
            4 => 1
        ),
        'imageindex' =>  62   
     ),     
     111 => array(
        'name' => clienttranslate("Potion of Resurrection"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, sacrifice the Potion of Resurrection and look at the top five cards in the discard area: add one of the cards to your hand and return the others to the bottom of the discard pile."),
        'points' => 0,
        'cost' => array(
            3 => 1,
            4 => 1
        ),
        'imageindex' =>  64   
     ),     
     113 => array(
        'name' => clienttranslate("Shield of Zira"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Instead of discarding or sacrificing one of your Power cards, you may sacrifice the Shield of Zira instead, in which case, you also receive 10 crystals."),
        'points' => 5,
        'cost' => array(
            1 => 1
        ),
        'imageindex' =>  66    
     ),     
     117 => array(
        'name' => clienttranslate("Argos Hawk"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("§§ Receive 10 crystals and increase your summoning gauge by 1. **, sacrifice the Argos Hawk: each opponent must decrease their summoning gauge by 1 but receives 6 crystals."),
        'points' => 4,
        'cost' => array(
            1 => 1,
            4 => 1
        ),
        'imageindex' =>  70    
     ),     
     118 => array(
        'name' => clienttranslate("Raven the Usurper"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("§§ Place a Raven token on an opponent's magic item and pay its summoning cost: Raven permanently acquires the effect(s) of the mimicked magic item. øø Sacrifice Raven the Usurper if the mimicked magic item is removed from play."),
        'points' => 2,
        'cost' => array(
            3 => 1
        ),
        'imageindex' =>  71    
     ),
     
     
     /////////////////////////////////////////////////////////////////////////////////////////
     //// Path of Destiny: 200 + card id

     201 => array(
        'name' => clienttranslate("Dragonsoul"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, discard 1 crystal: straighten a turned Power card other than a Dragonsoul."),
        'points' => 8,
        'cost' => array(
            
        ),
        'imageindex' => 73
     ),
     202 => array(
        'name' => clienttranslate("Magma Core"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("øø, Receive 1 Fire energy token when an opponent summons a Power card. **, sacrifice the Magma Core: receive 3 Fire energy tokens."),
        'points' => 0,
        'cost' => array(
            3 => array( 2 => 1, 3 => 2, 4 => 3 )
        ),
        'imageindex' => 74
     ),
     203 => array(
        'name' => clienttranslate("Twist of Fate"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("After selecting your 9 Power cards during the Prelude, remove Twist of Fate from the game and draw two Power cards: add one to your hand and discard the other. Before the tournament, remove any Twist of Fate cards from the draw pile and then shuffle."),
        'points' => 0,
        'cost' => array(
        ),
        'imageindex' => 75
     ),
     204 => array(
        'name' => clienttranslate("Potion of the Ancients"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, sacrifice the Potion of the Ancients and choose two effects: - Crystallize each energy in your reserve for 4 crystals. - Draw two Power cards and discard one. - Increase your summoning gauge by 2. - Receive 4 energy tokens."),
        'points' => 0,
        'cost' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        ),
        'imageindex' => 76
     ),
     205 => array(
        'name' => clienttranslate("Ethiel’s Fountain"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("At the end of the round, receive 3 crystals if you have no Power cards in your hand."),
        'points' => 7,
        'cost' => array(
            4 => 2
        ),
        'imageindex' => 77
     ),
     206 => array(
        'name' => clienttranslate("Dial of Colof"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("§§, Increase your summoning gauge by 2. øø, At the end of the round, if you have more Power cards in play than any opponent, you may reroll the Season die that was not selected by any of the players."),
        'points' => 12,
        'cost' => array(
            2 => 1,
            3 => 1,
            4 => 1            
        ),
        'imageindex' => 78
     ),
     207 => array(
        'name' => clienttranslate("Chalice of Eternity"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("øø, At the end of the round, you may place 1 energy token from your reserve on the Chalice of Eternity. **, discard 4 energy tokens placed on the Chalice of Eternity: look at the first 4 cards in the Power card draw pile, put one into play free of charge and discard the remaining cards."),
        'points' => 10,
        'cost' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        ),
        'imageindex' => 79
     ),
     208 => array( 
        'name' => clienttranslate("Staff of Winter"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("øø, In winter, all energy tokens in your reserve are also treated as earth energy. **, discard a magic item: receive 3 energy tokens."),
        'points' => 6,
        'cost' => array(
            2 => 2
        ),
        'imageindex' => 80
     ),
     209 => array(
        'name' => clienttranslate("Sepulchral Amulet"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Look at the first 3 cards in the discard pile: add one of the cards to your hand, place one on top of the draw pile and one at the bottom of the draw pile."),
        'points' => 8,
        'cost' => array(
            3 => 2
        ),
        'imageindex' => 81
     ),
     210 => array(
        'name' => clienttranslate("Eolis’s Replicator"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, discard 1 Water energy token: put a Replica Power card into play. This card is treated as a magic item worth 7 Prestige points at the end of the game."),
        'points' => 7,
        'cost' => array(
            2 => 1
        ),
        'imageindex' => 82
     ),
     211 => array(
        'name' => clienttranslate("Estorian Harp"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, discard 2 energy tokens of the same type: increase your summoning gauge by 1 and receive 3 crystals."),
        'points' => 8,
        'cost' => array(
            1 => 1
        ),
        'imageindex' => 83
     ),
     212 => array( 
        'name' => clienttranslate("Chrono-Ring"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Whenever the Season marker moves forward by 3 or more spaces during a round, receive 4 crystals or 1 energy."),
        'points' => 12,
        'cost' => array(
            0 => array( 2 => 2, 3 => 1, 4 => 0 ),
            2 => 1,
            4 => 1
        ),
        'imageindex' => 84
     ),
     213 => array(
        'name' => clienttranslate("Arus’s Mimicry"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("Discard or sacrifice a Power card: receive 12 crystals."),
        'points' => 10,
        'cost' => array(
            2 => 1,
            1 => 1,
            4 => 1
        ),
        'imageindex' => 85
     ),
     214 => array(
        'name' => clienttranslate("Carnivora Strombosea"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("At the end of the round, if you have no energy tokens in your reserve, look at the first card in the draw pile and choose an effect: - Replace it on top of the draw pile. - Add it to your hand and reduce your summoning gauge by 1."),
        'points' => 12,
        'cost' => array(
            4 => 1,
            3 => 1,
            1 => 1
        ),
        'imageindex' => 86
     ),
     215 => array( 
        'name' => clienttranslate("Urmian Psychic Cage"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("The Urmian Psychic Cage enters play with a Trap token placed on it. While the token remains on the card, a player summoning or putting into play a Power card must either: - Discard the Power card without applying its effects. - Or sacrifice a Power card. In both cases, the Trap token must then be removed."),
        'points' => 10,
        'cost' => array(
            0 => 2
        ),
        'imageindex' => 87
     ),
     
     216 => array( 
        'name' => clienttranslate("Servant of Ragfield"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Each player with at least 10 crystals increases their summoning gauge by 1, then draws a Power card and either adds it to their hand or discards it."),
        'points' => 10,
        'cost' => array(
            3 => 1,
            1 => 1
        ),
        'imageindex' => 88
     ),
     217 => array(
        'name' => clienttranslate("Argosian Tangleweed"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("§§, Place a Deadbolt token on an opponent's familiar. øø, A familiar with a Deadbolt token placed on it has no effect."),
        'points' => 14,
        'cost' => array(
            4 => 1,
            1 => 1
        ),
        'imageindex' => 89
     ),
     218 => array(
        'name' => clienttranslate("Io’s Minion"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("§§, Receive 1 Air energy token and increase your summoning gauge by 1. øø, You may no longer gain crystals. **, discard 1 Air energy token: pass Io's Minion (straightened) to the player on your left."),
        'points' => -5,
        'cost' => array(
            1 => 1
        ),
        'imageindex' => 90
     ),
     219 => array(
        'name' => clienttranslate("Otus the Oracle"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Draw and place one Power card per player in the centre of the play area. During their turn, each player may summon a single one of these cards, after paying the summoning cost."),
        'points' => 10,
        'cost' => array(
            2 => 1,
            1 => 1
        ),
        'imageindex' => 91
     ),
     
     220 => array( 
        'name' => clienttranslate("Crafty Nightshade"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Add the first two Power cards from the draw pile to your hand: give any Power card from your hand to the opponent with the fewest Power cards in play."),
        'points' => 4,
        'cost' => array(
            3 => 1
        ),
        'imageindex' => 92
     ),
     221 => array(
        'name' => clienttranslate("Igramul the Banisher"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Name a card: opponents reveal their hands and discard all copies of the named card. If at least one card was discarded, receive the energy present in that card's summoning cost."),
        'points' => 7,
        'cost' => array(
            0 => 3
        ),
        'imageindex' => 93
     ),
     222 => array(
        'name' => clienttranslate("Replica"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => '',
        'points' => 7,
        'cost' => array(
        ),
        'imageindex' => 94
     ),     
     
     /////////////////////////////////////////////////////////////////////////////////////////
     //// Promo cards
     301 => array(
        'name' => clienttranslate("Speedwall the Escaped"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("Cost : X crystals, X = your summoning gauge. **, when an opponent draws only one Power card, draw the card instead and then place Speedwall the Escaped in that opponent`s hand."),
        'points' => 7,
        'cost' => array(
        ),
        'imageindex' => 95
     ),     
     302 => array( // ok
        'name' => clienttranslate("Orb of Ragfield"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("§§, gain 20 crystals. øø, All Power cards in your hand that are worth less than 12 Prestige points henceforth have a summoning cost of 5 crystals."),
        'points' => -5,
        'cost' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1,
        ),
        'imageindex' => 96
     ),     
     303 => array(
        'name' => clienttranslate("Crystal Titan"),
        'category' => 'f', // mi = magic items / f = familiar
        'text' => clienttranslate("**, sacrifice the Crystal Titan, then discard all your Power cards and lose all your crystals : choose and sacrifice a Power card belonging to an opponent. øø, Whenever an opponent whishes to sacrifice a Power card, they must first give you 3 crystals."),
        'points' => 9,
        'cost' => array(
            0 => array( 2 => 0, 3 => 3, 4 => 8 ),
            3 => 1
        ),
        'imageindex' => 97
     ),     

     
);

/*
$this->energies = array(
    1 => array( 'name' => clienttranslate('air'),
                'nametr' => self::_('air') ),
    2 => array( 'name' => clienttranslate('water'),
                'nametr' => self::_('water') ),
    3 => array( 'name' => clienttranslate('fire'),
                'nametr' => self::_('fire') ),
    4 => array( 'name' => clienttranslate('earth'),
                'nametr' => self::_('earth') )
);
*/

$this->card_types_second_edition = array(

     5 => array(
        'name' => clienttranslate("Balance of Ishtar"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("**, Discard 3 identical energy tokens: transmute them into 9 crystals."),
        'points' => 4,
        'cost' => array(
            0 => 2
        ),
        'imageindex' => 51
     ),
     43 => array(
        'name' => clienttranslate("Idol of the Familiar"),
        'category' => 'mi', // mi = magic items / f = familiar
        'text' => clienttranslate("§§, Gain 10 crystals. **, Gain 1 crystal for each of your familiars in play."),
        'points' => 0,
        'cost' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        ),
        'imageindex' => 50
     )
);


$this->dices = array(

    // Seasons => dice_id => face_id => face_description

    // winter
    1 => array(
        1 => array(
            1 => array( 'nrj' => array(3=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(2=>2), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            3 => array( 'nrj' => array(2=>2), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 3 ),
            4 => array( 'nrj' => array(1=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            6 => array( 'nrj' => array(2=>1,1=>1), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 )
        ),
        2 => array(
            1 => array( 'nrj' => array(2=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            2 => array( 'nrj' => array(2=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 1 ),
            3 => array( 'nrj' => array(3=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            4 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(2=>1,1=>1), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            6 => array( 'nrj' => array(1=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 )
        ),
        3 => array(
            1 => array( 'nrj' => array(2=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            2 => array( 'nrj' => array(1=>1), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            3 => array( 'nrj' => array(2=>1, 1=>1), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            4 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(3=>1), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            6 => array( 'nrj' => array(2=>2), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 3 )
        ),
        4 => array(
            1 => array( 'nrj' => array(2=>1,1=>1), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(2=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 2 ),
            3 => array( 'nrj' => array(1=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            4 => array( 'nrj' => array(2=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 6 ),
            6 => array( 'nrj' => array(3=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 )
        ),
        5 => array(
            1 => array( 'nrj' => array(1=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 6 ),
            3 => array( 'nrj' => array(3=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            4 => array( 'nrj' => array(1=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 ),
            5 => array( 'nrj' => array(2=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            6 => array( 'nrj' => array(2=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 1 )
        )   
    ),
    
    // spring
    2 => array(
        1 => array(
            1 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            2 => array( 'nrj' => array(2=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            3 => array( 'nrj' => array(4=>2), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 3 ),
            4 => array( 'nrj' => array(4=>2), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(1=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            6 => array( 'nrj' => array(4=>1,2=>1), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 )
        ),
        2 => array(
            1 => array( 'nrj' => array(2=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(4=>1,2=>1), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            3 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            4 => array( 'nrj' => array(1=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(4=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 1 ),
            6 => array( 'nrj' => array(4=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 )
        ),
        3 => array(
            1 => array( 'nrj' => array(4=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            2 => array( 'nrj' => array(2=>1), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            3 => array( 'nrj' => array(4=>1,2=>1), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            4 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(1=>1), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            6 => array( 'nrj' => array(4=>2), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 3 )
        ),
        4 => array(
            1 => array( 'nrj' => array(4=>1,2=>1), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(4=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 2 ),
            3 => array( 'nrj' => array(2=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            4 => array( 'nrj' => array(4=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 6 ),
            6 => array( 'nrj' => array(1=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 )
        ),
        5 => array(
            1 => array( 'nrj' => array(2=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(2=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            3 => array( 'nrj' => array(), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 6 ),
            4 => array( 'nrj' => array(1=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(4=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 1 ),
            6 => array( 'nrj' => array(4=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 )
        ) 
    ),
    
    // summer
    3 => array(
        1 => array(
            1 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            2 => array( 'nrj' => array(4=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            3 => array( 'nrj' => array(3=>2), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 3 ),
            4 => array( 'nrj' => array(3=>2), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(2=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            6 => array( 'nrj' => array(3=>1,4=>1), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 )
        ),
        2 => array(
            1 => array( 'nrj' => array(3=>1,4=>1), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            3 => array( 'nrj' => array(2=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            4 => array( 'nrj' => array(3=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 1 ),
            5 => array( 'nrj' => array(3=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            6 => array( 'nrj' => array(4=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 )
        ),
        3 => array(
            1 => array( 'nrj' => array(4=>1), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(3=>1,4=>1), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            3 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            4 => array( 'nrj' => array(2=>1), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(3=>2), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 3 ),
            6 => array( 'nrj' => array(3=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 )
        ),
        4 => array(
            1 => array( 'nrj' => array(3=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 2 ),
            2 => array( 'nrj' => array(4=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            3 => array( 'nrj' => array(3=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            4 => array( 'nrj' => array(), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 6 ),
            5 => array( 'nrj' => array(2=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            6 => array( 'nrj' => array(3=>1,4=>1), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 )
        ),
        5 => array(
            1 => array( 'nrj' => array(3=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            2 => array( 'nrj' => array(4=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 ),
            3 => array( 'nrj' => array(4=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            4 => array( 'nrj' => array(), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 6 ),
            5 => array( 'nrj' => array(2=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            6 => array( 'nrj' => array(3=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 1 )
        )    
    ),
    
    // fall
    4 => array(
        1 => array(
            1 => array( 'nrj' => array(3=>1,1=>1), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            3 => array( 'nrj' => array(3=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            4 => array( 'nrj' => array(1=>2), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 3 ),
            5 => array( 'nrj' => array(1=>2), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            6 => array( 'nrj' => array(4=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 )
        ),
        2 => array(
            1 => array( 'nrj' => array(3=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(3=>1,1=>1), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            3 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            4 => array( 'nrj' => array(4=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(1=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 1 ),
            6 => array( 'nrj' => array(1=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 )
        ),
        3 => array(
            1 => array( 'nrj' => array(1=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            2 => array( 'nrj' => array(3=>1), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            3 => array( 'nrj' => array(3=>1,1=>1), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            4 => array( 'nrj' => array(), 'inv' => false, 'card' => true, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(4=>1), 'inv' => true, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 0 ),
            6 => array( 'nrj' => array(1=>2), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 3 )
        ),
        4 => array(
            1 => array( 'nrj' => array(3=>1,1=>1), 'inv' => false, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(1=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 2 ),
            3 => array( 'nrj' => array(3=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => true, 'pts' => 0 ),
            4 => array( 'nrj' => array(1=>2), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            5 => array( 'nrj' => array(), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 6 ),
            6 => array( 'nrj' => array(4=>1), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 )
        ),
        5 => array(
            1 => array( 'nrj' => array(3=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => true, 'pts' => 0 ),
            2 => array( 'nrj' => array(), 'inv' => false, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 6 ),
            3 => array( 'nrj' => array(4=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => false, 'pts' => 0 ),
            4 => array( 'nrj' => array(1=>2), 'inv' => false, 'card' => false, 
                         'time' => 2, 'trans' => false, 'pts' => 1 ),
            5 => array( 'nrj' => array(1=>2), 'inv' => true, 'card' => false, 
                         'time' => 3, 'trans' => false, 'pts' => 0 ),
            6 => array( 'nrj' => array(3=>1), 'inv' => true, 'card' => false, 
                         'time' => 1, 'trans' => true, 'pts' => 0 )
        )    
    )

);




