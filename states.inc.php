<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SeasonsSK implementation : Grégory Isabelli <gisabelli@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * states.inc.php
 *
 * SeasonsSK game states description
 *
 */
if (!defined('ST_END_SCORE')) {
    define('ST_END_SCORE', 90);
    define('ST_END_GAME', 99);
    define('STATE_DEBUGGING_END', 100);
}
/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array("" => 11) //98 for fake scoring, 11 for real
    ),

    /////////// Draft & deck building phase ////////////////

    10 => array(
        "name" => "draftChoice",
        "description" => clienttranslate('Everyone must choose a card to keep from the set'),
        "descriptionmyturn" => clienttranslate('${you} must choose a card to keep from the set'),
        "type" => "multipleactiveplayer",
        "action" => "stDraftChoice",
        "possibleactions" => array("draftChooseCard", "undoDraftChooseCard"),
        "transitions" => array("everyoneChoosed" => 11)
    ),
    11 => array(
        "name" => "continueDraftChoice",
        "description" => '',
        "type" => "game",
        "action" => "stContinueDraftChoice",
        "transitions" => array("endDraftChoice" => 14, "endDraftChoiceTwist" => 12, "continueDraftChoice" => 10)
    ),

    12 => array(
        "name" => "draftTwist",
        "description" => clienttranslate('Twist of Fate: Some player must choose a card'),
        "descriptionmyturn" => clienttranslate('Twist of Fate: ${you} must choose a card to keep'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array("draftTwist"),
        "transitions" => array("draftTwist" => 14)
    ),

    13 => array(
        "name" => "chooseToken",
        "description" => clienttranslate('Everyone must choose his ability token'),
        "descriptionmyturn" => clienttranslate('${you} must choose one ability token'),
        "type" => "multipleactiveplayer",
        "action" => "stMakeEveryoneActive",
        "possibleactions" => array("chooseToken"),
        "transitions" => array("startYear" => 20)
    ),

    14 => array(
        "name" => "prepareBuildLibrary",
        "description" => '',
        "type" => "game",
        "action" => "stPrepareBuildLibrary",
        "transitions" => array("" => 18)
    ),

    18 => array(
        "name" => "buildLibraryNew",
        "description" => clienttranslate('Everyone must distribute his cards in 3 decks: year I, year II, year III.'),
        "descriptionmyturn" => clienttranslate('${you} must distribute your cards in 3 decks: year I, year II, year III.'),
        "type" => "multipleactiveplayer",
        "action" => "stBuildLibraryNew",
        "possibleactions" => array("chooseLibrarynew", "undoChooseLibrarynew"),
        "transitions" => array("chooseLibrarynew" => 20, "chooseToken" => 13)
    ),

    /////////// Main cycle ////////////////
    20 => array(
        "name" => "startYear",
        "description" => '',
        "type" => "game",
        "action" => "stStartYear",
        "args" => "argStartYear",
        "transitions" => array("newyear" => 21, "endGame" => 98)
    ),

    21 => array(
        "name" => "startRound",
        "description" => '',
        "type" => "game",
        "action" => "stStartRound",
        "updateGameProgression" => true,
        "transitions" => array("newround" => 22, "endGame" => 98)
    ),

    22 => array(
        "name" => "diceChoice",
        "description" => clienttranslate('${actplayer} must choose a die'),
        "descriptionmyturn" => clienttranslate('${you} must choose a die'),
        "type" => "activeplayer",
        "possibleactions" => array("chooseDie"),
        "transitions" => array("chooseDie" => 23)
    ),
    23 => array(
        "name" => "diceChoiceNextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stDiceChoiceNextPlayer",
        "transitions" => array("nextPlayer" => 22, "noMoreDice" => 27)
    ),

    25 => array(
        "name" => "nextPlayerTurn",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayerTurn",
        "transitions" => array("nextPlayer" => 27, "endRound" => 39)
    ),

    27 => array(
        "name" => "maliceDie",
        "description" => clienttranslate('${actplayer} can use Die of Malice'),
        "descriptionmyturn" => clienttranslate('${you} can use Die of Malice to reroll your die'),
        "type" => "activeplayer",
        "action" => "stMaliceDie",
        "possibleactions" => array("reroll"),
        "transitions" => array("startTurn" => 217, "cardEffect" => 50)
    ),
    217 => array(
        "name" => "token17Effect",
        "description" => clienttranslate('${actplayer} can use his ability token to reroll'),
        "descriptionmyturn" => clienttranslate('${you} can use your ability token to reroll your die'),
        "type" => "activeplayer",
        "action" => "stToken17Effect",
        "possibleactions" => array("reroll", "playToken"),//reroll implies playing token automatically
        "transitions" => array("steadfastDie" => 227)
    ),
    227 => array(
        "name" => "steadfastDie",
        "description" => clienttranslate('${actplayer} can use Steadfast die'),
        "descriptionmyturn" => clienttranslate('${you} can use Steadfast die'),
        "type" => "activeplayer",
        "action" => "stSteadfastDie",
        "possibleactions" => array("steadFast"),
        "transitions" => array("startTurn" => 28, "cardEffect" => 50)
    ),
  
    28 => array(
        "name" => "startPlayerTurn",
        "description" => '',
        "type" => "game",
        "action" => "stStartPlayerTurn",
        "transitions" => array("checkEnergy" => 31, "startTurn" => 29)
    ),
    29 => array(
        "name" => "startPlayerTurn2",
        "description" => '',
        "type" => "game",
        "action" => "stStartPlayerTurn2",
        "transitions" => array("startTurn" => 30)
    ),
    30 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} can take some actions'),
        "descriptionmyturn" => clienttranslate('${you} can take some actions'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "action" => "stPlayerTurn",
        "possibleactions" => array("incSummon", "draw", "transmute", "summon", "active", "useBonus", 'endTurn', 'playToken'),
        "transitions" => array(
            "endOfTurn" => 25, "cardEffect" => 50, "summonVariableCost" => 35, "draw" => 32, "useBonus" => 30,
            "bonusDraw" => 36, "bonusExchange" => 37, "resetPlayerTurn" => 30, "playerTurn" => 30
        )
    ),
    31 => array(
        "name" => "checkEnergy",
        "description" => clienttranslate('${actplayer} can keep only ${keep} energies'),
        "descriptionmyturn" => clienttranslate('${you} must discard ${trash} energies'),
        "type" => "activeplayer",
        "action" => "stCheckEnergy",
        "args" => "argCheckEnergy",
        "possibleactions" => array("discardEnergy"),
        "transitions" => array("energyOk" => 30, "discardEnergy" => 30, "continueDiscard" => 31)
    ),

    32 => array(
        "name" => "keepOrDiscard",
        "description" => clienttranslate('${actplayer} can keep of discard his card'),
        "descriptionmyturn" => clienttranslate('${you} must choose to keep or discard ${card_name}'),
        "type" => "activeplayer",
        "args" => "argKeepOfDiscard",
        "possibleactions" => array("keepOrDiscard"),
        "transitions" => array("keepOrDiscard" => 30, "zombieTurn" => 30)
    ),

    35 => array(
        "name" => "summonVariableCost",
        "description" => clienttranslate('${actplayer} must choose how to pay the cost of ${card_name}'),
        "descriptionmyturn" => clienttranslate('${you} must choose how to pay the cost of ${card_name}:'),
        "type" => "activeplayer",
        "action" => "stSummonVariableCost",
        "args" => "argSummonVariableCost",
        "possibleactions" => array("chooseCost"),
        "transitions" => array("chooseCost" => 50, "cancelChooseCost" => 30)
    ),

    36 => array(
        "name" => "bonusDrawChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must add one card to his hand'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must add one card to your hand'),
        "type" => "activeplayer",
        "args" => "argBonusDrawChoice",
        "possibleactions" => array("chooseCard"),
        "transitions" => array("chooseCard" => 30, "zombieTurn" => 30)
    ),
    37 => array(
        "name" => "bonusExchangeDiscard",
        "description" => clienttranslate('Bonus: ${actplayer} must discard 2 energy tokens'),
        "descriptionmyturn" => clienttranslate('Bonus: ${you} must discard 2 energy tokens'),
        "type" => "activeplayer",
        "possibleactions" => array("discardEnergyBonus"),
        "transitions" => array("discardEnergy" => 38, "zombieTurn" => 30)
    ),
    38 => array(
        "name" => "bonusGainEnergy",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose which energy to get (x${nbr})'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose which energy to get (x${nbr})'),
        "type" => "activeplayer",
        "args" => "argBonusGainEnergy",
        "possibleactions" => array("gainEnergy"),
        "transitions" => array("next" => 38, "end" => 31, "zombieTurn" => 30)
    ),

    39 => array(
        "name" => "preEndRound",
        "description" => '',
        "type" => "game",
        "action" => "stPreEndRound",
        "transitions" => array("endRound" => 40, "cardEffect" => 50)
    ),
    40 => array(
        "name" => "endRound",
        "description" => '',
        "type" => "game",
        "action" => "stEndRound",
        "transitions" => array("nextYear" => 20, "nextRound" => 21, "endGame" => 98, "cardEffect" => 50)
    ),

    /////////// Apply card effect ////////////////

    50 => array(
        "name" => "cardEffect",
        "description" => '',
        "type" => "game",
        "action" => "stCardEffect",
        "transitions" => array(
            "nextEffect" => 51, "checkEnergy" => 52,
            "endEffectPlayerTurn" => 30, "endEffectEndOfRound" => 39, "endEffectNewRound" => 21, "endEffectNewYear" => 20, "endEffectBeforeTurn" => 27,
            "gainEnergy" => 60, "discardIshtar" => 61, "discardKairn" => 62, "necroticSacrifice" => 63,
            "discardMirror" => 64, "elementalChoice" => 66, "treeOfLifeChoice" => 67,
            "cauldronPlace" => 69, "vampiricChoice" => 70, "amuletFireChoice" => 73, "divineChoice" => 74,
            "potionDreamChoice" => 75, "dragonSkull" => 76, "temporalBoots" => 79, "syllasSacrifice" => 80,
            "nariaChoice" => 82, "amsugTakeback" => 83, "lewisChoice" => 85, "orbChoice" => 86, "discardIshtar2" => 88,
            "discardHornPlenty" => 89, 'familiarChoice' => 90, 'rattyNightshade' => 92, 'warden_choice' => 93,
            "throneDiscard" => 150, "telescopeChoice" => 151, "discardJewel" => 152, "fairyMonolith" => 153,
            "fairyMonolithActive" => 154, "seleniaCodex" => 155, "scrollIshtar" => 157, "statueOfEolisChoice" => 159,
            "resurrectionChoice" => 161,
            "potionSacrificeChoice" => 162, "ravenChoice" => 163,
            // Path of destiny
            "potionOfAncientChoice" => 165, 'sepuchralAmuletCardChoice' => 168, "discardEstorian" => 170, "arusSacrifice" => 171,
            "argosianChoice" => 172,
            "discardEolis" => 173, "dragonSoulCardChoice" => 174, "dialColofDualChoice" => 175,
            "staffWinterDiscard" => 176, "chronoRingChoice" => 178, "urmianChoice" => 179,
            "draw" => 181, // Note: Servant of Ragfield
            "craftyChoice" => 183, "discardMinion" => 185, "chaliceEternity" => 186, "chaliceEternityChoice" => 187,
            "carnivoraChoice" => 188, "igramulChoice" => 189,  "escaped_choice" =>
            193,  "endTokenEffect" => 299
        )
    ),

    51 => array(
        "name" => "nextEffect",
        "description" => '',
        "type" => "game",
        "action" => "stNextEffect",
        "transitions" => array("" => 50)
    ),

    52 => array(    // nextEffect with checkEnergy before
        "name" => "nextEffectCheckEnergy",
        "description" => clienttranslate('${actplayer} can keep only ${keep} energies'),
        "descriptionmyturn" => clienttranslate('${you} must discard ${trash} energies'),
        "type" => "activeplayer",
        "action" => "stCheckEnergy",
        "args" => "argCheckEnergy",
        "possibleactions" => array("discardEnergy", "discardEnergyEffect"),
        "transitions" => array("energyOk" => 51, "discardEnergy" => 51, "continueDiscard" => 52, "playerTurn" => 30)
    ),



    /////////// Card specific states ////////////////

    60 => array(
        "name" => "gainEnergy",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose which energy to get (x${nbr})'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose which energy to get (x${nbr})'),
        "type" => "activeplayer",
        "args" => "argGainEnergy",
        "possibleactions" => array("gainEnergy"),
        "transitions" => array("next" => 60, "end" => 52, 'endAmuletOfTime' => 91)
    ),
    61 => array(
        "name" => "discardIshtar",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard 4 identical energies'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard 4 identical energies'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect"),
        "transitions" => array("discardEnergy" => 51)
    ),
    62 => array(
        "name" => "discardKairn",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard an energy'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard an energy'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect"),
        "transitions" => array("discardEnergy" => 51)
    ),
    63 => array(

        "name" => "necroticSacrifice",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard or sacrifice a familiar'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard or sacrifice a familiar'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("sacrifice", "discard"), // Not: NO cancel otherwize you can use Heart of Argos even after a cancel
        "transitions" => array("sacrifice" => 60, "discard" => 60, "zombieTurn" => 51)
    ),
    64 => array(
        "name" => "mirrorDiscard",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose X identical energies'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose X identical energies'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseXenergy"),
        "transitions" => array("chooseXenergy" => 65, "none" => 51)
    ),
    65 => array(
        "name" => "mirrorChoose",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a type of energy'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose to get ${nbr}:'),
        "type" => "activeplayer",
        "args" => "argGainEnergy",
        "possibleactions" => array("chooseEnergyType"),
        "transitions" => array("chooseEnergyType" => 52)
    ),
    66 => array(
        "name" => "elementalChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose to use one energy${forfree}'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose to use one energy${forfree}'),
        "type" => "activeplayer",
        "args" => "argElementalChoice",
        "possibleactions" => array("chooseEnergyType", "cardEffectEnd"),
        "transitions" => array("chooseEnergyType" => 51, "gainEnergy" => 72, "continue" => 66)
    ),
    67 => array(
        "name" => "treeOfLifeChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must make a choice'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose:'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice"),
        "transitions" => array("discardEnergy" => 68, "gainEnergy" => 60)
    ),
    68 => array(
        "name" => "discardTree",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard an energy'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard an energy'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect"),
        "transitions" => array("discardEnergy" => 51)
    ),
    69 => array(
        "name" => "cauldronPlace",
        "description" => clienttranslate('${card_name}: ${actplayer} must place an energy on the Cauldron'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must place an energy on the Cauldron'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect", "placeenergyEffect"),
        "transitions" => array("discardEnergy" => 52, "potionSacrificeChoice" => 162)
    ),
    70 => array(
        "name" => "vampiricChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose to draw or discard a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose to draw or discard a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice"),
        "transitions" => array("gainEnergy" => 60, "chooseDiscard" => 71, "noGain" => 51)
    ),
    71 => array(
        "name" => "vampiricDiscard",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discard", "useZira"),
        "transitions" => array("gainEnergy" => 60, "noGain" => 51)
    ),
    72 => array(
        "name" => "gainEnergy", // Note: linked to Elemental Amulet
        "description" => clienttranslate('${card_name}: ${actplayer} must choose which energy to get (x${nbr})'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose which energy to get (x${nbr})'),
        "type" => "activeplayer",
        "args" => "argGainEnergy",
        "possibleactions" => array("gainEnergy"),
        "transitions" => array("next" => 72, "end" => 66)
    ),
    73 => array(
        "name" => "amuletFireChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must add one card to his hand'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must add one card to your hand'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseCard"),
        "transitions" => array("chooseCard" => 51)
    ),
    74 => array(
        "name" => "divineChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a card to summon for free'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a card to summon for free'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseCard"),
        "transitions" => array("chooseCard" => 51)
    ),
    75 => array(
        "name" => "potionDreamChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a card to summon for free'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a card to summon for free'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseCardHand", "chooseCardHandOtus"),
        "transitions" => array("chooseCardHand" => 51)
    ),
    76 => array(
        "name" => "dragonSkull1",
        "description" => clienttranslate('${card_name}: ${actplayer} must sacrifice a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must sacrifice a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("sacrifice"),
        "transitions" => array("sacrifice" => 77)
    ),
    77 => array(
        "name" => "dragonSkull2",
        "description" => clienttranslate('${card_name}: ${actplayer} must sacrifice a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must sacrifice a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("sacrifice"),
        "transitions" => array("sacrifice" => 78)
    ),
    78 => array(
        "name" => "dragonSkull3",
        "description" => clienttranslate('${card_name}: ${actplayer} must sacrifice a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must sacrifice a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("sacrifice", "lastsacrifice"),
        "transitions" => array("sacrifice" => 51)
    ),
    79 => array(
        "name" => "temporalBoots",
        "description" => clienttranslate('${card_name}: ${actplayer} must move the season token back or forward'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must move the season token back or forward'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("moveSeason"),
        "transitions" => array("moveSeason" => 51)
    ),
    80 => array(
        "name" => "syllasSacrifice",
        "description" => clienttranslate('${card_name}: ${actplayer} must sacrifice a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must sacrifice a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("sacrifice"),
        "transitions" => array("nextPlayer" => 81)
    ),
    81 => array(
        "name" => "syllasSacrificeNext",
        "type" => "game",
        "transitions" => array("continue" => 80, "end" => 51)
    ),
    82 => array(
        "name" => "nariaChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a card for ${target}'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a card for ${target}'),
        "type" => "activeplayer",
        "args" => "argOpponentTarget",
        "possibleactions" => array("chooseCard"),
        "transitions" => array("chooseCard" => 51, "nextPlayer" => 82)
    ),
    83 => array(
        "name" => "amsugTakeback",
        "description" => clienttranslate('${card_name}: ${actplayer} must take back a magical item'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must take back a magical item'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("takeBack"),
        "transitions" => array("nextPlayer" => 84)
    ),
    84 => array(
        "name" => "amsugTakebackNext",
        "type" => "game",
        "transitions" => array("continue" => 83, "end" => 51)
    ),
    85 => array(
        "name" => "lewisChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose an opponent'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose an opponent'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("choosePlayer"),
        "transitions" => array("choosePlayer" => 52)
    ),
    86 => array(
        "name" => "orbChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must make a choice'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose:'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice"),
        "transitions" => array("seeNextCard" => 87, "discardNextCard" => 51)
    ),
    87 => array(
        "name" => "orbChoice2",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose to summon or replace a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose to summon or replace this card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("orbChoice"),
        "transitions" => array("orbChoice" => 51)
    ),
    88 => array(
        "name" => "discardIshtar",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard 3 identical energies'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard 3 identical energies'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect"),
        "transitions" => array("discardEnergy" => 51)
    ),
    89 => array(
        "name" => "discardHornPlenty",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard an energy'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard an energy'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect"),
        "transitions" => array("discardEnergy" => 51)
    ),
    90 => array(
        "name" => "familiarChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must make a choice'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose:'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice", "familiarAddToHand", "familiarDiscard"),
        "transitions" => array("familiarAddToHand" => 51, "familiarDiscard" => 51)
    ),
    91 => array(
        "name" => "amuletOfTime",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard X power cards to draw X power cards'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard X power cards to draw X power cards'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("amuletOfTime", "useZira"),
        "transitions" => array("amuletOfTime" => 52, "chooseZira" => 91)
    ),
    92 => array(
        "name" => "rattyNightshade",
        "description" => clienttranslate('${card_name}: ${actplayer} must collect up to 2 energy token from each opponent`s energy reserve'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must collect up to 2 energy token from each opponent`s energy reserve'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("collectEnergy"),
        "transitions" => array("rattyNightshade" => 52)
    ),
    93 => array(
        "name" => "wardenChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must make a choice'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose:'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice"),
        "transitions" => array("discardEnergy" => 95, "discardCard" => 97)
    ),
    94 => array(
        "name" => "wardenDiscardEnergy",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard 4 energies'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard 4 energies'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect"),
        "transitions" => array("nextPlayer" => 95, "end" => 51)
    ),
    95 => array(
        "name" => "wardenDiscardEnergyNext",
        "type" => "game",
        "action" => "stWardenDiscardEnergyNext",
        "transitions" => array("playerChoice" => 94, "next" => 95, "end" => 51)
    ),
    96 => array(
        "name" => "wardenDiscardCard",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discard", "useZira"),
        "transitions" => array("nextPlayer" => 97, "end" => 51)
    ),
    97 => array(
        "name" => "wardenDiscardCardNext",
        "type" => "game",
        "action" => "stWardenDiscardCardNext",
        "transitions" => array("playerChoice" => 96, "next" => 97, "end" => 51)
    ),
    150 => array(
        "name" => "throneDiscard",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discard", "useZira"),
        "transitions" => array("discard" => 51)
    ),
    151 => array(
        "name" => "telescopeChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must replace a card on top of the draw pile'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must replace a card on top of the draw pile'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseCard"),
        "transitions" => array("chooseCard" => 51)
    ),
    152 => array(
        "name" => "discardJewel",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard 3 identical energies'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard 3 identical energies'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect"),
        "transitions" => array("discardEnergy" => 51)
    ),

    153 => array(
        "name" => "fairyMonolith",
        "description" => clienttranslate('${card_name}: ${actplayer} may place an energy on ${card_name}'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} may place an energy on ${card_name}:'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect", "doNotUse"),
        "transitions" => array("discardEnergy" => 51, "doNotUse" => 51)
    ),

    154 => array(
        "name" => "fairyMonolithActive",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose which energies to return in his reserve'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose which energies to return in your reserve'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCardWithId",
        "possibleactions" => array("fairyMonolithActive"),
        "transitions" => array("fairyMonolithActive" => 52)
    ),
    155 => array(
        "name" => "seleniaTakeback",
        "description" => clienttranslate('${card_name}: ${actplayer} must take back a magical item'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must take back a magical item'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("takeBack"),
        "transitions" => array("takeback" => 51)
    ),
    157 => array(
        "name" => "scrollIshtarChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a type of energy'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a type of energy'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseEnergyType"),
        "transitions" => array("scrollIshtarCardChoice" => 158)
    ),
    158 => array(
        "name" => "scrollIshtarCardChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must make a choice'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose:'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice", "familiarAddToHand", "familiarDiscard"),
        "transitions" => array("familiarAddToHand" => 51, "familiarDiscard" => 51)
    ),
    159 => array(
        "name" => "statueOfEolisChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must make a choice'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose:'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("statueOfEolisChoice", "gainEnergy"),
        "transitions" => array("statueOfEolisChoice" => 51, "end" => 51, "topcard" => 160, "zombieTurn" => 51)
    ),
    160 => array(
        "name" => "statueOfEolisLook",
        "description" => clienttranslate('${card_name}: ${actplayer} is looking at the top card of the draw pile'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} can look at the top card of the draw pile'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice"),
        "transitions" => array("end" => 51, "zombieTurn" => 51)
    ),
    161 => array(
        "name" => "resurrectionChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must add one card to his hand'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must add one card to your hand'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseCard"),
        "transitions" => array("chooseCard" => 51)
    ),
    162 => array(
        "name" => "potionSacrificeChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} may sacrifice Shield of Zira instead'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} may sacrifice Shield of Zira instead'),
        "type" => "activeplayer",
        "action" => "stPotionSacrificeChoice",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice", "useZira"),
        "transitions" => array(
            "chooseZira" => 52,
            "potionDreamChoice" => 75, "gainEnergy" => 60, "resurrectionChoice" => 161, "potionOfAncientChoice" => 165,  "crystalTitanChoice" => 192
        )
    ),
    163 => array(
        "name" => "ravenChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a Magical item to mimic'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a Magical item to mimic'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseOpponentCard", "doNotUse"),
        "transitions" => array("chooseOpponentCard" => 51, "doNotUse" => 51)
    ),

    165 => array(
        "name" => "potionOfAncientChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must make a choice'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose:'),
        "type" => "activeplayer",
        "args" => "argPotionOfAncientChoice",
        "action" => "stPotionOfAncientChoice",
        "possibleactions" => array("dualChoice"),
        "transitions" => array("continue" => 165, "stop" => 52, "potionOfAncientCardChoice" => 166, "gainEnergy" => 167)
    ),
    166 => array(
        "name" => "potionOfAncientCardChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must add one card to his hand'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must add one card to your hand'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseCard"),
        "transitions" => array("chooseCard" => 165)
    ),
    167 => array(
        "name" => "gainEnergy", // Note: linked to potion of the ancients
        "description" => clienttranslate('${card_name}: ${actplayer} must choose which energy to get (x${nbr})'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose which energy to get (x${nbr})'),
        "type" => "activeplayer",
        "args" => "argGainEnergy",
        "possibleactions" => array("gainEnergy"),
        "transitions" => array("next" => 167, "end" => 194)
    ),

    168 => array(
        "name" => "sepulchralAmuletChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must add one card to his hand'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must add one card to your hand'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseCard"),
        "transitions" => array("chooseCard" => 169, "moMoreCard" => 51)
    ),
    169 => array(
        "name" => "sepulchralAmuletChoice2",
        "description" => clienttranslate('${card_name}: ${actplayer} must replace a card on top of the draw pile'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must replace a card on top of the draw pile'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseCard"),
        "transitions" => array("chooseCard" => 51, "moMoreCard" => 51)
    ),
    170 => array(
        "name" => "discardEstorian",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard 2 identical energies'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard 2 identical energies'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect"),
        "transitions" => array("discardEnergy" => 51)
    ),
    171 => array(
        "name" => "arusSacrifice",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard or sacrifice a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard or sacrifice a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("sacrifice", "discard"),
        "transitions" => array("sacrifice" => 51, "discard" => 51, "zombieTurn" => 51)
    ),
    172 => array(
        "name" => "argosianChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a Familiar to lock'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a Familiar to lock'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseOpponentCard"),
        "transitions" => array("chooseOpponentCard" => 51, "zombieTurn" => 51)
    ),
    173 => array(
        "name" => "discardEolis",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard a Water energy'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard a Water energy'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect", "chooseCost"),
        "transitions" => array("discardEnergy" => 51, "zombieTurn" => 51)
    ),
    174 => array(
        "name" => "dragonsouldCardChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseTableauCard", "cancel"),
        "transitions" => array("chooseTableauCard" => 51, "cancel" => 51, "zombieTurn" => 51)
    ),

    175 => array(
        "name" => "dialDualChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} may reroll the remaining die'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} may reroll the remaining die'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice"),
        "transitions" => array("dualChoice" => 51, "zombieTurn" => 51)
    ),
    176 => array(
        "name" => "staffWinterDiscard",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discard", "useZira"), // Not: NO cancel otherwize you can use Heart of Argos even after a cancel
        "transitions" => array("gainEnergy" => 177,  "zombieTurn" => 51)
    ),

    177 => array(
        "name" => "gainEnergy", // Note: linked to staffWinterDiscard
        "description" => clienttranslate('${card_name}: ${actplayer} must choose which energy to get (x${nbr})'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose which energy to get (x${nbr})'),
        "type" => "activeplayer",
        "args" => "argGainEnergy",
        "possibleactions" => array("gainEnergy"),
        "transitions" => array("next" => 177, "end" => 52, "zombieTurn" => 52)
    ),
    178 => array(
        "name" => "chronoRingChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must make a choice'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose:'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chronoRingChoice", "gainEnergy", "chronoRingChoice"),
        "transitions" => array("chronoRingChoice" => 51, "end" => 51, "zombieTurn" => 51)
    ),
    179 => array(
        "name" => "urmianChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must make a choice'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose:'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("urmianChoice", "dualChoice"),
        "transitions" => array("urmianChoice" => 51, "urmianSacrifice" => 180, "zombieTurn" => 51)
    ),
    180 => array(
        "name" => "urmianSacrifice",
        "description" => clienttranslate('${card_name}: ${actplayer} must sacrifice a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must sacrifice a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("sacrifice"),
        "transitions" => array("sacrifice" => 51, "zombieTurn" => 51)
    ),
    181 => array(
        "name" => "keepOrDiscardRagfield", // Servant of ragfield
        "description" => clienttranslate('${actplayer} can keep of discard his card'),
        "descriptionmyturn" => clienttranslate('${you} must choose to keep or discard ${card_name}'),
        "type" => "activeplayer",
        "args" => "argKeepOfDiscard",
        "possibleactions" => array("keepOrDiscard"),
        "transitions" => array("keepOrDiscard" => 182, "draw" => 181, "zombieTurn" => 182)
    ),
    182 => array(
        "name" => "servantNext",
        "type" => "game",
        "action" => "stServantNext",
        "transitions" => array("continue" => 181, "end" => 51, "next" => 182, "draw" => 181)
    ),
    183 => array(
        "name" => "craftyChooseOpponent",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose an opponent'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose an opponent'),
        "type" => "activeplayer",
        "args" => "argCraftyChoice",
        "possibleactions" => array("choosePlayer"),
        "transitions" => array("choosePlayer" => 184)
    ),
    184 => array(
        "name" => "crafyChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a card to give to ${target}'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a card to give to ${target}'),
        "type" => "activeplayer",
        "args" => "argOpponentTarget",
        "possibleactions" => array("chooseCardHandcrafty"),
        "transitions" => array("chooseCardHand" => 51)
    ),
    185 => array(
        "name" => "discardMinion",
        "description" => clienttranslate('${card_name}: ${actplayer} must discard an Air energy'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must discard an Air energy'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect", "chooseCost"),
        "transitions" => array("discardEnergy" => 51, "zombieTurn" => 51)
    ),
    186 => array(
        "name" => "chaliceEternity",
        "description" => clienttranslate('${card_name}: ${actplayer} may place an energy on ${card_name}'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} may place an energy on ${card_name}:'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("discardEnergyEffect", "doNotUse"),
        "transitions" => array("discardEnergy" => 51, "doNotUse" => 51, "zombieTurn" => 51)
    ),
    187 => array(
        "name" => "chaliceEternityChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a card to summon for free'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a card to summon for free'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseCard"),
        "transitions" => array("chooseCard" => 51)
    ),
    188 => array(
        "name" => "carnivoraChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose to keep or replace this card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose to keep or replace this card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice"),
        "transitions" => array("dualChoice" => 51)
    ),
    189 => array(
        "name" => "igramulChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must name a card'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must name a card'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice"),
        "transitions" => array("dualChoice" => 191)
    ),
    190 => array(
        "name" => "igramulDiscard",
        "description" => clienttranslate('${card_name}: ${actplayer} may sacrifice Shield of Zira instead'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} may sacrifice Shield of Zira instead'),
        "type" => "activeplayer",
        "action" => "stIgramulDiscard",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("useZira"),
        "transitions" => array("nextPlayer" => 191, "end" => 52)
    ),
    191 => array(
        "name" => "igramulDiscardNext",
        "type" => "game",
        "transitions" => array("continue" => 190, "end" => 52)
    ),
    192 => array(
        "name" => "crystalTitanChoice",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose an opponent Power card to sacrifice'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose an opponent Power card to sacrifice'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("chooseOpponentCard", "doNotUse", "dualChoice"),
        "transitions" => array("chooseOpponentCard" => 51, "doNotUse" => 51)
    ),
    193 => array(
        "name" => "escapedChoice",
        "description" => clienttranslate('${actplayer} may activate ${card_name} to get the last card drawn.'),
        "descriptionmyturn" => clienttranslate('${you} may activate ${card_name} to get the last card drawn.'),
        "type" => "activeplayer",
        "args" => "argCurrentEffectCard",
        "possibleactions" => array("dualChoice"),
        "transitions" => array("dualChoice" => 51)
    ),
    194 => array( // Same as 52 but specific for Potion of Ancients
        "name" => "checkEnergy",
        "description" => clienttranslate('${actplayer} can keep only ${keep} energies'),
        "descriptionmyturn" => clienttranslate('${you} must discard ${trash} energies'),
        "type" => "activeplayer",
        "action" => "stCheckEnergy",
        "args" => "argCheckEnergy",
        "possibleactions" => array("discardEnergy"),
        "transitions" => array("energyOk" => 165, "discardEnergy" => 165, "continueDiscard" => 194)
    ),

    /* Token effects */
    200 => array(
        "name" => "tokenEffect",
        "description" => '',
        "descriptionmyturn" => '',
        "type" => "game",
        "action" => "stTokenEffect",
        "transitions" => array("token18Effect" => 218, "continuePlayerTurn" => 30, "token3Effect" => 60, "token10Effect" => 210, "token17Effect" => 217, "token11Effect" =>211, "token12Effect" => 212, "token2Effect" => 202)
    ),
    202 => array(
        "name" => "token2Effect",
        "description" => clienttranslate('Ability token: ${actplayer} must discard or sacrifice a power card'),
        "descriptionmyturn" => clienttranslate('${you} must move discard or sacrifice a power card'),
        "type" => "activeplayer",
        "possibleactions" => array("sacrifice", "discard"),
        "transitions" => array("endTokenEffect" => 299)
    ),
    210 => array(
        "name" => "token10Effect",
        "description" => clienttranslate('Ability token: ${actplayer} must move the season token 2 steps back or forward'),
        "descriptionmyturn" => clienttranslate('${you} must move the season token 2 steps back or forward'),
        "type" => "activeplayer",
        "possibleactions" => array("moveSeason"),
        "transitions" => array("moveSeason" => 51, )//"continuePlayerTurn" => 30
    ),
    211 => array(
        "name" => "token11Effect", //seeOpponentsHands
        "description" => clienttranslate('Ability token: ${actplayer} is looking at the other players hands'),
        "descriptionmyturn" => clienttranslate('${you} are looking at the other players hands'),
        "type" => "activeplayer",
        "args" => "argToken11Effect",
        "possibleactions" => array("endSeeOpponentsHands"),
        "transitions" => array("endTokenEffect" => 299)
    ),
    212 => array(
        "name" => "token12Effect", //sort 3 cards
        "description" => clienttranslate('Ability token: ${actplayer} is sorting the top 3 cards of the draw pile'),
        "descriptionmyturn" => clienttranslate('${you} must choose the order of the top 3 cards of the draw pile'),
        "type" => "activeplayer",
        "args" => "argToken12Effect",
        "possibleactions" => array("sort"),
        "transitions" => array("endTokenEffect" => 299)
    ),

    218 => array(
        "name" => "token18Effect",
        "description" => clienttranslate('${actplayer} must select a power card from one of the future libraries and add it to their hand'),
        "descriptionmyturn" => clienttranslate('${you} must select a power card from one of your libraries and add it to your hand'),
        "type" => "activeplayer",
        "args" => "argToken18Effect",
        "possibleactions" => array("playToken"),
        "transitions" => array("continuePlayerTurn" => 30)
    ),
    299 => array(
        "name" => "endTokenEffect",
        "description" => '',
        "descriptionmyturn" => '',
        "type" => "game",
        "action" => "stEndTokenEffect",
        "transitions" => array( "continuePlayerTurn" => 30)
    ),

    /////////// End of game ////////////////

    STATE_DEBUGGING_END => [ // active player state for debugging end of game
        "name" => "debuggingEnd",
        "description" => clienttranslate('${actplayer} Game is Over'),
        "descriptionmyturn" => clienttranslate('${you} Game is Over'),
        "type" => "activeplayer",
        "possibleactions" => ["endGame"],
        "transitions" => ["next" => 99, "loopback" => STATE_DEBUGGING_END] // 
    ],

    98 => array(
        "name" => "finalScoring",
        "description" => '',
        "type" => "game",
        "action" => "stFinalScoring",
        "transitions" => array("debugEnd" => STATE_DEBUGGING_END, "realEnd" => 99)
    ),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);
