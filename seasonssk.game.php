<?php

/**
 * seasons.game.php
 *
 * @author Grégory Isabelli <gisabelli@gmail.com>
 * @copyright Grégory Isabelli <gisabelli@gmail.com>
 * @package Game kernel
 *
 *
 * seasons main game core
 *
 */
require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');



if (!defined('NOTIF_TOKEN_CHANGE')) {
}

class SeasonsSK extends Table {
    function __construct() {
        parent::__construct();
        self::initGameStateLabels(array(
            "draftmode" => 100, "cards_version" => 101,
            "year" => 11, "month" => 12, "firstPlayer" => 13,
            "transmutationPossible" => 14,  // 0 = no transmutation possible. 1 = transmutation possible, 2 = transmultation possible with +1 bonus, 3 = ...
            "afterEffectState" => 15, "afterEffectPlayer" => 16, "currentEffect" => 17,
            "energyNbr" => 18, "toSummon" => 19, "diceSeason" => 20,
            "elementalAmulet1" => 21, "elementalAmulet2" => 22, "elementalAmulet3" => 23, "elementalAmulet4" => 24,
            "opponentTarget" => 25, "mustDrawPowerCard" => 26, "elementalAmuletFree" => 27,
            "lastCardDrawn" => 28, "firstActivation" => 29, "steadfast_die_mode" => 30,
            "discardPos" => 31, "useOtus" => 32, "lastCardPicked" => 33
        ));

        $this->cards = self::getNew("module.common.deck");
        $this->cards->init("card");
        $this->cards->autoreshuffle = true;
        $this->cards->autoreshuffle_trigger = array('obj' => $this, 'method' => 'deckAutoReshuffle');

        $this->tokensDeck = self::getNew("module.common.deck");
        $this->tokensDeck->init("ability_token");

        $this->bUpdateCardCount = false;
        $this->bUpdateScores = false;
        $this->tie_breaker_description = self::_("Cards summoned");
    }

    protected function initTable() {
        // Change $this->cards_types depending on card version
        if (self::getGameStateValue('cards_version', 1) == 1) {
            // Second edition
            foreach ($this->card_types_second_edition as $card_type_id => $card_type) {
                $this->card_types[$card_type_id] = $card_type;
            }
        }
    }

    protected function getGameName() {
        // Used for translations and stuff. Please do not modify.
        return "seasonssk";
    }

    protected function setupNewGame($players, $options = array()) {
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery($sql);

        // Create players
        $default_color = array("b4df4d", "f79a06", "9147a3", "817566");
        //$default_color = array("ff0000", "008000", "0000ff", "ffa500");
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_color);
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $default_color);
        self::reloadPlayersBasicInfos();

        $players_count = count($players);

        // Choose some dice sets
        $sql = "INSERT INTO dice (dice_season,dice_id,dice_face,dice_player_id) VALUES ";
        $sql_values = array();
        for ($season_id = 1; $season_id <= 4; $season_id++) {
            $dice_ids = array(1, 2, 3, 4, 5);
            shuffle($dice_ids);
            for ($i = 0; $i <= $players_count; $i++) // Note: number of player +1
            {
                $dice_id = array_shift($dice_ids);
                $sql_values[] = "('$season_id','$dice_id','1',NULL)";
            }
        }
        $sql .= implode(',', $sql_values);
        self::DbQuery($sql);

        self::setGameStateInitialValue('year', 1);
        self::setGameStateInitialValue('month', 1);
        self::setGameStateInitialValue('diceSeason', 0);
        self::setGameStateInitialValue('transmutationPossible', 0);
        self::setGameStateInitialValue('afterEffectPlayer', 0);
        self::setGameStateInitialValue('afterEffectState', 0);
        self::setGameStateInitialValue('currentEffect', 0);
        self::setGameStateInitialValue('energyNbr', 0);
        self::setGameStateInitialValue('toSummon', 0);
        self::setGameStateInitialValue('elementalAmulet1', 0);
        self::setGameStateInitialValue('elementalAmulet2', 0);
        self::setGameStateInitialValue('elementalAmulet3', 0);
        self::setGameStateInitialValue('elementalAmulet4', 0);
        self::setGameStateInitialValue('elementalAmuletFree', 0);
        self::setGameStateInitialValue('opponentTarget', 0);
        self::setGameStateInitialValue('mustDrawPowerCard', 0);
        self::setGameStateInitialValue('lastCardDrawn', 0);
        self::setGameStateInitialValue('firstActivation', 1);
        self::setGameStateInitialValue('steadfast_die_mode', 0);
        self::setGameStateInitialValue('discardPos', 1);
        self::setGameStateInitialValue('lastCardPicked', 0);



        self::initStat('table', 'turn_number', 0);

        self::initStat('player', 'points_crystals', 0);
        self::initStat('player', 'points_cards_on_tableau', 0);
        self::initStat('player', 'points_remaining_cards', 0);
        self::initStat('player', 'points_bonus', 0);
        self::initStat('player', 'crystal_transmutations', 0);
        self::initStat('player', 'cards_drawn', 0);
        self::initStat('player', 'cards_summoned', 0);
        self::initStat('player', 'cards_activated', 0);
        self::initStat('player', 'final_summoning', 0);
        self::initStat('player', 'final_tableau_size', 0);

        $active_player = self::activeNextPlayer();
        self::setGameStateInitialValue('firstPlayer', $active_player);

        // Initial resources
        $sql = "INSERT INTO resource (resource_player, resource_id, resource_qt) VALUES ";
        $sql_values = array();
        foreach ($players as $player_id => $player) {
            $sql_values[] = "('$player_id','1','0')";
            $sql_values[] = "('$player_id','2','0')";
            $sql_values[] = "('$player_id','3','0')";
            $sql_values[] = "('$player_id','4','0')";
        }
        $sql .= implode(',', $sql_values);
        self::DbQuery($sql);

        // Create cards
        $draftMode = self::getGameStateValue('draftmode');
        $deck_card_set = array();
        foreach ($this->card_types as $type_id => $card_type) {
            $deck_card_set[$type_id] = array('type' => $type_id, 'type_arg' => 0, 'nbr' => 2);

            if ($type_id == 222)   // Replica card => should be never present in deck
                $deck_card_set[$type_id]['nbr'] = 0;
        }

        if ($draftMode == 1) {
            // Apprentice => use cards 1 to 30 only
            foreach ($this->card_types as $type_id => $card_type) {
                if ($type_id < 1 || $type_id > 30)
                    $deck_card_set[$type_id]['nbr'] = 0;
            }

            // Apprentice => use pre-build deck
            $available_sets = array(1, 2, 3, 4);
            shuffle($available_sets);
            foreach ($players as $player_id => $player) {
                $set = array_shift($available_sets);

                $cards = $this->prebuild_decks[$set];
                $prebuild_hand = array();
                foreach ($cards as $card_id) {
                    $prebuild_hand[] = array('type' => $card_id, 'type_arg' => 0, 'nbr' => 1);
                    $deck_card_set[$card_id]['nbr']--;
                }
                $this->cards->createCards($prebuild_hand, 'hand', $player_id);
            }
        } else if ($draftMode == 2) {
            // Magician => use cards 1 to 30 only
            foreach ($this->card_types as $type_id => $card_type) {
                if ($type_id < 1 || $type_id > 30)
                    $deck_card_set[$type_id]['nbr'] = 0;
            }
        } else if ($draftMode == 3) {
            // Archmage => use cards 1 to 50
            foreach ($this->card_types as $type_id => $card_type) {
                if ($type_id < 1 || $type_id > 50)
                    $deck_card_set[$type_id]['nbr'] = 0;
            }
        } else if ($draftMode == 4) {
            // Archmage + EK => use cards 1 to 120
            foreach ($this->card_types as $type_id => $card_type) {
                if ($type_id < 1 || $type_id > 120)
                    $deck_card_set[$type_id]['nbr'] = 0;
            }
        } else if ($draftMode == 5) {
            // Archmage + POD => use cards 1 to 100 + 200 to 300
            foreach ($this->card_types as $type_id => $card_type) {
                if ($type_id > 100 && $type_id <= 120)
                    $deck_card_set[$type_id]['nbr'] = 0;
                if ($type_id > 300)
                    $deck_card_set[$type_id]['nbr'] = 0;
            }
        } else if ($draftMode == 6) {
            // Archmage + POD + EK => All cards except promo
            foreach ($this->card_types as $type_id => $card_type) {
                if ($type_id > 300)
                    $deck_card_set[$type_id]['nbr'] = 0;
            }
        } else if ($draftMode == 7) {
            // 12 Seasons tournament cards
            foreach ($this->card_types as $type_id => $card_type) {
                if (!in_array($type_id, array(
                    2, 4, 6, 7, 8, 9,
                    10, 11, 12, 14, 15, 17, 18, 19,
                    21, 26, 28,
                    30, 31, 33, 36, 37, 38,
                    40, 42, 47,
                    101, 102, 107,
                    110, 111, 112, 115, 116, 117, 118, 119,
                    120,
                    204, 206, 208, 209,
                    211, 212, 213, 215, 216, 217, 218, 220
                ))) {
                    $deck_card_set[$type_id]['nbr'] = 0;
                }
            }
        } else if ($draftMode == 8) {
            // All cards
        } else if ($draftMode == 9) {
            // Official 2022 tournament cards
            if (!in_array($type_id, array(
                1, 4, 6, 8, 9, 38, 12, 19, 21, 23, 18, 17, 25, 26, 27, 28, 31, 33, 36, 35, 39, 32, 42, 45, 102, 105, 110, 112, 106, 107, 103, 113, 115, 116, 118, 119, 120, 202, 204, 207, 212, 213, 214, 215, 216, 217, 218, 303, 302, 301
            ))) {
                $deck_card_set[$type_id]['nbr'] = 0;
            }
        }

        $this->cards->createCards($deck_card_set);
        $this->cards->shuffle("deck");

        // Then create replica cards
        $this->cards->createCards(array(array('type' => 222, 'type_arg' => 0, 'nbr' => 10)), 'replica_deck');

        if ($draftMode == 2 || $draftMode == 3 || $draftMode == 4 || $draftMode == 5 || $draftMode == 6 || $draftMode == 7 || $draftMode == 8 || $draftMode == 9) {
            // Deal 9 cards to each players
            foreach ($players as $player_id => $player) {
                $this->cards->pickCardsForLocation(9, 'deck', 'nextchoice', $player_id);
            }
        }

        $this->setupAndDealTokens();
    }

    // Get all datas (complete reset request from client side)
    protected function getAllDatas() {
        global $g_user;

        $players = self::loadPlayersBasicInfos();

        $result = array('players' => array());

        $sql = "SELECT player_id id, player_score score, player_nb_bonus_used nb_bonus, player_invocation invocation, player_reserve_size reserve_size, player_score_cristals cristalsScore, player_score_raw_cards rawCardsScore, player_score_eog_cards eogCardsScore, player_score_bonus_actions bonusActionsScore, player_score_token tokenScore, player_score_remaining_cards remainingCardsScore ";
        $sql .= "FROM player ";
        $sql .= "WHERE 1 ";
        $dbres = self::DbQuery($sql);
        while ($player = mysql_fetch_assoc($dbres)) {
            $result['players'][$player['id']] = $player;
        }

        $result['seasondices'] = self::getSeasonDices();
        $result['dice_season'] = self::getCurrentDiceSeason();

        // Material
        $result['dices'] = $this->dices;
        $result['card_types'] = self::getCardTypes();
        $result['abilityTokens'] = $this->abilityTokens;

        $result['firstplayer'] = self::getGameStateValue('firstPlayer');

        $result['year'] = self::getGameStateValue('year');
        $result['month'] = self::getGameStateValue('month');

        // Resources
        $result['resource'] = $this->getResourceStock();

        // Hand count
        $result['handcount'] = self::getCardCount();

        // Card choice
        $result['cardChoice'] = $this->cards->getCardsInLocation('choice', $g_user->get_id());
        $result['otusChoice'] = $this->cards->getCardsInLocation('otus');

        // Player hand
        $result['hand'] = $this->cards->getPlayerHand($g_user->get_id());

        // Players tableau
        $result['tableau'] = $this->cards->getCardsInLocation('tableau');

        // Resources (=energies) on cards
        $result['roc'] = self::getDoubleKeyCollectionFromDB("SELECT roc_card card, roc_id energy_id, roc_qt qt, roc_player player
                                                              FROM resource_on_card");

        $result['firstplayer'] = self::getGameStateValue('firstPlayer');

        // Cards in libraries
        $result['libraries'] = array(
            2 => $this->cards->getCardsInLocation('library2', $g_user->get_id()),
            3 => $this->cards->getCardsInLocation('library3', $g_user->get_id())
        );
        $result['counters'] = $this->argCounters();

        if ($this->isPathOfDestiny() || $this->isEnchantedKingdom()) {
            $result['tokens'] = [];
            foreach ($players as $player_id => $player) {
                $result['tokens'][$player_id] = $this->tokensDeck->getCardsInLocation('hand', $player_id);
            }
        }
        return $result;
    }


    function getGameProgression() {
        // Game progression: get player maximum score

        $month = self::getGameStateValue('month');
        $year = self::getGameStateValue('year');

        $month_from_start = min(36, max(1, 12 * ($year - 1) + $month));

        $progression = min(100, max(0, round(100 * ($month_from_start - 1) / 35)));
        return $progression;
    }

    function setupAndDealTokens() {
        if ($this->isPathOfDestiny() || $this->isEnchantedKingdom()) {
            $tokens = [];
            if ($this->isEnchantedKingdom()) {
                foreach ($this->abilityTokens as $token_id => $token) {
                    if ($token_id >= 1 && $token_id <= 12) {
                        $tokens[] = array('type' => $token_id, 'type_arg' => 0, 'nbr' => 1);
                    }
                }
            }
            if ($this->isPathOfDestiny()) {
                foreach ($this->abilityTokens as $token_id => $token) {
                    if ($token_id >= 13 && $token_id <= 18) {
                        $tokens[] = array('type' => $token_id, 'type_arg' => 0, 'nbr' => 1);
                    }
                }
            }
            $this->tokensDeck->createCards($tokens, 'deck');
            $this->tokensDeck->shuffle("deck");
            if (!$this->isEnchantedKingdom()) {
                $this->dealTokenToAllPlayers(1);
            } else {
                $this->dealTokenToAllPlayers(3);
            }
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions    (functions used everywhere)
    ////////////  
    function getPlayersIds() {
        return array_keys($this->loadPlayersBasicInfos());
    }

    function getPlayerName(int $playerId) {
        return $this->getUniqueValueFromDb("SELECT player_name FROM player WHERE player_id = $playerId");
    }

    function getPlayerScore(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_score FROM player where `player_id` = $playerId"));
    }

    function incPlayerScore(int $playerId, int $incScore, $message = '', $params = []) {

        $this->DbQuery("UPDATE player SET player_score = player_score + $incScore WHERE player_id = $playerId");

        $this->notifyAllPlayers('points', $message, $params + [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'points' => $incScore,
            'abspoints' => $incScore,
            'newScore' => $this->getPlayerScore($playerId),
        ]);
    }

    function updatePlayer(int $playerId, String $field, int $newValue) {
        $this->DbQuery("UPDATE player SET $field = $newValue WHERE player_id = $playerId");
    }

    function dealTokenToAllPlayers($number) {
        $dealt = [];
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $dealt[$player_id] = $this->pickTokens($number, $player_id);
        }
    }

    function pickTokens($nb, $player_id) {
        $cards = $this->tokensDeck->pickCards($nb, 'deck', $player_id);
        return $cards;
    }

    function isPathOfDestiny() {
        $draftMode = self::getGameStateValue('draftmode');
        return in_array($draftMode, [5, 6, 7, 8]);
    }

    function isEnchantedKingdom() {
        $draftMode = self::getGameStateValue('draftmode');
        return in_array($draftMode, [4, 6, 8]);
    }

    function getCardTypes() {
        // Get card types, with player's number adaptations
        $result = $this->card_types;

        $players = self::loadPlayersBasicInfos();
        $player_count = count($players);

        if ($player_count == 1)
            $player_count = 2;  // Note: single player mode for debug.

        $card_with_variable_cost = array(11, 33, 39, 41, 49, 120, 202, 212, 303);

        foreach ($card_with_variable_cost as $card_type_id) {
            if (isset($result[$card_type_id])) {
                foreach ($result[$card_type_id]['cost'] as $ress_id => $cost_array) {
                    if (is_array($cost_array))
                        $result[$card_type_id]['cost'][$ress_id] = $cost_array[$player_count];
                }
            }
        }

        return $result;
    }


    function notifyUpdateCardCount() {
        $this->bUpdateCardCount = true;
    }
    function notifyUpdateScores() {
        $this->bUpdateScores = true;
    }
    function onEndAjaxAction() {
        if ($this->bUpdateCardCount) {
            $this->notifyAllPlayers("updateCardCount", '', array('count' => self::getCardCount()));
        }
        if ($this->bUpdateScores) {
            $this->notifyAllPlayers("updateScores", '', array(
                'scores' => self::getCollectionFromDB("SELECT player_id, player_score FROM player", true)
            ));
        }
    }

    function argCounters() {
        $players = self::getCollectionFromDB("SELECT player_id, player_score, player_invocation FROM player", false);
        $counters = array();
        foreach ($players as $player_id => $player) {
            $counters['cristals_counter_' . $player_id] = array('counter_name' => 'cristals_counter_' . $player_id, 'counter_value' => $player['player_score']);
        }
        return $counters;
    }

    // Count cards in game: card in hands
    function getCardCount() {
        $result = $this->cards->countCardsByLocationArgs('hand');

        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            if (!isset($result[$player_id]))
                $result[$player_id] = 0;
        }

        return $result;
    }

    function getCurrentSeason() {
        $season = self::getGameStateValue('month');
        return floor(($season - 1) / 3) + 1;
    }

    // Return from season the seasons dices are taken (with Temporal Boots,
    // this may be different from the actual current season).
    // A special value of 0 means that we're still setting up the game
    // (drafting or distributing cards in 3 years).
    function getCurrentDiceSeason() {
        return self::getGameStateValue('diceSeason');
    }

    function getSeasonDices() {
        $season = self::getCurrentDiceSeason();
        if ($season == 0)
            return self::getObjectListFromDB("SELECT dice_season season, dice_id id, dice_face face, dice_player_id player FROM dice");
        else
            return self::getObjectListFromDB("SELECT dice_season season, dice_id id, dice_face face, dice_player_id player FROM dice WHERE dice_season='$season'");
    }

    // Give to players library cards they kept in their libraries
    function giveLibaryCardsToPlayers($year) {
        $cards = $this->cards->getCardsInLocation('library' . $year);
        if (count($cards) > 0) {
            // There are some cards to distribute
            $players = self::loadPlayersBasicInfos();
            foreach ($players as $player_id => $player) {
                foreach ($cards as $card) {
                    $cards_for_player = array();
                    if ($card['location_arg'] == $player_id) {
                        // A card for this player
                        $cards_for_player[] = $card;
                    }

                    // Give these card to this player
                    $this->cards->moveAllCardsInLocation('library' . $year, 'hand', $player_id, $player_id);

                    // Notify
                    self::notifyPlayer($player_id, "pickPowerCards", '', array("cards" => $cards_for_player, "fromLibrary" => true));
                }
            }

            self::notifyAllPlayers('pickLibraryCards', clienttranslate("Everyone draw cards from his library"), array());
        }

        self::notifyUpdateCardCount();
    }

    // Check resources
    function checkResourceCost($player_id, $cost) {
        // Check resources
        $resource = self::getResourceStock($player_id);
        foreach ($cost as $resource_id => $qt_needed) {
            if ($qt_needed > 0) {
                $bNotEnoughResources = false;

                if (!isset($resource[$resource_id]))
                    $bNotEnoughResources = true;
                if ($resource[$resource_id] < $qt_needed)
                    $bNotEnoughResources = true;

                if ($bNotEnoughResources) {
                    // Check if there are some resources unselected on an amulet of water

                    $sql = "SELECT roc_qt
                            FROM resource_on_card
                            INNER JOIN card ON card_id=roc_card
                            WHERE roc_id='$resource_id'
                            AND roc_qt>0
                            AND roc_player='$player_id'
                            AND card_location='tableau'
                            AND card_location_arg='$player_id'
                            AND card_type IN ('4','118;4')";

                    if (self::getUniqueValueFromDB($sql) !== null)
                        throw new feException(self::_("To execute this action you need more: ") . ' ' . $this->energies[$resource_id]['nametr'] . '. (' . self::_("Did you selected the needed energies on your Amulet of Water?") . ')', true);
                    else
                        throw new feException(self::_("To execute this action you need more: ") . ' ' . $this->energies[$resource_id]['nametr'], true);
                }
            }
        }
    }

    // Check a resource cost for the active player, including Amulet of Water (we should
    // also check Staff of Winter, but so far this function isn't called with Earth
    // energy). Throw an feException if the active player cannot pay the cost.
    function checkTotalResourceCost($cost) {
        $stock = self::getTotalResourceStock();
        foreach ($cost as $resource_id => $qt_needed) {
            if ($qt_needed > $stock[$resource_id]) {
                throw new feException(self::_("To execute this action you need more: ") . ' ' . $this->energies[$resource_id]['nametr'], true);
            }
        }
    }

    // Check a cost (POSITIVE) against a stock
    // If there is NO energy, return false (wrong cost)
    function checkCostAgainstStock($cost, $stock) {
        $total = 0;
        foreach ($cost as $resource_id => $qt_needed) {
            if ($qt_needed > 0 && $resource_id != 0) {
                $total += $qt_needed;
                if (!isset($stock[$resource_id]))
                    return false;
                if ($stock[$resource_id] < $qt_needed)
                    return false;
            }
        }
        if ($total == 0)
            return false;
        return true;
    }

    // Apply a resource delta to player's stock and notify
    function applyResourceDelta($player_id, $resources_delta, $bCheckBefore = true) {
        if ($bCheckBefore) {
            $cost = array();
            foreach ($resources_delta as $resource_id => $delta) {
                if ($delta < 0)
                    $cost[$resource_id] = -$delta;
            }
            if (count($cost) > 0)
                self::checkResourceCost($player_id, $cost);
        }

        foreach ($resources_delta as $resource_id => $delta) {
            $sql = "UPDATE resource SET resource_qt=resource_qt+$delta ";
            $sql .= "WHERE resource_player='$player_id' AND resource_id='$resource_id' ";
            self::DbQuery($sql);
        }

        self::notifyAllPlayers("resourceStockUpdate", '', array('player_id' => $player_id, 'delta' => $resources_delta));
    }

    function deckAutoReshuffle() {
        // Deck is reshuffled
        self::notifyAllPlayers('reshuffle', clienttranslate('No more cards in deck ! The discard pile is shuffled back into the draw pile'), array());
    }

    function getResourceStock($player_id = null) {
        $sql = "SELECT resource_player, resource_id, resource_qt FROM resource ";
        if ($player_id != null)
            $sql .= "WHERE resource_player='$player_id' ";

        $dbres = self::DbQuery($sql);
        while ($row = mysql_fetch_assoc($dbres)) {
            if (!isset($result[$row['resource_player']]))
                $result[$row['resource_player']] = array();
            $result[$row['resource_player']][$row['resource_id']] = $row['resource_qt'];
        }

        if ($player_id != null)
            return $result[$player_id];
        else
            return $result;
    }

    function getAmuletOfWaterStock($player_id) {
        return self::getCollectionFromDB("SELECT roc_id, SUM( roc_qt )
                                                   FROM resource_on_card
                                                   INNER JOIN card ON card_id=roc_card
                                                   WHERE card_location='tableau' AND card_location_arg='$player_id'
                                                   AND card_type IN ('4','118;4')
                                                   GROUP BY roc_id ", true);   // 4 = amulet of water
    }

    // All available energy tokens for the active player, including Amulet of Water.
    // Staff of Winter effect is not considered.
    function getTotalResourceStock() {
        $stock = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
        $player_id = self::getActivePlayerId();
        foreach (self::getResourceStock($player_id) as $type => $qt) {
            $stock[$type] += $qt;
        }
        foreach (self::getAmuletOfWaterStock($player_id) as $type => $qt) {
            $stock[$type] += $qt;
        }
        return $stock;
    }

    function countPlayerEnergies($player_id, $bIncludeAmulet = false) {
        $result = self::getUniqueValueFromDb("SELECT SUM( resource_qt ) FROM resource WHERE resource_player='$player_id' ");

        if ($bIncludeAmulet) {
            // Include amulet of water energies
            $result += self::getUniqueValueFromDb("SELECT SUM( roc_qt )
                                                   FROM resource_on_card
                                                   INNER JOIN card ON card_id=roc_card
                                                   WHERE card_location='tableau' AND card_location_arg='$player_id'
                                                   AND card_type IN ('4','118;4') ", true);   // 4 = amulet of water
        }

        return $result;
    }

    // Check some player energy. If there are too much energy, return false
    function checkEnergy($player_id) {
        $energy_count = self::countPlayerEnergies($player_id);
        $reserve_size = self::getPlayerReserveSize($player_id);

        if ($energy_count > $reserve_size) {
            return false;
        }

        return true;
    }

    function getPlayerReserveSize($player_id) {
        return self::getUniqueValueFromDB("SELECT player_reserve_size FROM player WHERE player_id='$player_id' ");
    }

    function adaptReserveSize($player_id) {
        $current_reserve_size = self::getUniqueValueFromDB("SELECT player_reserve_size
                FROM player
                WHERE player_id='$player_id' ");

        // Check if there are some Bespelled Grimoire (=18) in player's tableau (or Mesodae’s Lantern / Statue of Eolis)
        $cards = self::getAllCardsOfTypeInTableau(array(18, 107, 106), $player_id);

        $new_reserve_size = 7;

        if (isset($cards[18])) {
            if (count($cards[18]) != 0)
                $new_reserve_size = 10;
        }

        if (isset($cards[106]))
            $new_reserve_size -= count($cards[106]);

        if (isset($cards[107]))
            $new_reserve_size -= count($cards[107]);

        if ($new_reserve_size != $current_reserve_size) {
            self::DbQuery("UPDATE player SET player_reserve_size='$new_reserve_size' WHERE player_id='$player_id' ");

            $players = self::loadPlayersBasicInfos();

            self::notifyAllPlayers('reserveSizeChange', clienttranslate(' ${player_name} can now store up to ${reserve_size} energies'), array(
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'reserve_size' => $new_reserve_size
            ));
        }
    }

    function insertEffect($card_id, $effect_type) {
        $card = $this->cards->getCard($card_id);
        $card_type_id = self::ct($card['type']);

        $sql = "INSERT INTO effect (effect_card, effect_type, effect_card_type) VALUES ('$card_id','$effect_type','$card_type_id') ";
        self::DbQuery($sql);
    }

    // Apply all cards effect in current effect table stack,

    //  then get back to the specified state with the specified active player
    function applyCardsEffect($returnState, $active_player_id) {
        if ($returnState == 'playerTurn')
            self::setGameStateInitialValue('afterEffectState', 1);
        if ($returnState == 'endOfRound')
            self::setGameStateInitialValue('afterEffectState', 2);
        if ($returnState == 'newRound')
            self::setGameStateInitialValue('afterEffectState', 3);
        if ($returnState == 'newYear')
            self::setGameStateInitialValue('afterEffectState', 4);
        if ($returnState == 'beforeTurn')
            self::setGameStateInitialValue('afterEffectState', 5);

        self::setGameStateInitialValue('afterEffectPlayer', $active_player_id);

        $this->gamestate->nextState('cardEffect');
    }

    function getCardEffectMethod($card_name, $effect) {
        $card_name_normalized = strtolower(str_replace('-', '_', str_replace('’', '_', str_replace(' ', '_', $card_name))));
        return $card_name_normalized . '_' . $effect;
    }

    function getCurrentEffectCardName() {
        $currentEffect = self::getGameStateValue('currentEffect');
        $card_type_id = self::getUniqueValueFromDB("SELECT effect_card_type
                                          FROM effect
                                          WHERE effect_id='$currentEffect'");

        return $this->card_types[self::ct($card_type_id)]['name'];
    }

    function getCurrentEffectCard() {
        $currentEffect = self::getGameStateValue('currentEffect');
        return self::getObjectFromDB("SELECT card_id, card_type
                                          FROM effect
                                          INNER JOIN card ON card_id=effect_card
                                          WHERE effect_id='$currentEffect'");
    }

    function getCurrentEffectCardId() {
        $currentEffect = self::getGameStateValue('currentEffect');
        $card_id = self::getUniqueValueFromDB("SELECT effect_card
                                          FROM effect
                                          WHERE effect_id='$currentEffect'");
        return $card_id;
    }

    function getCurrentEffectCardOwner() {
        $currentEffect = self::getGameStateValue('currentEffect');
        return self::getUniqueValueFromDB("SELECT card_location_arg
                                          FROM effect
                                          INNER JOIN card ON card_id=effect_card
                                          WHERE effect_id='$currentEffect'");
    }

    // Return all cards of given type in all tableau / in given player tableau
    // If card_types_id = null, return all cards in tableau
    // Result if player_id = null:  card_type => player_id => array( card_id )
    // Result if player_id is specified:   card_type => array( card_id )
    function getAllCardsOfTypeInTableau($card_types_id, $player_id = null, $bExcludeActivatedCard = false) {
        $sql = "SELECT card_id id, card_type type, card_location_arg player
                FROM card
                WHERE card_location='tableau' ";
        if ($player_id !== null)
            $sql .= "AND card_location_arg='$player_id' ";
        if ($bExcludeActivatedCard)
            $sql .= "AND card_type_arg='0' ";
        if ($card_types_id !== null) {
            $all_card_types_id = array();
            foreach ($card_types_id as $card_type_id) {
                $all_card_types_id[] = $card_type_id;
                $all_card_types_id[] = '118;' . $card_type_id;
            }

            $sql .= "AND card_type IN ('" . implode("','", $all_card_types_id) . "') ";
        }

        $cards = self::getObjectListFromDB($sql);
        $result = array();
        foreach ($cards as $card) {
            $card_type_id = self::ct($card['type']);
            if (!isset($result[$card_type_id]))
                $result[$card_type_id] = array();

            if ($player_id === null) {
                if (!isset($result[$card_type_id][$card['player']]))
                    $result[$card_type_id][$card['player']] = array();

                $result[$card_type_id][$card['player']][] = $card['id'];
            } else
                $result[$card_type_id][] = $card['id'];
        }

        return $result;
    }

    // Trigger an effect linked to an event for all cards of specified type specified
    // If player_id is null => look into all cards in all tableau. Otherwise look into tableau of specified player.
    function triggerEffectsOnEvent($event, $card_types_id, $player_id = null, $bIfNotActivated = false, $exclude_player = null) {
        $bAtLeastOneEffect = false;

        $all_card_types_id = array();
        foreach ($card_types_id as $card_type_id) {
            $all_card_types_id[] = $card_type_id;
            $all_card_types_id[] = '118;' . $card_type_id;
        }


        $sql = "SELECT card_id, card_type, card_location_arg location_arg
                FROM card
                WHERE card_location='tableau' ";
        if ($player_id !== null)
            $sql .= "AND card_location_arg='$player_id' ";
        $sql .= " AND card_type IN ('" . implode("','", $all_card_types_id) . "') ";

        if ($bIfNotActivated)
            $sql .= " AND card_type_arg='0' ";

        $cards = self::getObjectListFromDB($sql);

        foreach ($cards as $card) {
            if ($exclude_player !== null && $card['location_arg'] == $exclude_player) {   // Do nothing
            } else {
                self::insertEffect($card['card_id'], $event);
                $bAtLeastOneEffect = true;
            }
        }

        return $bAtLeastOneEffect;
    }

    function getStandardArgs($withCardInfo = true) {
        if ($withCardInfo) {
            $currentEffect = self::getGameStateValue('currentEffect');
            $card_type_id = self::ot(self::getUniqueValueFromDB("SELECT card_type
                                          FROM effect
                                          INNER JOIN card ON card_id=effect_card
                                          WHERE effect_id='$currentEffect'"));

            $card_type = $this->card_types[$card_type_id];
        }
        return array(
            'i18n' => array('card_name'),
            'player_id' => self::getActivePlayerId(),
            'player_name' => self::getActivePlayerName(),
            'card_name' => $withCardInfo ? $card_type['name'] : _("Ability token")
        );
    }

    // Original card type (see "Raven the usurpateur")
    function ot($type_id) {
        $sep = strpos($type_id, ';');
        if ($sep === false)
            return $type_id;
        else
            return substr($type_id, 0, $sep);
    }
    // Current card type (see "Raven the usurpateur")
    function ct($type_id) {
        $sep = strpos($type_id, ';');
        if ($sep === false)
            return $type_id;
        else
            return substr($type_id, $sep + 1);
    }

    // Trigger the gain of final points at the end of the game
    function gainEndOfGameEffectsPoints() {
        $players = self::loadPlayersBasicInfos();
        $cardWithPoints = self::getAllCardsOfTypeInTableau(array(
            19, // Ragfield’s Helm
            45, // Lantern of Xidit
            46, // Sealed Chest of Urm
            112 // Jewel of the Ancients
        ));
        $scores = [];
        foreach ($players as $player_id => $player) {
            $scores[$player_id] = 0;
        }
        foreach ($cardWithPoints as $card_type_id => $theseplayers) {
            foreach ($theseplayers as $player_id => $cards) {
                $totalPerPlayer = 0;
                foreach ($cards as $i => $card_id) {
                    $card_count = count($cards);

                    if ($card_type_id == 19) {
                        // Ragfield’s Helm: if you have more power cards in play than each of your opponents, gain 20 additional crystals.
                        $player_to_powercards = $this->cards->countCardsByLocationArgs('tableau');
                        $players_with_maximum = getKeysWithMaximum($player_to_powercards);
                        if (count($players_with_maximum) == 1) {
                            if (reset($players_with_maximum) == $player_id) {
                                $points = self::checkMinion(20, $player_id);
                                $totalPerPlayer += $points;
                                // => the owner of Ragfield’s Helm has more power cards in play => 20 pts
                                self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");

                                self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                                    'i18n' => array('card_name'),
                                    'player_id' => $player_id,
                                    'player_name' => $players[$player_id]['player_name'],
                                    'points' => $points,
                                    'card_name' => $this->card_types[19]['name']
                                ));
                                self::notifyUpdateScores();
                            }
                        }
                    } else if ($card_type_id == 45) {
                        // Lantern of Xidit: each energy in reserve => +3 points / energy

                        $energyNbr = self::countPlayerEnergies($player_id);
                        $points = self::checkMinion(3 * $energyNbr, $player_id);
                        $totalPerPlayer += $points;
                        self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");

                        self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                            'i18n' => array('card_name'),
                            'player_id' => $player_id,
                            'player_name' => $players[$player_id]['player_name'],
                            'points' => $points,
                            'card_name' => $this->card_types[45]['name']
                        ));
                        self::notifyUpdateScores();
                    } else if ($card_type_id == 46) {
                        // Sealed Chest of Urm: If only magic items in play => +20 points

                        $familiar_item_nbr = self::countCardOfCategoryInTableau($player_id, 'f');

                        if ($familiar_item_nbr == 0) {
                            $points = self::checkMinion(20, $player_id);
                            $totalPerPlayer += $points;
                            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");

                            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                                'i18n' => array('card_name'),
                                'player_id' => $player_id,
                                'player_name' => $players[$player_id]['player_name'],
                                'points' => $points,
                                'card_name' => $this->card_types[46]['name']
                            ));
                            self::notifyUpdateScores();
                        }
                    } else if ($card_type_id == 112) {
                        // Jewel of the Ancients: if more than 3 token on the Jewel: +35pts (otherwise -10pts)
                        $energy_on_cauldron = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);
                        $total_energy = 0;
                        foreach ($energy_on_cauldron as $ress_id => $ress_qt) {
                            $total_energy += $ress_qt;
                        }

                        if ($total_energy >= 3) {
                            $points = self::checkMinion(35, $player_id);
                            $totalPerPlayer += $points;
                            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");

                            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                                'i18n' => array('card_name'),
                                'player_id' => $player_id,
                                'player_name' => $players[$player_id]['player_name'],
                                'points' => $points,
                                'card_name' => $this->card_types[112]['name']
                            ));
                            self::notifyUpdateScores();
                        } else {
                            $loose = 10;
                            $totalPerPlayer -= $loose;
                            self::DbQuery("UPDATE player SET player_score=GREATEST( 0,player_score-$loose ) WHERE player_id='$player_id'");
                            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} looses ${points_disp} crystals'), array(
                                'i18n' => array('card_name'),
                                'player_id' => $player_id,
                                'points' => -$loose,
                                'points_disp' => abs($loose),
                                'player_name' => $players[$player_id]['player_name'],
                                'card_name' => $this->card_types[112]['name']
                            ));
                            self::notifyUpdateScores();
                        }
                    }
                }
                //check total
                $this->updatePlayer($player_id, "player_score_eog_cards", $totalPerPlayer);
                $scores[$player_id] = $totalPerPlayer;
            }
        }
        foreach ($players as $player_id => $player) {
            $this->notifyAllPlayers('eogCardsScore', '', [
                'playerId' => $player_id,
                'points' => $scores[$player_id],
            ]);
        }
    }

    // Return number of cards of given category in specified player's tableau
    function countCardOfCategoryInTableau($player_id, $card_category, $bCheckInHands = false) {
        $location = $bCheckInHands ? 'hand' : 'tableau';

        $sql = "SELECT card_id id, card_type type
                FROM card
                WHERE card_location='$location' AND card_location_arg='$player_id'";


        $item_nbr = 0;
        $cards = self::getObjectListFromDB($sql);
        foreach ($cards as $card) {
            if ($this->card_types[self::ot($card['type'])]['category'] == $card_category) {
                $item_nbr++;
            }
        }

        return $item_nbr;
    }

    // Return true if we are able to summon a new card according to current summoning gauge
    function checkSummoningGauge() {
        $player_id = self::getActivePlayerId();
        $summoning_gauge = self::getUniqueValueFromDB("SELECT player_invocation FROM player WHERE player_id='$player_id'");
        $cards_played = $this->cards->countCardInLocation("tableau", $player_id);
        if ($cards_played >= $summoning_gauge)
            return false;
        else
            return true;
    }

    // Increase summoning gauge of player by $nbr by effect $card_name
    // (or the seasons die if $card_name is empty)
    function increaseSummoningGauge($player_id, $card_name = "", $nbr = 1) {
        $summoning_gauge = self::getUniqueValueFromDB("SELECT player_invocation FROM player WHERE player_id='$player_id'");
        $new_summoning_gauge = $summoning_gauge + $nbr;
        if ($new_summoning_gauge > 15)
            $new_summoning_gauge = 15;

        self::DbQuery("UPDATE player SET player_invocation='$new_summoning_gauge' WHERE player_id='$player_id'");

        $players = self::loadPlayersBasicInfos();
        $notifArgs = array(
            'i18n' => array('card_name'),
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'nbr' => $nbr,
            'old' => $summoning_gauge,
            'new' => $new_summoning_gauge,
            'card_name' => $card_name
        );
        if ($summoning_gauge >= 15) {
            self::notifyAllPlayers("incInvocationLevel", clienttranslate('${player_name} already has the maximum summoning gauge (${new})'), $notifArgs);
        } else if ($card_name == "") {
            self::notifyAllPlayers("incInvocationLevel", clienttranslate('${player_name} increases his summoning gauge from ${old} to ${new} with the die'), $notifArgs);
        } else {
            self::notifyAllPlayers("incInvocationLevel", clienttranslate('${card_name}: ${player_name} increases his summoning gauge from ${old} to ${new}'), $notifArgs);
        }
    }

    function checkPlayerCanSacrificeCard($player_id, $multiplier = 1) {
        // Check if this player can sacrifice a card, taking into account Crystal Titan
        $titans = self::getAllCardsOfTypeInTableau(array(303));
        $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
        $players = self::loadPlayersBasicInfos();
        $titan_total_cost = 0;
        if (isset($titans[303])) {
            $titans = $titans[303];
            foreach ($titans as $titanowner_id => $titan_cards) {
                if ($titanowner_id != $player_id) {
                    foreach ($titan_cards as $titan_card) {
                        $titan_total_cost += 3;
                    }
                }
            }
        }

        if (($titan_total_cost * $multiplier)  > $player_score)
            return false;
        else
            return true;
    }

    // Clean a card in database when it leaves a tableau
    function cleanTableauCard($card_id, $player_id, $bSacrifice = true, $bForceSacrifice = false) {
        $card = self::getObjectFromDB("SELECT card_type, card_location, card_location_arg FROM card WHERE card_id='$card_id'");
        $card_type_id = $card['card_type'];

        if ($card_type_id != self::ot($card_type_id))
            self::DbQuery("UPDATE card SET card_type='" . self::ot($card_type_id) . "' WHERE card_id='$card_id'");

        self::DbQuery("DELETE FROM resource_on_card WHERE roc_card='$card_id' ");
        self::DbQuery("UPDATE card SET card_type_arg='0' WHERE card_id='$card_id' "); // Inactivate card in case it is played again this turn

        // If there is a Crystal Titan somewhere => must give 3 crystal
        if ($bSacrifice) {
            $titans = self::getAllCardsOfTypeInTableau(array(303));
            $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
            $players = self::loadPlayersBasicInfos();
            if (isset($titans[303])) {
                $titans = $titans[303];
                foreach ($titans as $titanowner_id => $titan_cards) {
                    if ($titanowner_id != $player_id) {
                        foreach ($titan_cards as $titan_card) {
                            // player must give 3 crystals to titan owner
                            if ($player_score < 3) {
                                if ($bForceSacrifice) {
                                    // We force the sacrifice (ex: for Raven)
                                } else
                                    throw new feException("(bug) Not enough crystals to pay Crystal Titans"); // Not supposed to happened
                            }
                            self::DbQuery("UPDATE player SET player_score=GREATEST(0, player_score-3) WHERE player_id='$player_id'");
                            $player_score -= 3;
                            self::notifyAllPlayers('winPoints', '', array('player_id' => $player_id, 'points' => -3));

                            $points = self::checkMinion(3, $titanowner_id);
                            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$titanowner_id'");
                            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gives 3 crystals to ${player_name2}'), array(
                                'i18n' => array('card_name'),
                                'player_id' => $titanowner_id,
                                'points' => $points,
                                'player_name' => self::getCurrentPlayerName(),
                                'player_name2' => $players[$titanowner_id]['player_name'],
                                'card_name' => $this->card_types['303']['name']
                            ));
                            self::notifyUpdateScores();
                        }
                    }
                }
            }
        }

        if (self::ct($card_type_id) == 217) {
            self::cleanArgosianLock($card_id);
        }

        if (self::ot($card_type_id) == 118) {
            // It's a raven => may remove a raven entry
            self::DbQuery("DELETE FROM raven WHERE raven_id='$card_id'");
        }

        // Cursed Treatise of Arus sacrificed => all player energies are discarded
        if ($bSacrifice && self::ct($card_type_id) == 42) {
            $playerStock = self::getResourceStock($player_id);
            $cost = array();
            foreach ($playerStock as $ress_id => $ress_qt) {
                $cost[$ress_id] = -$ress_qt;
            }
            self::applyResourceDelta($player_id, $cost);
            self::notifyAllPlayers('cursedTreatise', clienttranslate('${card_name}: ${player_name} discards all his energy tokens'), array(
                'i18n' => array('card_name'),
                'card_name' => $this->card_types[self::ot($card_type_id)]['name'],
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
            ));
        }

        // Check if the reserve size needs to be adapted
        if (self::ct($card_type_id) == 18 || self::ct($card_type_id) == 106 || self::ct($card_type_id) == 107) {
            self::adaptReserveSize($player_id);
        }

        // Check some raven is copying the card
        $raven_ids = self::getObjectListFromDB("SELECT raven_id FROM raven WHERE raven_original_item='$card_id'", true);
        foreach ($raven_ids as $raven_id) {
            // Okay, let's sacrifice this card
            $raven = $this->cards->getCard($raven_id);
            $this->cards->moveCard($raven_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($raven_id, $raven['location_arg'], true, true);
            self::notifyUpdateCardCount();
            $players = self::loadPlayersBasicInfos();

            $card_name = self::getCurrentEffectCardName();

            self::notifyAllPlayers('discardFromTableau', clienttranslate('${card_name}: ${player_name} sacrifices ${sacrified}'), array(
                'i18n' => array('card_name', 'sacrified'),
                'card_name' => $card_name,
                'card_id' => $raven_id,
                'player_id' => $raven['location_arg'],
                'player_name' => $players[$raven['location_arg']]['player_name'],
                'sacrified' => $this->card_types[118]['name']
            ));
        }
    }

    function cleanArgosianLock($card_id) {
        // It's an argosian => must remove the associated lock
        $associated_card_id = self::getUniqueValueFromDB("SELECT argosian_locked_item FROM argosian WHERE argosian_id='$card_id'");

        if ($associated_card_id) {
            self::DbQuery("DELETE FROM argosian WHERE argosian_id='$card_id'");

            // Restore the associated card original type
            $associated_card = $this->cards->getCard($associated_card_id);
            $associated_card_type_id = self::ot($associated_card['type']);

            // Change the type of the target card into the type "argosian" (217)
            self::DbQuery("UPDATE card SET card_type='$associated_card_type_id' WHERE card_id='$associated_card_id' ");

            self::notifyAllPlayers('removeLock', '', array(
                'card_id' => $associated_card_id
            ));

            if ($associated_card_type_id == 118) {
                // Particular case: original card was a Raven => must reconfigure it
                $raven_original_id = self::getUniqueValueFromDB("SELECT raven_original_item FROM raven WHERE raven_id='$associated_card_id'");
                if ($raven_original_id) {
                    $raven_original = $this->cards->getCard($raven_original_id);
                    $raven_original_type_id = self::ot($raven_original['type']);

                    // Change the type of Raven into the new card type
                    self::DbQuery("UPDATE card SET card_type='118;$raven_original_type_id' WHERE card_id='$associated_card_id' ");

                    self::notifyAllPlayers('ravenCopy', '', array(
                        'player_id' => $associated_card['location_arg'],
                        'card_id' => $associated_card_id,
                        'card_type' => '118;' . $raven_original_type_id
                    ));
                }
            }

            // If an effect on reserve has been restored, we must re-adapt reserve size
            self::adaptReserveSize($associated_card['location_arg']);
        }

        // ... it could also by a locked item because we give them the 217 current type
        self::DbQuery("DELETE FROM argosian WHERE argosian_locked_item='$card_id'");
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    //////////// 
    function score() {
        $this->gamestate->nextState('finalScoring');
    }

    function draftChooseCard($card_id) {
        self::checkAction('draftChooseCard');

        $player_id = self::getCurrentPlayerId();

        // Get cards details
        $card = $this->cards->getCard($card_id);

        if (!$card)
            throw new feException("Card not found");
        if ($card['location'] != 'choice' || $card['location_arg'] != $player_id)
            throw new feException("This card is not available");

        // Place this card in player's hand
        $this->cards->moveCard($card_id, 'hand', $player_id);

        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

        // All remaining choices => to next player choice
        $players = self::loadPlayersBasicInfos();
        $next_player_table = self::createNextPlayerTable(array_keys($players));
        $nextPlayer = $next_player_table[$player_id];

        $this->cards->moveAllCardsInLocation('choice', 'nextchoice', $player_id, $nextPlayer);

        self::notifyUpdateCardCount();

        $this->gamestate->setPlayerNonMultiactive($player_id, 'everyoneChoosed');
    }

    function draftTwist($card_id) {
        self::checkAction('draftTwist');

        $player_id = self::getCurrentPlayerId();

        // Get cards details
        $card = $this->cards->getCard($card_id);

        if (!$card)
            throw new feException("Card not found");
        if ($card['location'] != 'choice' || $card['location_arg'] != $player_id)
            throw new feException("This card is not available");

        // Place this card in player's hand
        $this->cards->moveCard($card_id, 'hand', $player_id);

        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

        // All remaining choices => to discard
        $players = self::loadPlayersBasicInfos();
        $next_player_table = self::createNextPlayerTable(array_keys($players));
        $nextPlayer = $next_player_table[$player_id];

        // Discard all choice
        $this->cards->moveAllCardsInLocation('choice', 'discard', $player_id, self::incGameStateValue('discardPos', 1));

        self::notifyUpdateCardCount();

        // Has player another twist ?
        $total_cards = $this->cards->countCardsInLocation('hand', $player_id);


        if ($total_cards == 9)
            $this->gamestate->setPlayerNonMultiactive($player_id, 'draftTwist');
        else {
            // Pick 2 cards for choice
            $cards = $this->cards->pickCardsForLocation(2, 'deck', 'choice', $player_id);
            self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));
        }
    }

    function chooseLibrary($card_ids) {
        self::checkAction('chooseLibrary');

        $player_id = self::getCurrentPlayerId();

        // Check these cards are in player's hand
        $cards = $this->cards->getCards($card_ids);

        foreach ($cards as $card) {
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id)
                throw new feException("This card is not in your hand");
        }

        // 3 cards (check)
        if (count($cards) != 3)
            throw new feException(self::_('You must choose 3 cards'), true);

        // Get current library
        if (self::checkAction('chooseLibrary2', false))
            $library = 2;
        else
            $library = 3;

        // Place these cards in library
        $this->cards->moveCards($card_ids, 'library' . $library, $player_id);

        self::notifyAllPlayers('placeInLibrary', clienttranslate('${player_name} places 3 cards in his library'), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName()
        ));

        self::notifyPlayer($player_id, 'placeMyInLibrary', '', array(
            'player_id' => $player_id,
            'cards' => $cards,
            'year' => $library
        ));

        self::notifyUpdateCardCount();

        // This player => no more active
        $this->gamestate->setPlayerNonMultiactive($player_id, 'endBuildLibrary');
    }

    function chooseLibrarynew($card_ids) {
        self::checkAction('chooseLibrarynew');

        if (count($card_ids) != 9)
            throw new feException("Invalid number of cards: " . count($card_ids));

        $player_id = self::getCurrentPlayerId();

        // Check these cards are in player's hand
        $cards = $this->cards->getCards($card_ids);

        $card_no = 0;

        $year_to_library = array(1 => array(), 2 => array(), 3 => array());

        foreach ($card_ids as $card_id) {
            $card = $cards[$card_id];

            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id)
                throw new feException("This card is not in your hand");

            if (floor($card_no / 3) == 0) {
                /// Keep in hand (Year I)
                $year_to_library[1][$card['id']] = $card;
            } else if (floor($card_no / 3) == 1) {
                // Year II
                $year_to_library[2][$card['id']] = $card;
            } else if (floor($card_no / 3) == 2) {
                // Year III
                $year_to_library[3][$card['id']] = $card;
            }

            $card_no++;
        }

        foreach ($year_to_library as $library => $cards) {
            $card_ids = array_keys($cards);

            if ($library != 1) {
                // Place these cards in library
                $this->cards->moveCards($card_ids, 'library' . $library, $player_id);
            }

            self::notifyPlayer($player_id, 'placeMyInLibrarynew', '', array(
                'player_id' => $player_id,
                'cards' => $cards,
                'year' => $library
            ));
        }
        self::notifyUpdateCardCount();

        // This player => no more active
        $state = $this->isEnchantedKingdom() ? "chooseToken" : 'chooseLibrarynew';
        $this->gamestate->setPlayerNonMultiactive($player_id, $state);
    }

    function chooseDie($die_id) {
        self::checkAction('chooseDie');

        $season = self::getCurrentDiceSeason();

        // Check if die is available
        $diceowner = self::getObjectFromDb("SELECT dice_player_id, dice_face FROM dice WHERE dice_season='$season' AND dice_id='$die_id' ");
        if ($diceowner['dice_player_id'] !== null) {
            throw new feException("This dice is not available");
        }

        // Okay, dice is available
        $player_id = self::getActivePlayerId();
        $sql = "UPDATE dice SET dice_player_id='$player_id' WHERE dice_season='$season' AND dice_id='$die_id' ";
        self::DbQuery($sql);

        self::notifyAllPlayers('chooseDie', clienttranslate('${player_name} chooses a die'), array(
            'player_name' => self::getCurrentPlayerName(),
            'player_id' => $player_id,
            'die' => $die_id,
            'die_type' => $season . $die_id . $diceowner['dice_face']
        ));

        $this->gamestate->nextState('chooseDie');
    }

    function endTurn() {
        self::checkAction('endTurn');

        if (self::getGameStateValue('mustDrawPowerCard') == 1)
            throw new feException(self::_("You must draw a power card before the end of your turn"), true);


        $this->gamestate->nextState('endOfTurn');
    }

    // Apply cost with given energy on given Amulet of Water cards
    function applyAmuletOfWaterEnergyCost($energies) {
        // Get all energies on amulet of water of player
        $player_id = self::getActivePlayerId();
        $aow = self::getDoubleKeyCollectionFromDB("SELECT roc_card card, roc_id energy_id, roc_qt qt
                                                    FROM resource_on_card
                                                    INNER JOIN card ON card_id=roc_card AND card_type IN ('4','118;4')
                                                    WHERE roc_player='$player_id'");

        $cost = array(0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0);

        foreach ($energies as $energy) {
            if ($energy >= 10) {
                $real_energy = $energy % 10;
                $card_id = intval(floor($energy / 10));

                if (!isset($aow[$card_id]))
                    throw new feException("Energy selection on Amulet of Water cannot be found");

                if (!isset($aow[$card_id][$real_energy]))
                    throw new feException("Energy selection on Amulet of Water cannot be found");

                if ($aow[$card_id][$real_energy] <= 0)
                    throw new feException("Energy selection on Amulet of Water cannot be found");

                $aow[$card_id][$real_energy]--;

                self::DbQuery("UPDATE resource_on_card SET roc_qt=roc_qt-1
                                WHERE roc_card='$card_id' AND roc_id='$real_energy' ");

                self::notifyAllPlayers('removeEnergyOnCard', '', array(
                    'player_id' => $player_id,
                    'card_id' => $card_id,
                    'energy_type' => $real_energy
                ));
            }
        }
    }

    function useBonus($bonusId) {
        self::checkAction('useBonus');
        $player_id = self::getActivePlayerId();

        // Use a special bonus

        // Check if player can still use some bonus
        $nb_used = self::getUniqueValueFromDB("SELECT player_nb_bonus_used FROM player WHERE player_id='$player_id' ");
        if ($nb_used >= 3)
            throw new feException(self::_("You cannot use more than 3 bonus by game"), true);

        switch ($bonusId) {
            case 1:
                $bonus_name = clienttranslate('Exchange 2 energies');
                break;
            case 2:
                $bonus_name = clienttranslate('Transmute (+1)');
                break;
            case 3:
                $bonus_name = clienttranslate('Increase summoning gauge');
                break;
            case 4:
                $bonus_name = clienttranslate('Choose power card');
                break;
        }

        // Okay, increase bonus usage
        self::DbQuery("UPDATE player SET player_nb_bonus_used=player_nb_bonus_used+1 WHERE player_id='$player_id' ");
        $nb_used++;
        self::notifyAllPlayers('bonusUsed', clienttranslate('${player_name} uses a bonus: ${bonus_name}'), array(
            'i18n' => array('bonus_name'),
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'bonus_id' => $bonusId,
            'bonus_name' => $bonus_name,
            'bonus_used' => $nb_used
        ));

        if ($bonusId == 1) {
            // Discard 2 energies to gain 2 energies
            self::setGameStateValue('currentEffect', 0);
            self::setGameStateValue('energyNbr', 2);

            if (self::countPlayerEnergies(self::getActivePlayerId(), true) < 2)
                throw new feException(self::_("You don't have enough energies"), true);

            $this->gamestate->nextState('bonusExchange');
        } else if ($bonusId == 2) {
            // Transmute with bonus
            $current = self::getGameStateValue('transmutationPossible');
            if ($current < 2)
                self::setGameStateValue('transmutationPossible', 2);  // Note: 2 = "with bonus +1"
            else
                self::setGameStateValue('transmutationPossible', $current + 1);
            $this->gamestate->nextState('useBonus');
        } else if ($bonusId == 3) {
            if (self::getUniqueValueFromDB("SELECT player_invocation FROM player WHERE player_id='$player_id' ") == 15)
                throw new feException(self::_("Your summoning gauge is already at its maximum (15)"), true);

            // +1 summoning gauge
            self::increaseSummoningGauge($player_id, clienttranslate('Bonus'), 1);
            $this->gamestate->nextState('useBonus');
        } else if ($bonusId == 4) {
            // Draw 2 cards and keep 1
            if (self::getGameStateValue('mustDrawPowerCard') == 1) {
                // 2 cards => choice pool
                $cards = $this->cards->pickCardsForLocation(2, 'deck', 'choice', $player_id);
                self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));

                self::setGameStateValue('currentEffect', 0);
                self::setGameStateValue('mustDrawPowerCard', 0);
                $this->gamestate->nextState('bonusDraw');
            } else
                throw new feException(self::_("You cannot use this bonus if there is no card symbol on your dice"), true);
        }
    }

    // Build a consolidated real cost, transforming all Amulet Of Water energies in real energies
    function mergeEnergyInRealCost($energies) {
        $result = array();

        foreach ($energies as $energy) {
            if ($energy >= 0 && $energy <= 4)
                $result[] = $energy;
            else {
                $real_energy = $energy % 10;
                $result[] = $real_energy;
            }
        }
        return $result;
    }

    // Return all energies that are not Amulet of Water energies
    function filterAmuletOfWaterEnergies($energies) {
        $result = array();
        foreach ($energies as $energy) {
            if ($energy >= 0 && $energy <= 4)
                $result[] = $energy;
        }
        return $result;
    }

    function isStaffWinterActive() {
        $season_id = self::getCurrentSeason();
        $player_id = self::getActivePlayerId();

        // If Winter + Staff of Winter, all energies worth 3
        if ($season_id == 1) {
            // Check Staff of Winter
            $staff = self::getAllCardsOfTypeInTableau(array(
                208
            ), $player_id);

            if (isset($staff[208])) {
                return true;
            }
        }

        return false;
    }

    // Return a nice HTML representation of energy tokens. If crystals is given,
    // also display crystals (or 0 crystals if $ress is empty).
    function htmlResources($ress, $crystals = null) {
        $display = "";
        // Energy tokens
        foreach ($ress as $ress_id => $ress_qt) {
            if ($ress_id > 0) {
                for ($i = 0; $i < $ress_qt; $i++) {
                    $display .= '<div class="sicon energy' . $ress_id . '"></div>';
                }
            }
        }

        // Crystals
        if ($crystals !== null && ($crystals > 0 || $display == "")) {
            $display .= '<div class="sicon energy0"></div>x' . $crystals;
        }
        return $display;
    }

    function transmute($energies, $bPotionOfLifeSpecial = false) {
        if (!$bPotionOfLifeSpecial) {
            self::checkAction('transmute');

            $transmutationPossible = self::getGameStateValue("transmutationPossible");
            if ($transmutationPossible == 0)
                throw new feException("Transmutation is not possible with this die");
        } else
            $transmutationPossible = self::getGameStateValue("transmutationPossible");

        $player_id = self::getActivePlayerId();

        // Apply the cost of amulet of waters
        self::applyAmuletOfWaterEnergyCost($energies);
        $originalEnergies = self::mergeEnergyInRealCost($energies);
        $energies = self::filterAmuletOfWaterEnergies($energies);

        $cost = array();
        foreach ($energies as $energy) {
            if (!isset($cost[$energy]))
                $cost[$energy] = 0;
            $cost[$energy]--;
        }

        // Check & Consume resources
        self::applyResourceDelta($player_id, $cost, true);

        // Compute gain in points
        $paid = array();
        foreach ($originalEnergies as $energy) {
            if (!isset($paid[$energy]))
                $paid[$energy] = 0;
            $paid[$energy]++;
        }

        // Transmute number of points for each energy
        $season_id = self::getCurrentSeason();
        if ($bPotionOfLifeSpecial) {
            // Potion of Life: all energies worth 4
            $transmutation = array(
                1 => 4,
                2 => 4,
                3 => 4,
                4 => 4
            );
        } else if (self::isStaffWinterActive()) {
            // If Winter + Staff of Winter, all energies worth 3
            $transmutation = array(
                1 => 3,
                2 => 3,
                3 => 3,
                4 => 3
            );
        } else {
            // Normal case depending on season
            $transmutation = $this->seasons[$season_id]['transmutation'];
        }

        $points = 0;
        $total_nbr = 0;
        foreach ($paid as $energy_id => $energy_nbr) {
            $energy_nbr = abs($energy_nbr);
            $points_by_energy = $transmutation[$energy_id];

            if ($transmutationPossible >= 2)   // note: 2 = "with +1 bonus"
                $points_by_energy += ($transmutationPossible - 1);

            $points += $points_by_energy * $energy_nbr;
            $total_nbr += $energy_nbr;
        }

        $points = self::checkMinion($points);

        $sql = "UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ";
        self::DbQuery($sql);

        $notifArgs = array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'energies' => self::htmlResources($paid),
            'points' => $points
        );
        $notifDescr = clienttranslate('${player_name} transmutes ${energies} for ${points} point(s)');
        if ($bPotionOfLifeSpecial) {
            $notifDescr = clienttranslate('${card_name}: ${player_name} transmutes ${energies} for ${points} point(s)');
            $notifArgs['i18n'] = array('card_name');
            $notifArgs['card_name'] = self::getCurrentEffectCardName();
        }
        self::notifyUpdateScores();
        self::notifyAllPlayers('winPoints', $notifDescr, $notifArgs);
        self::incStat($points, 'crystal_transmutations', $player_id);

        // Purse of Io: bonus for all transmuted energy
        $purseOfIo = self::getAllCardsOfTypeInTableau(array(
            8, // Purse of Io
        ), $player_id);

        if (isset($purseOfIo[8])) {
            $points = self::checkMinion($total_nbr * count($purseOfIo[8]), $player_id);
            $sql = "UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ";
            self::DbQuery($sql);

            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                'i18n' => array('card_name'),
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'points' => $points,
                'card_name' => $this->card_types[8]['name']
            ));
            self::notifyUpdateScores();
        }

        // Io's transmuter: mark it as active if used during this round
        // Io's transmuter is used if:
        //  _ the current die has crystal and no cristalization power
        //  _ this is not a Potion of Life Cristalization
        if (!$bPotionOfLifeSpecial) {
            $transmuter = self::getAllCardsOfTypeInTableau(array(
                109 // Io's transmuter (+2 crystals)
            ), $player_id);

            if (isset($transmuter[109])) {
                // There's at least one transmuter

                $playerDice = self::getObjectFromDB("SELECT dice_id, dice_face FROM dice
                                                      WHERE dice_season='$season_id' AND dice_player_id='$player_id' ");

                $dice = $this->dices[$season_id][$playerDice['dice_id']][$playerDice['dice_face']];

                if ($dice['trans'] == false && $dice['pts'] > 0) {
                    // In this case, we are really using Io's transmuter for this transmutation
                    //  => mark all of them as active
                    self::DbQuery("UPDATE card SET card_type_arg='1'
                        WHERE card_type IN ('109','118;109') AND card_location='tableau' AND card_location_arg='$player_id'");
                }
            }
        }
    }

    // If active player (or specified player) has Io's minion => reduce points to 0 and notify it
    function checkMinion($points, $player_id = null) {
        if ($player_id === null) {
            $player_id = self::getActivePlayerId();
            $player_name = self::getActivePlayerName();
        } else {
            $players = self::loadPlayersBasicInfos();
            $player_name = $players[$player_id]['player_name'];
        }

        if ($points > 0) {
            $minion = self::getAllCardsOfTypeInTableau(array(
                218
            ), $player_id);

            if (isset($minion[218])) {
                // There is a minion !

                self::notifyAllPlayers('simpleNote', clienttranslate('${card_name}: ${player_name} cannot score crystals'), array(
                    'i18n' => array('card_name'),
                    'card_name' => $this->card_types[218]['name'],
                    'player_name' => $player_name
                ));

                return 0;
            }
        }

        return $points;
    }

    function drawPowerCard() {
        self::checkAction('draw');

        if (self::getGameStateValue('mustDrawPowerCard') == 1) {
            self::doDrawPowerCard();
        }
    }

    function getPossibleCards() {
        $cards = [11]; //todo
        return $cards;
    }

    function doDrawPowerCard() {
        $player_id = self::getActivePlayerId();

        $card = $this->cards->pickCard('deck', $player_id);
        //$this->updatePlayer($player_id, PLAYER_FIELD_RESET_POSSIBLE, false);//todo merger
        self::notifyUpdateCardCount();

        self::incStat(1, 'cards_drawn', $player_id);

        self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${player_name} draws a power card'), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName()
        ));

        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card));

        self::setGameStateValue('lastCardDrawn', $card['id']);

        self::setGameStateValue('mustDrawPowerCard', 0);

        $this->gamestate->nextState('draw');
    }

    function doNotUse() {
        self::checkAction('doNotUse');
        $this->gamestate->nextState('doNotUse');
    }

    function discardEnergy($energies, $bDuetoEffect, $bDueToBonus = false) {
        if ($bDuetoEffect)
            self::checkAction('discardEnergyEffect');
        else if ($bDueToBonus)
            self::checkAction('discardEnergyBonus');
        else
            self::checkAction('discardEnergy');

        $player_id = self::getActivePlayerId();

        $originalEnergies = $energies;
        if ($bDuetoEffect || $bDueToBonus) {
            // Apply the cost of amulet of waters
            self::applyAmuletOfWaterEnergyCost($energies);
            $originalEnergies = self::mergeEnergyInRealCost($energies);
        }
        $energies = self::filterAmuletOfWaterEnergies($energies);

        if ($bDueToBonus) {
            if (count($originalEnergies) != 2)
                throw new feException(self::_("You must discard 2 energies"), true);
        }

        if (!$bDueToBonus && !$bDuetoEffect) {
            // Remaining energies must be exactly 7
            $energy_count = self::countPlayerEnergies($player_id);
            $reserve_size = self::getPlayerReserveSize($player_id);
            $trash = ($energy_count - $reserve_size);

            if (count($energies) != $trash)
                throw new feException(sprintf(self::_("You must discard exactly %s energy tokens"), $trash), true);
        }

        $cost = array();
        foreach ($energies as $energy) {
            if (!isset($cost[$energy]))
                $cost[$energy] = 0;
            $cost[$energy]--;
        }

        self::notifyAllPlayers("discardEnergy", clienttranslate('${player_name} discards ${nbr} energies'), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'nbr' => count($originalEnergies)
        ));

        // Check & Consume resources
        self::applyResourceDelta($player_id, $cost, true);

        if ($bDueToBonus) {
            $this->gamestate->nextState("discardEnergy");
        } else if ($bDuetoEffect) {
            $paid = array();
            foreach ($originalEnergies as $energy) {
                if (!isset($paid[$energy]))
                    $paid[$energy] = 0;
                $paid[$energy]--;
            }

            // Discard energy because of an effect => call this effect
            $card_name = self::getCurrentEffectCardName();
            $method_name = self::getCardEffectMethod($card_name, 'discardEnergy');
            $this->$method_name($paid);
        } else {
            // Standard card: discard energy because reserve is too small
            if (self::checkEnergy($player_id))
                $this->gamestate->nextState("discardEnergy");
            else
                $this->gamestate->nextState("continueDiscard");
        }
    }



    // Summon a new card
    function summon($card_id, $forceCost = null, $bFreeByEffect = false, $amuletEnergies = null) {
        if ($forceCost !== null)
            self::checkAction('summon');

        $player_id = self::getActivePlayerId();

        // Get cards details
        $card = $this->cards->getCard($card_id);

        $bFromOtus = false;

        if (!$card)
            throw new feException("Card not found");
        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
            if ($card['location'] == 'otus') {
                if (self::getGameStateValue('useOtus') != 0)
                    throw new feException(self::_("You cannot summon more than one card from Otus the Oracle each turn"), true);

                $bFromOtus = true;
            } else
                throw new feException("This card is not in your hand");
        }

        // Check summoning gauge  
        if (!self::checkSummoningGauge())
            throw new feException(self::_("You summoning gauge is not big enough to summon this new card"), true);

        $card_types = self::getCardTypes();
        $card_type = $card_types[$card['type']];
        $cost = array();

        if ($bFreeByEffect && $card['type'] == 106)
            throw new feException(self::_("Mesodae’s Lantern cannot be put into play via another Power card."), true);

        if (self::getGameStateValue('mustDrawPowerCard') == 1)
            throw new feException(self::_("You must draw your power card before doing this action"), true);

        $bOrbOfRagfield = false;

        if ($forceCost !== null) {
            // Cost has been forced, because it's a variable cost 
            foreach ($forceCost as $ress_id => $ress_qt) {
                $cost[$ress_id] = -$ress_qt;
            }
        } else {
            if ($card['type'] == 31) {
                // Elemental amulet => specific case, never go to "summonVariableCost" state
            } else {
                $total_ress = 0;
                foreach ($card_type['cost'] as $ress_id => $ress_qt) {
                    if ($ress_id != 0) {
                        $cost[$ress_id] = -$ress_qt;
                        $total_ress += $ress_qt;
                    }
                }

                // If some Orb of Ragfield => change completely cost
                $bOrbOfRagfield = count(self::getAllCardsOfTypeInTableau(array(302), $player_id));
                if ($bOrbOfRagfield && $card_type['points'] < 12) {
                    // Okay, applying Orb of Ragfield
                    $card_type['cost'] = array(0 => 5);
                    $cost = array();
                    $total_ress = 0;
                }


                // If some Hand of fortune is in play => reduce cost
                $handOfFortune = count(self::getAllCardsOfTypeInTableau(array(20), $player_id));
                $bStaffWinter = self::isStaffWinterActive();

                if ($total_ress > 1  && $handOfFortune > 0)    // Note: Hand of fortune is useless for card which cost 1 resource or less
                {
                    self::setGameStateValue('toSummon', $card_id);
                    // There is at least a hand of fortune => player should choose the cost to pay
                    $this->gamestate->nextState('summonVariableCost');
                    return;
                } else if ($total_ress > 0 && $bStaffWinter && isset($card_type['cost'][4])) {
                    self::setGameStateValue('toSummon', $card_id);
                    // There is at least a staff of Winter => player should choose the cost to pay
                    $this->gamestate->nextState('summonVariableCost');
                    return;
                }
            }
        }

        // Card is ready to be summoned. Check its costs...
        $point_cost = 0;
        foreach ($card_type['cost'] as $ress_id => $ress_qt) {
            if ($ress_id == 0)
                $point_cost += $ress_qt;
        }
        if ($card['type'] == 301) {
            // Speedwall the Escaped
            $point_cost = self::getUniqueValueFromDB("SELECT player_invocation FROM player WHERE player_id='$player_id'");

            if ($bOrbOfRagfield)
                $point_cost = 5;
        }

        if ($bFreeByEffect) {
            $cost_displayed = clienttranslate('free');
        } else {
            $cost_displayed = self::htmlResources($card_type['cost'], $point_cost);
        }

        if ($bFreeByEffect && $card['type'] == 31) {
            // Elemental amulet => specific case, can use 4 different energies for free
            self::setGameStateValue('elementalAmuletFree', 4);
        }

        if ($amuletEnergies !== null) {
            // Force the use of some energies from Amulet of Water
            // => reduce the cost of the card to summon
            self::applyAmuletOfWaterEnergyCost($amuletEnergies);

            foreach ($amuletEnergies as $energy) {
                if ($energy > 10) {
                    $energy = $energy % 10;
                    if (!isset($cost[$energy]))
                        throw new feException(self::_("No need to use following resource: ") . self::_($this->energies[$energy]['name']), true);
                    if ($cost[$energy] == 0)
                        throw new feException(self::_("No need to use following resource: ") . self::_($this->energies[$energy]['name']), true);
                    $cost[$energy]++;
                }
            }
        }

        self::applyResourceDelta($player_id, $cost);

        if ($bFromOtus)
            self::setGameStateValue('useOtus', 1);


        if (!$bFreeByEffect) {
            // Cost in points
            $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
            if ($point_cost > 0) {
                if ($player_score < $point_cost)
                    throw new feException(self::_("You don't have enough crystals to summon this card"), true);

                self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score-$point_cost ) WHERE player_id='$player_id'");
                $player_score -= $point_cost;
                self::notifyAllPlayers('winPoints', '', array('player_id' => $player_id, 'points' => -$point_cost));
                self::notifyUpdateScores();
            }
        }

        $players = self::loadPlayersBasicInfos();

        if (!$bFreeByEffect) {
            // Arcano leech
            $arcanos = self::getAllCardsOfTypeInTableau(array(33));
            if (isset($arcanos[33])) {
                $arcanos = $arcanos[33];
                foreach ($arcanos as $arcanowner_id => $arcano_cards) {
                    if ($arcanowner_id != $player_id) {
                        foreach ($arcano_cards as $arcano_card) {
                            // player must give one crystal to arcano owner
                            if ($player_score == 0)
                                throw new feException(self::_("You don't have enough crystals to give to") . ' ' . $this->card_types['33']['name'], true);

                            self::DbQuery("UPDATE player SET player_score=GREATEST(0, player_score-1) WHERE player_id='$player_id'");

                            $player_score--;
                            self::notifyAllPlayers('winPoints', '', array('player_id' => $player_id, 'points' => -1));

                            $points = self::checkMinion(1, $arcanowner_id);
                            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$arcanowner_id'");
                            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gives 1 crystal to ${player_name2}'), array(
                                'i18n' => array('card_name'),
                                'player_id' => $arcanowner_id,
                                'points' => $points,
                                'player_name' => self::getCurrentPlayerName(),
                                'player_name2' => $players[$arcanowner_id]['player_name'],
                                'card_name' => $this->card_types['33']['name']
                            ));
                            self::notifyUpdateScores();
                        }
                    }
                }
            }
        }

        if (!$bFreeByEffect)   // Note: cards summoned for free does not trigger onSummon effects
        {
            // Trigger effects of cards on "onSummon" event:
            $onSummonCardsLocalPlayer = array(
                6,  // Staff of spring
                30, // Yjang’s Forgotten Vase
            );
            self::triggerEffectsOnEvent('onSummon', $onSummonCardsLocalPlayer, $player_id);
        }

        // Trigger effects of Urmian Psychic Cage
        $onSummonCardsAllPlayer = array(
            215,  // Urmian Psychic Cage
        );
        self::triggerEffectsOnEvent('onSummon', $onSummonCardsAllPlayer, null, true);

        if (!$bFreeByEffect) {
            $onSummonCardsOpponents = array(
                202,  // Magma core, only if standard summon
            );
            self::triggerEffectsOnEvent('onSummon', $onSummonCardsOpponents, null, true, $player_id);
        }


        // Move card to player's tableau
        $this->cards->moveCard($card_id, 'tableau', $player_id);
        self::notifyUpdateCardCount();

        self::incStat(1, 'cards_summoned', $player_id);

        // Notify all players (with card cost)
        self::notifyAllPlayers('summon', clienttranslate('${player_name} summons a ${card_name} for ${cost}'), array(
            'i18n' => array('card_name', 'cost'),
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_type['name'],
            'cost' => $cost_displayed,
            'card' => $card,
            'fromOtus' => $bFromOtus
        ));

        // Summon card effect
        self::insertEffect($card_id, 'play');

        self::applyCardsEffect('playerTurn', $player_id);
    }



    function active($card_id, $bActiveBeforeTurn = false, $energy = array()) {
        if (!$bActiveBeforeTurn)
            self::checkAction('active');

        $player_id = self::getActivePlayerId();

        // Get cards details
        $card = $this->checkCardIsInTableau($card_id, $player_id);

        if ($card['type_arg'] == 1)
            throw new feException(self::_("This card has been activated already during this turn"), true);

        $card_type = $this->card_types[self::ct($card['type'])];

        // Note: if there is no effect, there can't be any activation
        $method_name = self::getCardEffectMethod($card_type['name'], 'active');
        if (!method_exists($this, $method_name))
            throw new feException(self::_("This card has no activation effect"), true);

        if (self::getGameStateValue('mustDrawPowerCard') == 1)
            throw new feException(self::_("You must draw your power card before doing this action"), true);


        // Mark this card as "actived"
        self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id' ");

        // Notify all players (with card cost)
        self::notifyAllPlayers('active', clienttranslate('${player_name} actives ${card_name}'), array(
            'i18n' => array('card_name'),
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_type['name'],
            'card' => $card
        ));

        self::incStat(1, 'cards_activated', $player_id);

        // Thieving Fairies (cf Arcano Leech) + Heart of Argos
        $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
        $players = self::loadPlayersBasicInfos();
        $fairies_and_heart = self::getAllCardsOfTypeInTableau(array(41, 101), null, true);

        if (isset($fairies_and_heart[41])) {
            $fairies = $fairies_and_heart[41];
            foreach ($fairies as $fairiesowner_id => $fairies_cards) {
                if ($fairiesowner_id != $player_id) {
                    foreach ($fairies_cards as $fairies_card) {
                        // player must give one crystal to fairies owner
                        if ($player_score == 0) {
                            $points = self::checkMinion(1, $fairiesowner_id);
                            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$fairiesowner_id'");
                            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gets ${points} crystals'), array(
                                'i18n' => array('card_name'),
                                'player_id' => $fairiesowner_id,
                                'points' => $points,
                                'player_name' => $players[$fairiesowner_id]['player_name'],
                                'card_name' => $this->card_types['41']['name']
                            ));
                            self::notifyUpdateScores();
                        } else {
                            self::DbQuery("UPDATE player SET player_score=GREATEST(0,player_score-1) WHERE player_id='$player_id'");
                            $player_score--;
                            self::notifyAllPlayers('winPoints', '', array('player_id' => $player_id, 'points' => -1));

                            $points = self::checkMinion(2, $fairiesowner_id);
                            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$fairiesowner_id'");
                            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gives 1 crystal to ${player_name2} and gets 1 more crystal'), array(
                                'i18n' => array('card_name'),
                                'player_id' => $fairiesowner_id,
                                'points' => $points,
                                'player_name' => self::getCurrentPlayerName(),
                                'player_name2' => $players[$fairiesowner_id]['player_name'],
                                'card_name' => $this->card_types['41']['name']
                            ));
                            self::notifyUpdateScores();
                        }
                    }
                }
            }
        }
        if (isset($fairies_and_heart[101])) {
            // Heart of Argos
            $hearts = $fairies_and_heart[101];
            foreach ($hearts as $heartsowner_id => $hearts_cards) {
                if ($heartsowner_id == $player_id) {
                    foreach ($hearts_cards as $hearts_card) {
                        // First activation of a card => place a earth token on this card
                        $energy_on_heart = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$hearts_card' ", true);

                        if (isset($energy_on_amulet[4]))
                            self::DbQuery("UPDATE resource_on_card SET roc_qt=roc_qt+1 WHERE roc_card='$hearts_card' AND roc_id='4' ");
                        else
                            self::DbQuery("INSERT INTO resource_on_card (roc_id,roc_card,roc_qt,roc_player) VALUES ('4','$hearts_card','1','$player_id') ");

                        // Mark Heart of Argos as "activated" in order the effect cannot be triggerd a second time this turn
                        self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$hearts_card' ");

                        self::notifyAllPlayers('placeEnergyOnCard', clienttranslate('${player_name} places a ${energy} on ${card_name}'), array(
                            'i18n' => array('card_name'),
                            'player_id' => $player_id,
                            'player_name' => self::getCurrentPlayerName(),
                            'card_name' => $this->card_types['101']['name'],
                            'energy' => '<div class="sicon energy4"></div>',
                            'energy_type' => 4,
                            'card_id' => $hearts_card
                        ));
                    }
                }
            }
        }

        // Summon card effect
        self::insertEffect($card_id, 'active');
        self::applyCardsEffect($bActiveBeforeTurn ? 'beforeTurn' : 'playerTurn', $player_id);
    }

    // Choose an energy to gain
    function gainEnergy($energy_id) {
        self::checkAction('gainEnergy');

        if ($energy_id >= 1 && $energy_id <= 4) {
            $player_id = self::getActivePlayerId();
            if (self::getGameStateValue('currentEffect') != 0) {
                $effect_card = self::getCurrentEffectCard();
                $card_id = $effect_card['card_id'];
                $notifArgs = self::getStandardArgs();
                $notifArgs['card_id'] = $card_id;
            } else {
                $effect_card = null;
                $notifArgs = array(
                    'i18n' => array('card_name'),
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_name' => clienttranslate('Bonus')
                );
            }

            $notifArgs['energy'] = '<div class="sicon energy' . $energy_id . '"></div>';
            $notifArgs['energy_type'] = $energy_id;

            if ($effect_card !== null && self::ct($effect_card['card_type']) == 4) {
                // Specific: amulet of water: energy gained is placed on the card
                $energy_on_amulet = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);

                if (isset($energy_on_amulet[$energy_id]))
                    self::DbQuery("UPDATE resource_on_card SET roc_qt=roc_qt+1 WHERE roc_card='$card_id' AND roc_id='$energy_id' ");
                else
                    self::DbQuery("INSERT INTO resource_on_card (roc_id,roc_card,roc_qt,roc_player) VALUES ('$energy_id','$card_id','1','$player_id') ");

                self::notifyAllPlayers('placeEnergyOnCard', clienttranslate('${player_name} places a ${energy} on ${card_name}'), $notifArgs);
            } else {
                // This energy => player stock
                self::applyResourceDelta($player_id, array($energy_id => 1));
                self::notifyAllPlayers('gainEnergy', clienttranslate('${card_name}: ${player_name} gets a ${energy}'), $notifArgs);
            }

            $to_gain = self::incGameStateValue('energyNbr', -1);

            if ($to_gain <= 0) {
                if ($effect_card !== null && self::ct($effect_card['card_type']) == 115)  // Amulet of time: we have to do something afterwards
                    $this->gamestate->nextState('endAmuletOfTime');
                else
                    $this->gamestate->nextState('end');
            } else
                $this->gamestate->nextState('next');
        } else if ($energy_id == 0) {
            if (self::checkAction("statueOfEolisChoice", false)) {
                // Statue of Eolis: +2 crystals + look at the top card

                $player_id = self::getActivePlayerId();
                $players = self::loadPlayersBasicInfos();
                $gain = self::checkMinion(2, $player_id);
                self::DbQuery("UPDATE player SET player_score=GREATEST( 0,player_score+$gain ) WHERE player_id='$player_id'");
                self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                    'i18n' => array('card_name'),
                    'player_id' => $player_id,
                    'points' => $gain,
                    'points_disp' => abs($gain),
                    'player_name' => $players[$player_id]['player_name'],
                    'card_name' => $this->card_types['107']['name']
                ));
                self::notifyUpdateScores();

                // See first card on the drawpile
                $card = $this->cards->pickCardForLocation('deck', 'choice', $player_id);
                self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => array($card)));


                $this->gamestate->nextState('topcard');
            } else if (self::checkAction('chronoRingChoice')) {
                // Chrono ring: +4 crystals
                $player_id = self::getActivePlayerId();
                $players = self::loadPlayersBasicInfos();
                $gain = self::checkMinion(4, $player_id);
                self::DbQuery("UPDATE player SET player_score=GREATEST( 0,player_score+$gain ) WHERE player_id='$player_id'");
                self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                    'i18n' => array('card_name'),
                    'player_id' => $player_id,
                    'points' => $gain,
                    'points_disp' => abs($gain),
                    'player_name' => $players[$player_id]['player_name'],
                    'card_name' => $this->card_types['212']['name']
                ));
                self::notifyUpdateScores();

                $this->gamestate->nextState('end');
            }
        } else
            throw new feException("Invalid energy id");
    }

    function sacrifice($card_id) {
        self::checkAction('sacrifice');

        $player_id = self::getActivePlayerId();

        $card_name = self::getCurrentEffectCardName();

        // Get cards details
        $card =  $this->checkCardIsInTableau($card_id, $player_id);

        $card_type = $this->card_types[self::ot($card['type'])];

        // Okay, let's sacrifice this card    
        $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));

        $bForceSacrifice = false;
        if ($card_name == "Arus’s Mimicry") {
            $bForceSacrifice = true;    // Force sacrifice even if not enough to pay Titan, to avoid blocking situations
        }

        self::cleanTableauCard($card_id, $player_id, true, $bForceSacrifice);
        self::notifyUpdateCardCount();

        self::notifyAllPlayers('discardFromTableau', clienttranslate('${card_name}: ${player_name} sacrifices ${sacrified}'), array(
            'i18n' => array('card_name', 'sacrified'),
            'card_name' => $card_name,
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'sacrified' => $card_type['name']
        ));

        // If sacrified Shield of Zira: may gains some crystals
        $bSacrificeZira = false;
        if (self::ct($card['type']) == 113) {
            // Points gains = 10 x MIN( number of shield of zira in player's tableau+1, number of card in player's tableau )
            $zira = self::getAllCardsOfTypeInTableau(array(
                113 // Zira's shield
            ), $player_id);

            $ziraCount = isset($zira[113]) ? count($zira[113]) : 0;

            $cards_sacrificable = $this->cards->countCardsInLocation('tableau', $player_id);

            // Exception: if current effect is Necrotic kriss, we must take into account only familiar (in tableau+hand)
            if ($card_name == 'Necrotic Kriss') {
                $cards_sacrificable = self::countCardOfCategoryInTableau($player_id, 'f');
                $cards_sacrificable += self::countCardOfCategoryInTableau($player_id, 'f', true);
            }

            $zira_scores = min($ziraCount + 1, $cards_sacrificable);

            if ($zira_scores > 0) {
                for ($i = 0; $i < $zira_scores; $i++) {
                    $points = self::checkMinion(10, $player_id);
                    self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score+$points ) WHERE player_id='$player_id' ");

                    self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                        'i18n' => array('card_name'),
                        'player_id' => $player_id,
                        'player_name' => self::getActivePlayerName(),
                        'points' => $points,
                        'card_name' => $this->card_types[113]['name']
                    ));
                    self::notifyUpdateScores();
                }
            }


            $bSacrificeZira = true;
        }


        // Sacrifice card effect
        $method_name = self::getCardEffectMethod($card_name, 'sacrifice');
        $this->$method_name($card_id, self::ot($card['type']), $bSacrificeZira);   // Note: we transmit original type here because the only usage is to determine if card is a familiar/magical item (see Necrotic Kriss)
    }

    function cancel() {
        // Cancel current effect
        self::checkAction('cancel');
        $this->gamestate->nextState('cancel');
    }

    function chooseTableauCard($card_id) {
        self::checkAction('chooseTableauCard');

        $player_id = self::getActivePlayerId();

        // Get cards details
        $card =  $this->checkCardIsInTableau($card_id, $player_id);

        $card_type = $this->card_types[self::ot($card['type'])];

        $card_name = self::getCurrentEffectCardName();

        $method_name = self::getCardEffectMethod($card_name, 'chooseTableauCard');
        $this->$method_name($card_id, self::ot($card['type']));
    }

    function takeBack($card_id) {
        self::checkAction('takeBack');

        $player_id = self::getActivePlayerId();

        // Get cards details
        $card =  $this->checkCardIsInTableau($card_id, $player_id);

        $card_type = $this->card_types[self::ot($card['type'])];

        // Okay, let's move back this card to player's hand
        $this->cards->moveCard($card_id, 'hand', $player_id);
        self::cleanTableauCard($card_id, $player_id, false);
        self::notifyUpdateCardCount();

        $card_name = self::getCurrentEffectCardName();

        self::notifyAllPlayers('discardFromTableau', clienttranslate('${card_name}: ${player_name} takes back ${sacrified}'), array(
            'i18n' => array('card_name', 'sacrified'),
            'card_name' => $card_name,
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'sacrified' => $card_type['name']
        ));

        $card = $this->cards->getCard($card_id); // To get the clean card name
        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card));

        if ($card['type'] == 222) {
            // This is a replica => must remove it from game now
            $this->cards->moveCard($card_id, 'removed');
            self::notifyAllPlayers('discard', clienttranslate('The replica cannot be taken back and is removed from game'), array(
                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }

        $method_name = self::getCardEffectMethod($card_name, 'takeBack');
        $this->$method_name($card_id, self::ot($card['type']));
    }

    function checkCardIsInHand($card_id, $player_id) {
        return $this->checkCardIsInLocation($card_id, 'hand', $player_id);
    }

    function checkCardIsInTableau($card_id, $player_id) {
        return $this->checkCardIsInLocation($card_id, 'tableau', $player_id);
    }

    function checkCardIsInLocation($card_id, $location, $player_id) {
        $card = $this->cards->getCard($card_id);

        if (!$card)
            throw new feException("Card not found");
        if ($card['location'] != $location || $card['location_arg'] != $player_id)
            throw new feException("This card is not in your " . $location);
        return $card;
    }

    function discard($card_id) {
        self::checkAction('discard');

        $player_id = self::getActivePlayerId();

        // Get cards details
        $card = $this->checkCardIsInHand($card_id, $player_id);
        $card_type = $this->card_types[self::ot($card['type'])];

        // Okay, card can be discarded
        $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
        self::notifyUpdateCardCount();

        $card_name = self::getCurrentEffectCardName();

        self::notifyAllPlayers('discard', clienttranslate('${card_name}: ${player_name} discards ${discarded}'), array(
            'i18n' => array('card_name', 'sacrified'),
            'card_name' => $card_name,
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'discarded' => $card_type['name']
        ));

        // Discard card effect
        $method_name = self::getCardEffectMethod($card_name, 'discard');
        $this->$method_name($card_id, self::ct($card['type']));
    }

    function chooseToken($tokenId) {
        self::checkAction('chooseToken');
        $player_id = self::getCurrentPlayerId();
        $token = $this->checkTokenBelongsToPlayer($tokenId, $player_id);

        $this->tokensDeck->moveAllCardsInLocation('hand', 'discard', $player_id); //other tokens are discarded
        $this->tokensDeck->moveCard($tokenId, 'hand',  $player_id);
        $this->gamestate->setPlayerNonMultiactive($player_id, 'startYear');

        self::notifyAllPlayers('tokenChosen', '', array(
            'token_id' => $tokenId,
            'player_id' => $player_id,
        ));
    }

    function playToken() {
        //no self::checkAction('playToken'); here because many tokens have specific moments to be played
        $player_id = self::getCurrentPlayerId();
        $tokens = $this->tokensDeck->getCardsInLocation('hand', $player_id);
        if (count($tokens) != 1) {
            throw new BgaUserException("No token can be played at this point");
        }
        $token = array_pop($tokens);
        $notifArgs = $this->getStandardArgs(false);

        switch ($token["type"]) {
            case 14:
                //move back on bonus track
                $nb_used = self::getUniqueValueFromDB("SELECT player_nb_bonus_used FROM player WHERE player_id='$player_id' ");
                if ($nb_used > 0) {
                    $this->notifyAbilityTokenInUse();
                    $this->decreaseBonusUsage($player_id, $nb_used, $notifArgs);
                } else {
                    throw new BgaUserException("You can not use this token now since you've never used a bonus action");
                }
                break;
            case 15:
                //discard 5 fire energy to draw a card
                $cost = [3 => 5]; //3=fire
                $delta = [3 => -5];
                $stock = self::getResourceStock($player_id);
                if (!self::checkCostAgainstStock($cost, $stock)) {
                    throw new BgaUserException("You don't have 5 fire energies in your reserve");
                }
                $this->notifyAbilityTokenInUse();
                $this->applyResourceDelta($player_id, $delta, false);
                $this->doDrawPowerCard();
                break;
            

            default:
                # code...
                break;
        }

        $this->tokensDeck->moveCard($token["id"], 'used',  $player_id);
        self::notifyAllPlayers('tokenUsed', '', array(
            'token_id' => $token["id"],
            'player_id' => $player_id,
        ));
    }

    function notifyAbilityTokenInUse() {
        self::notifyAllPlayers('msg', clienttranslate('${player_name} uses his ability token'), array(
            'player_name' => self::getCurrentPlayerName(),
        ));
    }

    function checkTokenBelongsToPlayer($tokenId, $player_id) {
        $card = $this->tokensDeck->getCard($tokenId);

        if (!$card)
            throw new feException("Token not found");
        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id)
            throw new feException("This token is not yours");
        return $card;
    }

    function chooseCardHand($card_id) {
        self::checkAction('chooseCardHand');

        $player_id = self::getActivePlayerId();

        // Get cards details
        $card = $this->cards->getCard($card_id);

        $bFromOtus = false;
        if (!$card)
            throw new feException("Card not found");
        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
            if ($card['location'] == 'otus' && self::checkAction('chooseCardHandOtus', false)) {
                if (self::getGameStateValue('useOtus') != 0)
                    throw new feException(self::_("You cannot summon more than one card from Otus the Oracle each turn"), true);

                $bFromOtus = true;
            } else
                throw new feException("This card is not in your hand");
        }

        $card_type = $this->card_types[self::ot($card['type'])];

        $card_name = self::getCurrentEffectCardName();
        // Bug #13: when Raven copy Potion of Dream, as "cleanTableauCard" is applied to Raven, we lost the initial type
        // ===> as chooseCardHand is limited to Potion of dream, we force Potion of Dream here
        $card_name = "Potion of Dreams";

        // Effect
        $method_name = self::getCardEffectMethod($card_name, 'chooseCardHand');

        $this->$method_name($card_id, self::ct($card['type']));
    }
    function chooseCardHandcrafty($card_id) {
        self::checkAction('chooseCardHandcrafty');

        $player_id = self::getActivePlayerId();

        // Get cards details
        $card = $this->cards->getCard($card_id);

        if (!$card)
            throw new feException("Card not found");
        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id)
            throw new feException("This card is not in your hand");

        $card_type = $this->card_types[self::ot($card['type'])];

        $card_name = self::getCurrentEffectCardName();

        // Effect
        $method_name = self::getCardEffectMethod($card_name, 'chooseCardHand');

        $this->$method_name($card_id, self::ct($card['type']));
    }

    function chooseOpponentCard($card_id, $amuletEnergies) {
        self::checkAction('chooseOpponentCard');

        $player_id = self::getActivePlayerId();

        // Get cards details
        $card = $this->cards->getCard($card_id);

        if (!$card)
            throw new feException("Card not found");
        if ($card['location'] != 'tableau' || $card['location_arg'] == $player_id)
            throw new feException("You must choose an opponent card");


        $card_type = $this->card_types[self::ot($card['type'])];

        $card_name = self::getCurrentEffectCardName();

        // Effect
        $method_name = self::getCardEffectMethod($card_name, 'chooseOpponentCard');
        $this->$method_name($card_id, self::ct($card['type']), $amuletEnergies);
    }


    function chooseXenergy($energies) {
        self::checkAction('chooseXenergy');

        $player_id = self::getActivePlayerId();

        // Apply the cost of amulet of waters
        self::applyAmuletOfWaterEnergyCost($energies);
        $originalEnergies = self::mergeEnergyInRealCost($energies);
        $energies = self::filterAmuletOfWaterEnergies($energies);

        if (!self::isStaffWinterActive()) {
            // Check if energies are the same
            // Note: with staff of winter, energies are always the same
            $identic_resource = null;
            foreach ($originalEnergies as $originalEnergy) {
                if ($identic_resource == null)
                    $identic_resource = $originalEnergy;
                else {
                    if ($identic_resource != $originalEnergy)
                        throw new feException(self::_("You must choose identical energies"), true);
                }
            }
        }

        $cost = array();
        foreach ($energies as $energy) {
            if (!isset($cost[$energy]))
                $cost[$energy] = 0;
            $cost[$energy]--;
        }


        // Check & Consume resources
        self::applyResourceDelta($player_id, $cost, true);


        self::notifyAllPlayers("discardEnergy", clienttranslate('${player_name} discards ${nbr} energies'), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'nbr' => count($energies)
        ));


        // Discard energy because of an effect => call this effect
        $card_name = self::getCurrentEffectCardName();
        $method_name = self::getCardEffectMethod($card_name, 'chooseXenergy');

        $this->$method_name(count($originalEnergies));
    }

    function chooseEnergyType($energy_id, $amuletEnergies) {
        self::checkAction('chooseEnergyType');

        if ($energy_id >= 1 && $energy_id <= 4) {
            $card_name = self::getCurrentEffectCardName();
            $method_name = self::getCardEffectMethod($card_name, 'chooseEnergyType');
            $this->$method_name($energy_id, $amuletEnergies);
        } else
            throw new feException("Invalid energy id");
    }

    function cardEffectEnd() {
        self::checkAction('cardEffectEnd');
        $card_name = self::getCurrentEffectCardName();
        $method_name = self::getCardEffectMethod($card_name, 'cardEffectEnd');
        $this->$method_name();
    }

    function dualChoice($choice_id) {
        self::checkAction('dualChoice');

        $card_name = self::getCurrentEffectCardName();
        $method_name = self::getCardEffectMethod($card_name, 'dualChoice');
        $this->$method_name($choice_id);
    }

    function useZira($choice_id) {
        $player_id = self::getActivePlayerId();
        $card_id = self::getCurrentEffectCardId();
        $card_name = self::getCurrentEffectCardName();
        $method_name = self::getCardEffectMethod($card_name, 'useZira');
        $notifArgs = self::getStandardArgs();

        $bUseZira = ($choice_id == 1);
        $zira_card_id = null;

        if ($bUseZira) {
            // Sacrifice one Zira's shield and get 10 crystals
            // ... sacrifice

            self::checkAction('useZira');

            $zira = self::getAllCardsOfTypeInTableau(array(
                113 // Zira's shield
            ), $player_id);

            if (isset($zira[113])) {
                // There is at least one Zira's shield in play => gains 10 crystals by shield of zira
                foreach ($zira[113] as $card_id) {
                    $points = self::checkMinion(10, $player_id);
                    self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score+$points ) WHERE player_id='$player_id' ");

                    self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                        'i18n' => array('card_name'),
                        'player_id' => $player_id,
                        'player_name' => self::getActivePlayerName(),
                        'points' => $points,
                        'card_name' => $this->card_types[113]['name']
                    ));

                    self::notifyUpdateScores();
                }

                // Sacrifice this last one
                $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
                self::cleanTableauCard($card_id, $player_id);
                $zira_card_id = $card_id;

                self::notifyAllPlayers('discardFromTableau', '', array(
                    'card_id' => $card_id,
                    'player_id' => $player_id
                ));
            } else
                throw new feException("You do not have any Shield of Zira");

            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);

            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }

        $next = $this->$method_name($card_id, $card_name, $notifArgs, $bUseZira, $zira_card_id);

        if ($next == 'do_not_nextState') {
            // ... do nothing
        } else if ($next !== null) {
            $this->gamestate->nextState($next);
        } else
            $this->gamestate->nextState('chooseZira');
    }
    function keepOrDiscard($choice_id) {
        self::checkAction('keepOrDiscard');

        $player_id = self::getActivePlayerId();

        if ($choice_id == 1) {
            // Keep, nothing to do
            self::notifyAllPlayers('keepPowerCard', clienttranslate('${player_name} keeps his power card'), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName()
            ));

            self::mayUseEscaped();
        } else {
            // Discard
            $card_id = self::getGameStateValue('lastCardDrawn');

            // Get cards details
            $card = $this->cards->getCard($card_id);

            if (!$card)
                throw new feException("Card not found");
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id)
                throw new feException("This card is not in your hand");

            $card_type = $this->card_types[self::ot($card['type'])];

            // Okay, card can be discarded
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::notifyUpdateCardCount();

            self::notifyAllPlayers('discard', clienttranslate('${player_name} discards his power card'), array(
                'card_id' => $card_id,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName()
            ));
        }

        $state = $this->gamestate->state();

        $this->gamestate->nextState('keepOrDiscard');

        if ($state['name'] != 'keepOrDiscard') {
            // An effect is in progress (probably servant of ragfield), so applyCardsEffect will be called anyway
        } else {
            self::applyCardsEffect('playerTurn', $player_id);
        }
    }


    // Speedwall the Escaped may be used to get the card in hand
    function mayUseEscaped($player_id = null) {
        $card_id = self::getGameStateValue('lastCardDrawn');

        if ($player_id == null)
            $player_id = self::getActivePlayerId();

        $players = self::loadPlayersBasicInfos();
        $escaped = self::getAllCardsOfTypeInTableau(array(301), null, true);

        if (isset($escaped[301])) {
            $escaped = $escaped[301];
            foreach ($escaped as $escapedowner_id => $escaped_cards) {
                if ($escapedowner_id != $player_id) {
                    foreach ($escaped_cards as $escaped_card) {
                        self::insertEffect($escaped_card, 'onDrawOne');
                    }
                }
            }
        }
    }


    function chooseCost($cost_id, $amuletEnergies) {
        self::checkAction('chooseCost');

        $player_id = self::getActivePlayerId();

        $argSummon = self::argSummonVariableCost();

        if (!isset($argSummon['costs'][$cost_id]))
            throw new feException("You cannot pay this card with this cost");

        $cost = $argSummon['costs'][$cost_id];

        self::setGameStateValue('afterEffectState', 1);
        self::setGameStateValue('afterEffectPlayer', $player_id);
        $this->gamestate->nextState('chooseCost');

        self::summon(self::getGameStateValue('toSummon'), $cost, false, $amuletEnergies);
    }

    function chooseCostCancel() {
        self::checkAction('chooseCost');
        $this->gamestate->nextState('cancelChooseCost');
    }

    function chooseCard($card_id) {
        self::checkAction('chooseCard');

        // Check this card is on the "choice" pool of this player
        $player_id = self::getActivePlayerId();

        // Get cards details
        $card = $this->cards->getCard($card_id);

        if (!$card)
            throw new feException("Card not found");
        if ($card['location'] != 'choice' || $card['location_arg'] != $player_id)
            throw new feException("This card is not available");

        if (self::getGameStateValue('currentEffect') != 0) {
            $card_name = self::getCurrentEffectCardName();
            $method_name = self::getCardEffectMethod($card_name, 'chooseCard');
            $this->$method_name($card_id, $card);
        } else {
            // This "choose card" is linked to bonus n°4 => add this card to player hand
            $this->cards->moveCard($card_id, 'hand', $player_id);
            self::notifyUpdateCardCount();
            self::incStat(1, 'cards_drawn', $player_id);

            self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} choosed a power card'), array(
                'i18n' => array('card_name'),
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card_name' => clienttranslate('Bonus')
            ));
            self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

            // Discard all choice
            $this->cards->moveAllCardsInLocation('choice', 'discard', $player_id, self::incGameStateValue('discardPos', 1));

            $this->gamestate->nextState('chooseCard');
        }
    }

    function moveSeason($monthChoice) {
        self::checkAction('moveSeason');

        $current_year = self::getGameStateValue('year');
        $current_month = self::getGameStateValue('month');
        $currentSeason = self::getCurrentSeason();

        $possible_months = array();
        for ($i = 1; $i <= 3; $i++)  // Future
        {
            $month = $current_month + $i;
            $year = $current_year;
            if ($month > 12) {
                $month -= 12;
                $year++;
            }
            $possible_months[$month] = $year;

            if ($i == 3)
                $plus_three = $month;
        }
        for ($i = 1; $i <= 3; $i++)  // Past
        {
            $month = $current_month - $i;
            $year = $current_year;
            if ($month < 1) {
                $month += 12;
                $year--;
            }
            $possible_months[$month] = $year;
        }

        if (!isset($possible_months[$monthChoice]))
            throw new feException(self::_("You must move the Season token forward or back 1 to 3 spaces"), true);

        $newYear = $possible_months[$monthChoice];
        self::setGameStateValue('month', $monthChoice);
        self::setGameStateValue('year', $newYear);

        if ($newYear != $current_year) {
            if ($newYear == 2 || $newYear == 3) {
                self::giveLibaryCardsToPlayers($newYear);
            }
        }

        $notifArgs = self::getStandardArgs();
        $notifArgs['month'] = $monthChoice;
        $notifArgs['year'] = $possible_months[$monthChoice];
        self::notifyAllPlayers(
            'timeProgression',
            clienttranslate('${card_name}: ${player_name} moves the season token'),
            $notifArgs
        );

        $newSeason = self::getCurrentSeason();

        if ($currentSeason != $newSeason) {
            self::triggerEffectsOnEvent('onSeasonChange', array(
                11, // Figrim the Avaricious (each opponent gives you a crystal)
                27,  // Hourglass of time => gain 1 energy
                107 // Statue of Eolis: Whenever the season changes, either collect 1 energy token OR receive 2 crystals and look at the top card of the draw pile.
            ));
        }

        if ($plus_three == $monthChoice) {
            self::triggerEffectsOnEvent('onSeasonChange', array(
                212 // Chrono-ring
            ));
        }

        $this->gamestate->nextState('moveSeason');
    }

    function choosePlayer($player_id) {
        self::checkAction('choosePlayer');
        $players = self::loadPlayersBasicInfos();

        if (!isset($players[$player_id]))
            throw new feException("Wrong player");

        $card_name = self::getCurrentEffectCardName();
        $method_name = self::getCardEffectMethod($card_name, 'choosePlayer');
        $this->$method_name($player_id);
    }

    // Note: die of malice management
    function reroll($bReroll) {
        self::checkAction('reroll');

        $player_id = self::getActivePlayerId();
        $maliceDice = self::getAllCardsOfTypeInTableau(array(15), $player_id, true);
        if (isset($maliceDice[15])) {
            $card_id = reset($maliceDice[15]);

            if (!$bReroll) {
                // Mark die of malice as "active" anyway
                self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id' ");

                $this->gamestate->nextState('startTurn');
            } else {
                // For coherence, we must "active" the Die Of Malice as if it was any activation
                self::active($card_id, true);
            }
        } else
            throw new feException("Can't found die of malice");
    }

    function steadfast($action_id) {
        // Steadfast die action choice
        self::checkAction('steadFast');

        $player_id = self::getActivePlayerId();
        $maliceDice = self::getAllCardsOfTypeInTableau(array(114), $player_id, true);
        if (isset($maliceDice[114])) {
            $card_id = reset($maliceDice[114]);
            if ($action_id == 0) {
                // Mark card as "active" anyway
                self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id' ");

                $this->gamestate->nextState('startTurn');
            } else {
                self::setGameStateValue('steadfast_die_mode', $action_id);
                $this->gamestate->nextState('startTurn');
            }
        } else
            throw new feException("Can't found steadfast die");
    }

    function orbChoice($bReplace, $energy) {
        self::checkAction('orbChoice');
        $card_name = self::getCurrentEffectCardName();
        $method_name = self::getCardEffectMethod($card_name, 'orbChoice');
        $this->$method_name($bReplace, $energy);
    }

    function amuletOfTime($cards) {
        self::checkAction('amuletOfTime');
        $card_name = self::getCurrentEffectCardName();
        $method_name = self::getCardEffectMethod($card_name, 'amuletOfTime');
        $this->$method_name($cards);
    }

    function collectEnergy($player_to_energies) {
        self::checkAction('collectEnergy');
        $card_name = self::getCurrentEffectCardName();
        $method_name = self::getCardEffectMethod($card_name, 'collectEnergy');
        $this->$method_name($player_to_energies);
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    function argStartYear() {
        return array(
            'currentYear' => self::getGameStateValue('year'),
        );
    }

    function argCheckEnergy() {
        $player_id = self::getActivePlayerId();
        $energy_count = self::countPlayerEnergies($player_id);
        $reserve_size = self::getPlayerReserveSize($player_id);

        return array(
            'keep' => $reserve_size,
            'trash' => ($energy_count - $reserve_size)
        );
    }

    function argPlayerTurn() {
        return array(
            'transmutationPossible' => self::getGameStateValue('transmutationPossible'),
            'possibleCards' => $this->getPossibleCards(),
            'drawCardPossible' => self::getGameStateValue('mustDrawPowerCard'),
        );
    }

    function argBonusDrawChoice() {
        return array(
            'i18n' => array('card_name'),
            'card_name' => clienttranslate('bonus')
        );
    }
    function argBonusGainEnergy() {
        return array(
            'i18n' => array('card_name'),
            'card_name' => clienttranslate('bonus'),
            'nbr' => self::getGameStateValue('energyNbr')
        );
    }

    function argGainEnergy() {
        return array(
            'i18n' => array('card_name'),
            'card_name' => self::getCurrentEffectCardName(),
            'nbr' => self::getGameStateValue('energyNbr')
        );
    }

    function argCurrentEffectCard() {
        return array(
            'i18n' => array('card_name'),
            'card_name' => self::getCurrentEffectCardName()
        );
    }

    function argCurrentEffectCardWithId() {
        return array(
            'i18n' => array('card_name'),
            'card_name' => self::getCurrentEffectCardName(),
            'card_id' => self::getCurrentEffectCardId()
        );
    }

    function argKeepOfDiscard() {
        $card_id = self::getGameStateValue('lastCardDrawn');

        $card = $this->cards->getCard($card_id);

        return array(
            'i18n' => array('card_name'),
            'card_name' => $this->card_types[self::ot($card['type'])]['name']
        );
    }

    function argElementalChoice() {
        $bForFree = (self::getGameStateValue('elementalAmuletFree') > 0);
        if ($bForFree)
            $forfree = ' ' . clienttranslate('for free');
        else
            $forfree = '';

        $availableLimit = $bForFree ? 1 : 2;    // Note: if it's not for free, energy has to be in stock at the beginning of the turn to be available

        return array(
            'i18n' => array('card_name', 'forfree'),
            'card_name' => self::getCurrentEffectCardName(),
            'available' => array(
                1 => (self::getGameStateValue('elementalAmulet1') >= $availableLimit) ? 1 : 0,
                2 => (self::getGameStateValue('elementalAmulet2') >= $availableLimit) ? 1 : 0,
                3 => (self::getGameStateValue('elementalAmulet3') >= $availableLimit) ? 1 : 0,
                4 => (self::getGameStateValue('elementalAmulet4') >= $availableLimit) ? 1 : 0
            ),
            'forfree' => $forfree
        );
    }

    function argOpponentTarget() {
        $players = self::loadPlayersBasicInfos();
        return array(
            'i18n' => array('card_name'),
            'card_name' => self::getCurrentEffectCardName(),
            'target' => $players[self::getGameStateValue('opponentTarget')]['player_name']
        );
    }

    function argCraftyChoice() {
        $players = self::loadPlayersBasicInfos();

        $player_to_card_in_play = $this->cards->countCardsByLocationArgs('tableau');

        foreach ($players as $player_id => $dummy) {
            if (!isset($player_to_card_in_play[$player_id]))
                $player_to_card_in_play[$player_id] = 0;
        }

        $player_id = self::getActivePlayerId();
        unset($player_to_card_in_play[$player_id]);

        $targets = getKeysWithMaximum($player_to_card_in_play, false);

        return array(
            'i18n' => array('card_name'),
            'card_name' => self::getCurrentEffectCardName(),
            'targets' => $targets
        );
    }

    function argSummonVariableCost() {
        $player_id = self::getActivePlayerId();
        $card_to_summon_id = self::getGameStateValue('toSummon');
        $card_to_summon = $this->cards->getCard($card_to_summon_id);
        $card_types = self::getCardTypes();
        $card_to_summon_type = $card_types[self::ot($card_to_summon['type'])];

        $cost = $card_to_summon_type['cost'];

        $result = array(
            'i18n' => array('card_name'),
            'card_name' => $card_to_summon_type['name'],
            'costs' => array()
        );

        $stock = self::getResourceStock($player_id);

        // Add energies from amulet of waters
        $amuletOfWaterStock = self::getAmuletOfWaterStock($player_id);

        foreach ($amuletOfWaterStock as $energy_id => $qt) {
            $stock[$energy_id] += $qt;
        }

        $handOfFortunes = self::getAllCardsOfTypeInTableau(array(20), $player_id);
        $handOfFortune = 0;
        if (isset($handOfFortunes[20]))
            $handOfFortune = count($handOfFortunes[20]);

        $bStaffWinter = self::isStaffWinterActive();

        $cost_id = 0;

        // Reduce number of handOfFortune if there is not enough resource (total)
        $total = 0;
        foreach ($cost as $ress_id => $qt) {
            if ($ress_id != 0)
                $total += $qt;
        }
        $handOfFortune = min($handOfFortune, $total - 1);   // Note: hand of fortune cannot reduce energie number to 0

        $originalcost = $cost;
        unset($originalcost[0]);
        for ($i = 1; $i <= 4; $i++) {
            if (!isset($originalcost[$i]))
                $originalcost[$i] = 0;
        }

        $possiblecosts = array($originalcost);

        if ($bStaffWinter) {
            // There could be some alternative cost by replacing earth energies by something else
            $new_possible_cost = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);


            $earth_needed = $originalcost[4];

            if ($earth_needed > 0) {
                $base_cost = $originalcost;
                unset($base_cost[0]);
                $base_cost[4] = 0;

                if ($earth_needed == 1) {
                    for ($ress1 = 1; $ress1 <= 3; $ress1++) {
                        $newcost = $base_cost;
                        $newcost[$ress1]++;
                        $possiblecosts[] = $newcost;
                    }
                } else if ($earth_needed == 2) {
                    for ($ress1 = 1; $ress1 <= 3; $ress1++) {
                        $newcost = $base_cost;
                        $newcost[$ress1]++;
                        for ($ress2 = 1; $ress2 <= 3; $ress2++) {
                            $newnewcost = $newcost;
                            $newnewcost[$ress2]++;
                            $possiblecosts[] = $newnewcost;
                        }
                    }
                } else if ($earth_needed == 3) {
                    for ($ress1 = 1; $ress1 <= 4; $ress1++) {
                        $newcost = $base_cost;
                        $newcost[$ress1]++;
                        for ($ress2 = 1; $ress2 <= 4; $ress2++) {
                            $newnewcost = $newcost;
                            $newnewcost[$ress2]++;
                            for ($ress3 = 1; $ress3 <= 4; $ress3++) {
                                $newnewnewcost = $newnewcost;
                                $newnewnewcost[$ress3]++;
                                $possiblecosts[] = $newnewnewcost;
                            }
                        }
                    }
                }
            }
        }

        $possiblecosts = array_map("unserialize", array_unique(array_map("serialize", $possiblecosts)));

        foreach ($possiblecosts as $cost) {
            if ($handOfFortune == 1) {
                foreach ($cost as $ress_id => $qt) {
                    if ($qt > 0 && $ress_id != 0) {
                        $newcost = $cost;
                        unset($newcost[0]);
                        $newcost[$ress_id]--;

                        if (self::checkCostAgainstStock($newcost, $stock)) {
                            $result['costs'][$cost_id] = $newcost;  // Player has enough energy => add this cost
                            $cost_id++;
                        }
                    }
                }
            } else if ($handOfFortune == 2) {
                foreach ($cost as $ress_id => $qt) {
                    if ($qt > 0 && $ress_id != 0) {
                        $newcost = $cost;
                        unset($newcost[0]);
                        $newcost[$ress_id]--;

                        foreach ($newcost as $ress_id => $qt) {
                            if ($qt > 0 && $ress_id != 0) {
                                $newnewcost = $newcost;
                                $newnewcost[$ress_id]--;
                                if (self::checkCostAgainstStock($newnewcost, $stock)) {
                                    $result['costs'][$cost_id] = $newnewcost;  // Player has enough energy => add this cost
                                    $cost_id++;
                                }
                            }
                        }
                    }
                }
            } else if ($handOfFortune == 0) {
                if (self::checkCostAgainstStock($cost, $stock)) {
                    $result['costs'][$cost_id] = $cost;
                    $cost_id++;
                }
            }
        }

        $result['costs'] = array_map("unserialize", array_unique(array_map("serialize", $result['costs'])));

        return $result;
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state reactions   (reactions to game planned states from state machine
    ////////////

    function stDraftChoice() {
    }

    function stContinueDraftChoice() {
        if (self::getGameStateValue('draftmode') == 1) {
            // Skip draft phase (beginner mode)
            $this->gamestate->nextState('endDraftChoice');
        } else {
            $players = self::loadPlayersBasicInfos();
            foreach ($players as $player_id => $player) {
                self::giveExtraTime($player_id);
            }

            if ($this->cards->countCardInLocation('nextchoice') > count($players)) {
                // There are still at least 2 cards per player to draft
                $this->cards->moveAllCardsInLocationKeepOrder('nextchoice', 'choice');

                // Signal to players the new card choices
                $cards = $this->cards->getCardsInLocation('choice');
                $player_to_cards = array();
                foreach ($cards as $card) {
                    $player_id = $card['location_arg'];

                    if (!isset($player_to_cards[$player_id]))
                        $player_to_cards[$player_id] = array();

                    $player_to_cards[$player_id][] = $card;
                }

                foreach ($player_to_cards as $player_id => $cards) {
                    self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));
                }

                $this->gamestate->setAllPlayersMultiactive();
                $this->gamestate->nextState('continueDraftChoice');
            } else {
                // Just one card remaining for each player: automatically pick it to save a turn
                $cards = $this->cards->getCardsInLocation('nextchoice');
                foreach ($cards as $card) {
                    $player_id = $card['location_arg'];
                    $card_id = $card['id'];
                    $this->cards->moveCard($card_id, 'hand', $player_id);
                    self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card));
                }

                // Check if there are some "twist cards"
                $twists = $this->cards->getCardsOfTypeInLocation(203, null, 'hand');

                // Remove all twist from the deck
                self::DbQuery("DELETE FROM card WHERE card_type='203' AND card_location='deck'");


                if (count($twists) == 0)
                    $this->gamestate->nextState('endDraftChoice');
                else {
                    $to_active = array();
                    foreach ($twists as $twist) {
                        $to_active[] = $twist['location_arg'];

                        // Discard twist
                        $this->cards->moveCard($twist['id'], 'discard', self::incGameStateValue('discardPos', 1));
                        self::notifyAllPlayers('discard', '', array(
                            'player_id' => $twist['location_arg'],
                            'card_id' => $twist['id']
                        ));

                        // Pick 2 cards for choice
                        $cards = $this->cards->pickCardsForLocation(2, 'deck', 'choice', $twist['location_arg']);
                        self::notifyPlayer($twist['location_arg'], 'newCardChoice', '', array('cards' => $cards));
                    }

                    $this->gamestate->setPlayersMultiactive($to_active, 'endDraftChoiceTwist');
                    $this->gamestate->nextState('endDraftChoiceTwist');
                }
            }
        }
    }

    function stPrepareBuildLibrary() {
        // Remove all Twist of the faith from the game
        self::DbQuery("DELETE FROM card WHERE card_type='203'");

        $this->gamestate->setAllPlayersMultiactive();
        $this->gamestate->nextState('');
    }

    function stBuildLibraryNew() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    function stPlayerTurn() {
        // Trigger action "draw" if must draw
        // => We can't do this, otherwise the "draw Bonus" have no chance to be activated
        //      if( self::getGameStateValue( 'mustDrawPowerCard' ) == 1 )
        //          $this->doDrawPowerCard();
    }

    function stStartYear() {
        $year = self::getGameStateValue('year');
        if ($year >= 4) {
            $this->gamestate->nextState('endGame');
            return;
        }


        $this->gamestate->nextState('newyear');
    }

    function stStartRound() {
        $year = self::getGameStateValue('year');
        if ($year >= 4) {
            $this->gamestate->nextState('endGame');
            return;
        }

        // Roll season dices
        $season = self::getCurrentSeason();
        self::setGameStateValue('diceSeason', $season);
        $sql = "UPDATE dice SET dice_face=FLOOR( rand()*5 )+1, dice_player_id=NULL WHERE dice_season='$season' ";
        self::DbQuery($sql);

        self::notifyAllPlayers("newDices", '', array(
            'dices' => self::getSeasonDices()
        ));

        // All cards => inactive
        self::DbQuery("UPDATE card SET card_type_arg='0' WHERE card_type NOT IN ('215','118;215')");            // NOte: 215 = Urmian Psychic Cage is never inactivated

        $this->gamestate->nextState("newround");
    }

    function stDiceChoiceNextPlayer() {
        // Check how many dice left
        $season = self::getCurrentDiceSeason();
        $dice_count = self::getUniqueValueFromDb("SELECT COUNT( dice_id ) FROM dice
                                                 WHERE dice_player_id IS NULL AND dice_season='$season'");

        // 1 die left => end of dice choice
        if ($dice_count == 1) {
            self::activeNextPlayer();
            $this->gamestate->nextState('noMoreDice');
        } else {
            self::activeNextPlayer();
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stMaliceDie() {
        $player_id = self::getActivePlayerId();

        // Looking for die of malice
        $maliceDice = self::getAllCardsOfTypeInTableau(array(15), $player_id, true);
        if (isset($maliceDice[15])) {
            // Player has some malice Die => stay at this state to make a choice
        } else
            $this->gamestate->nextState('startTurn');
    }

    function stSteadfastDie() {

        $player_id = self::getActivePlayerId();

        self::setGameStateValue('steadfast_die_mode', 0);

        // Looking for steadfast die
        $maliceDice = self::getAllCardsOfTypeInTableau(array(114), $player_id, true);
        if (isset($maliceDice[114])) {
            // Player has some malice Die => stay at this state to make a choice
        } else
            $this->gamestate->nextState('startTurn');
    }

    function stStartPlayerTurn() {
        // Get current player dice
        $player_id = self::getActivePlayerId();
        $season = self::getCurrentDiceSeason();
        $playerDice = self::getObjectFromDB("SELECT dice_id, dice_face FROM dice
                                              WHERE dice_season='$season' AND dice_player_id='$player_id' ");

        $dice = $this->dices[$season][$playerDice['dice_id']][$playerDice['dice_face']];

        $steadfast_die_mode = self::getGameStateValue('steadfast_die_mode');

        if ($steadfast_die_mode != 0) {
            if ($steadfast_die_mode == 1)
                $dice = array('nrj' => array(1 => 1), 'inv' => false, 'card' => false, 'time' => 1, 'trans' => false, 'pts' => 0);
            else if ($steadfast_die_mode == 2)
                $dice = array('nrj' => array(2 => 1), 'inv' => false, 'card' => false, 'time' => 1, 'trans' => false, 'pts' => 0);
            else if ($steadfast_die_mode == 3)
                $dice = array('nrj' => array(3 => 1), 'inv' => false, 'card' => false, 'time' => 1, 'trans' => false, 'pts' => 0);
            else if ($steadfast_die_mode == 4)
                $dice = array('nrj' => array(4 => 1), 'inv' => false, 'card' => false, 'time' => 1, 'trans' => false, 'pts' => 0);
            else if ($steadfast_die_mode == 8)
                $dice = array('nrj' => array(), 'inv' => true, 'card' => false, 'time' => 1, 'trans' => false, 'pts' => 0);
            else if ($steadfast_die_mode == 9)
                $dice = array('nrj' => array(), 'inv' => false, 'card' => false, 'time' => 1, 'trans' => true, 'pts' => 0);
            else
                throw new feException("Invalid steadfast die action");

            self::notifyAllPlayers("steadfastDieUsage", clienttranslate('${player_name} uses Steadfast die instead of performing actions on his die.'), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName()
            ));
        }

        if ($dice['trans'] === true) {
            self::setGameStateValue('transmutationPossible', 1);
        } else {
            self::setGameStateValue('transmutationPossible', 0);
        }

        self::setGameStateValue('useOtus', 0);

        // Give automatically points
        if ($dice['pts'] > 0) {
            $pts = self::checkMinion($dice['pts'], $player_id);
            $sql = "UPDATE player SET player_score=player_score+$pts WHERE player_id='$player_id' ";
            self::DbQuery($sql);

            self::notifyAllPlayers("score", clienttranslate('${player_name} gains ${points} points with his dice'), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'points' => $pts
            ));

            // If Io's transmuter...



            $transmuter = self::getAllCardsOfTypeInTableau(array(
                109 // Io's transmuter (may transmute this turn)
            ), $player_id);

            if (isset($transmuter[109])) {
                // There's one transmuter here!
                $card_id = reset($transmuter[109]);

                self::setGameStateValue("transmutationPossible", 1);
                self::notifyAllPlayers('transmutationPossible', clienttranslate('${card_name}: ${player_name} can transmute this turn'), array(
                    'i18n' => array('card_name'),
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_name' => $this->card_types[109]['name']
                ));
            }
        }

        // Give automatically energy from the die
        self::applyResourceDelta($player_id, $dice['nrj']);

        // Give automatically invocation level from the die
        if ($dice['inv'] === true) {
            self::increaseSummoningGauge($player_id);
        }


        if ($dice['card'] === true) {
            self::setGameStateValue('mustDrawPowerCard', 1);
        } else {
            self::setGameStateValue('mustDrawPowerCard', 0);
        }

        self::giveExtraTime($player_id);

        if (self::checkEnergy($player_id))
            $this->gamestate->nextState('startTurn');
        else
            $this->gamestate->nextState('checkEnergy');
    }

    function stStartPlayerTurn2() {
        // Note: depreciated (now draw a new power card is manual

        $this->gamestate->nextState('');
    }

    function stSummonVariableCost() {
        $variableCost = self::argSummonVariableCost();
        if (count($variableCost['costs']) == 0)
            throw new feException(self::_("You have not enough energies to summon this card"), true);
    }

    // Apply current stack of cards effect
    function stCardEffect() {
        // Get the first card effect
        $effect = self::getObjectFromDB("SELECT effect_id, effect_card, effect_type, card_type, card_location_arg player_id
                                          FROM effect
                                          INNER JOIN card ON card_id=effect_card
                                          ORDER BY effect_id ASC
                                          LIMIT 0,1");

        if ($effect === null) {
            // No more effect ! Get back to initial state.           
            $this->gamestate->changeActivePlayer(self::getGameStateValue('afterEffectPlayer'));
            switch (self::getGameStateValue('afterEffectState')) {
                case 1:
                    $this->gamestate->nextState('endEffectPlayerTurn');
                    break;
                case 2:
                    $this->gamestate->nextState('endEffectEndOfRound');
                    break;
                case 3:
                    $this->gamestate->nextState('endEffectNewRound');
                    break;
                case 4:
                    $this->gamestate->nextState('endEffectNewYear');
                    break;
                case 5:
                    $this->gamestate->nextState('endEffectBeforeTurn');
                    break;
            }

            return;
        }

        // There's an effect: let's treat it !
        self::setGameStateValue('currentEffect', $effect['effect_id']);
        $nextState = "nextEffect";

        // THE big switch for card effect management...        
        $card_name = $this->card_types[self::ct($effect['card_type'])]['name'];
        $method_name = self::getCardEffectMethod($card_name, $effect['effect_type']);

        if (method_exists($this, $method_name)) {
            // There is an effect to apply for this card!
            $this->gamestate->changeActivePlayer($effect['player_id']);
            $notifArgs = array(
                'i18n' => array('card_name'),
                'player_id' => $effect['player_id'],
                'player_name' => self::getActivePlayerName(),
                'card_name' => $card_name
            );
            $changeState = $this->$method_name($effect['effect_card'], $card_name, $notifArgs);

            if ($changeState)
                $nextState = $changeState;
        } else {
            // Method does not exists.
            // For 'play', this does mean "nothing to do".
            // Otherwise it's a mistake
            if ($effect['effect_type'] != 'play')
                throw new feException("Can't find " . $method_name);
        }

        if ($nextState != 'do_not_nextState')
            $this->gamestate->nextState($nextState);
    }

    function stCheckEnergy() {
        if (self::checkEnergy(self::getActivePlayerId()))
            $this->gamestate->nextState('energyOk');
    }

    function stNextEffect() {
        // Remove current effect and go to next effect
        $effect_id = self::getGameStateValue('currentEffect');
        self::DbQuery("DELETE FROM effect WHERE effect_id='$effect_id' ");

        $this->gamestate->nextState();
    }

    function stNextPlayerTurn() {
        // All cards => unactivated, with two exceptions:
        // - Urmian Psychic Cage (215) should remain activated for the rest of the game
        // - For Io's transmuter (109), we toggle the activation state just before the
        //   endRound transition:
        //   we need to apply the end-of-round effect if it was activated before,
        //   but in order to apply the end-of-round effect, it has to be deactivated
        self::DbQuery("UPDATE card SET card_type_arg='0' WHERE ( card_type!='109' AND card_type!='118;109' AND card_type!='215' AND card_type!='118;215' )");

        // Next player
        $next_player = self::activeNextPlayer();
        $first_player = self::getGameStateValue('firstPlayer');

        // End the round if everyone has played his die
        if ($next_player == $first_player) {
            // Toggle Io's transmuter activation, see comment above
            self::DbQuery("UPDATE card SET card_type_arg=1-card_type_arg WHERE card_type IN ('109','118;109') AND card_location='tableau'");
            $this->gamestate->nextState('endRound');
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stPreEndRound() {
        // Trigger effects at end of the round, in player order
        // starting with the first player who went first this round
        $players = $this->loadPlayersBasicInfos();
        $first_player = self::getGameStateValue('firstPlayer');
        $next_player = self::createNextPlayerTable(array_keys($players));

        // Loop over all players
        $current_player = $first_player;
        for (;;) {
            $state_change = $this->playerEndOfRoundEffects($current_player);
            if ($state_change) { // State change needed
                return;
            }
            $current_player = $next_player[$current_player];
            if ($current_player == $first_player) {
                break;
            }
        }

        $this->gamestate->nextState('endRound');
    }

    // Apply end-of-round effects for this player. Return false if we are done,
    // return true if a change of state is needed for user interaction.
    function playerEndOfRoundEffects($player_id) {
        $players = $this->loadPlayersBasicInfos();

        // Get all relevant cards for this player. We want only non-activated cards:
        // cards have been de-activated in stNextPlayerTurn(), so these "activations"
        // are just to keep track of end-of-round effects.
        // For that reason, we have to make sure to activate all cards whenever
        // we apply their effect.
        $endOfRoundPermanent = self::getAllCardsOfTypeInTableau(array(
            214, // Carnivora Strombosea (if no energy, may draw a card)
            14,  // Beggar's horn (1 energy token if 1 energy or less)
            102, // Horn of Plenty (Discard one energy token, and get 5 crystals if this is a Earth)
            101, // Heart of Argos (get the earth energy on the card)
            103, // Fairy Monolith (may place 1 energy on card)
            207, // Chalice of Eternity (may place 1 energy on card)
            39,  // Titus Deepgaze (opponent gives 1 crystal, sacrifice otherwise)
            206, // Dial of colof (may reroll remaining seasons die)
            13,  // Wondrous Chest (+3 if 4+ energy)
            50,  // Damned Soul of Onys (-3 crystals)
            106, // Mesodae’s Lantern (+3 crystals)
            109, // Io's transmuter (+2 crystals if transmutation used)
            205  // Ethiel’s Fountain (+3 crystals if hand empty)
        ), $player_id, true);

        // Dial of Colof: reroll remaining die if more cards than an opponent.
        // Do this first because it involves randomness, so the player might
        // decide other effects depending on the outcome.
        if (isset($endOfRoundPermanent[206])) {
            $cards = $endOfRoundPermanent[206];
            $cards_in_tableau = $this->cards->countCardInLocation('tableau', $player_id);
            // Has more power card in play than at least 1 player ?
            $bAtLeastOne = false;
            foreach ($players as $opponent_id => $opponent) {
                $cnt = $this->cards->countCardInLocation('tableau', $opponent_id);

                if ($cnt < $cards_in_tableau)
                    $bAtLeastOne = true;
            }

            if ($bAtLeastOne) {
                self::notifyAllPlayers('simpleNode', clienttranslate('${card_name}: ${player_name} has more power cards than at least one opponent'), array(
                    'i18n' => array('card_name'),
                    'card_name' => $this->card_types[206]['name'],
                    'player_name' => $players[$player_id]['player_name']
                ));
            } else {
                self::notifyAllPlayers('simpleNode', clienttranslate('${card_name}: ${player_name} does not have more power cards than any opponent'), array(
                    'i18n' => array('card_name'),
                    'card_name' => $this->card_types[206]['name'],
                    'player_name' => $players[$player_id]['player_name']
                ));
            }

            foreach ($cards as $card_id) {
                self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate

                if ($bAtLeastOne) {
                    self::insertEffect($card_id, 'onEndTurn');
                    self::applyCardsEffect('endOfRound', $player_id);
                    return true;
                }
            }
        }

        // Damned Soul of Onys: -3 crystals
        // Do this before effects gaining crystals
        if (isset($endOfRoundPermanent[50])) {
            $cards = $endOfRoundPermanent[50];
            $loose = count($cards) * 3;

            foreach ($cards as $card_id) {
                self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate
            }
            self::DbQuery("UPDATE player SET player_score=GREATEST( 0,player_score-$loose ) WHERE player_id='$player_id'");
            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} looses ${points_disp} crystals'), array(
                'i18n' => array('card_name'),
                'player_id' => $player_id,
                'points' => -$loose,
                'points_disp' => abs($loose),
                'player_name' => $players[$player_id]['player_name'],
                'card_name' => $this->card_types['50']['name']
            ));
            self::notifyUpdateScores();
        }

        // Ethiel's Fountain: +3 crystals if empty hand
        // Do this before Carnivora Strombosea
        if (isset($endOfRoundPermanent[205])) {
            $cards = $endOfRoundPermanent[205];
            foreach ($cards as $card_id) {
                self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate
            }

            // Number of cards in hand
            if ($this->cards->countCardInLocation('hand', $player_id) == 0) {
                // Hand empty => +3 crystals for each Ethiel's Fountain
                $gain = self::checkMinion(count($cards) * 3, $player_id);

                self::DbQuery("UPDATE player SET player_score=GREATEST( 0,player_score+$gain ) WHERE player_id='$player_id'");
                self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                    'i18n' => array('card_name'),
                    'player_id' => $player_id,
                    'points' => $gain,
                    'points_disp' => abs($gain),
                    'player_name' => $players[$player_id]['player_name'],
                    'card_name' => $this->card_types['205']['name']
                ));
                self::notifyUpdateScores();
            }
        }

        // Carnivora Strombosea: no energy tokens => look at top card, maybe draw it.
        // Handle this before other effects involving energy tokens,
        // such that it gets rechecked every time, for example after Horn of Plenty.
        if (isset($endOfRoundPermanent[214])) {
            $cards = $endOfRoundPermanent[214];
            foreach ($cards as $card_id) {
                $energy_count = self::countPlayerEnergies($player_id);
                if ($energy_count == 0) {
                    self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate
                    self::insertEffect($card_id, 'onEndTurn');
                    self::applyCardsEffect('endOfRound', $player_id);
                    return true;
                }
            }
        }

        // Beggar's horn: 1 energy token if 1- energy.
        // Same reason as Carnivora Strombosea to check this early,
        // but after Carnivora Strombosea.
        if (isset($endOfRoundPermanent[14])) {
            $cards = $endOfRoundPermanent[14];
            foreach ($cards as $card_id) {
                $energy_count = self::countPlayerEnergies($player_id);
                if ($energy_count <= 1) {
                    self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate
                    self::insertEffect($card_id, 'onEndTurn');
                    self::applyCardsEffect('endOfRound', $player_id);
                    return true;
                }
            }
        }

        // Heart of Argos (get the earth energy on the card)
        if (isset($endOfRoundPermanent[101])) {
            $cards = $endOfRoundPermanent[101];
            foreach ($cards as $card_id) {
                $energy_on_heart = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);
                $total_energy = 0;
                foreach ($energy_on_heart as $ress_id => $ress_qt) {
                    $total_energy += $ress_qt;
                }
                if ($total_energy > 0) {
                    // There is a Earth on Heart
                    // All energies of cauldron => added to current player energies
                    self::applyResourceDelta($player_id, array(4 => 1));

                    // Remove all energies on heart
                    self::DbQuery("DELETE FROM resource_on_card WHERE roc_card='$card_id' ");
                    self::notifyAllPlayers('removeEnergiesOnCard', '', array('card_id' => $card_id));

                    // Apply "end of turn" effect to check maximum energy limit
                    self::insertEffect($card_id, 'onEndTurn');
                    self::applyCardsEffect('endOfRound', $player_id);
                    return true;
                }
            }
        }

        // Titus Deepgaze: opponent gives 1 crystal, sacrifice otherwise
        if (isset($endOfRoundPermanent[39])) {
            $cards = $endOfRoundPermanent[39];
            foreach ($cards as $card_id) {
                self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate

                // Each opponent gives a crystal
                $bMustSacrificeTitus = false;
                $player_to_score = self::getCollectionFromDB("SELECT player_id, player_score FROM player", true);
                foreach ($players as $opponent_id => $opponent) {
                    if ($opponent_id != $player_id) {
                        // This opponent should give a crystal to titus owner
                        if ($player_to_score[$opponent_id] == 0)
                            $bMustSacrificeTitus = true;
                        else {
                            self::DbQuery("UPDATE player SET player_score=GREATEST(0,player_score-1) WHERE player_id='$opponent_id'");
                            self::notifyAllPlayers('winPoints', '', array('player_id' => $opponent_id, 'points' => -1));

                            $points = self::checkMinion(1, $player_id);
                            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id'");
                            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gives 1 crystal to ${player_name2}'), array(
                                'i18n' => array('card_name'),
                                'player_id' => $player_id,
                                'points' => $points,
                                'player_name' => $players[$opponent_id]['player_name'],
                                'player_name2' => $players[$player_id]['player_name'],
                                'card_name' => $this->card_types['39']['name']
                            ));
                            self::notifyUpdateScores();
                        }
                    }
                }

                if ($bMustSacrificeTitus) {
                    self::insertEffect($card_id, 'onEndTurn');
                    self::applyCardsEffect('endOfRound', $player_id);
                    return true;
                }
            }
        }

        // Wondrous Chest: +3 if 4+ energy
        if (isset($endOfRoundPermanent[13])) {
            $cards = $endOfRoundPermanent[13];
            // Activate unconditionally, as there are no remaining effects which can give
            // more energy tokens (only Heart of Argos and that is already handled)
            foreach ($cards as $card_id) {
                self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate
            }

            // Check player energy number
            $energy_count = self::countPlayerEnergies($player_id);
            if ($energy_count >= 4) {
                $points = self::checkMinion(3 * count($cards), $player_id);
                self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
                self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                    'i18n' => array('card_name'),
                    'player_id' => $player_id,
                    'player_name' => $players[$player_id]['player_name'],
                    'points' => $points,
                    'card_name' => $this->card_types[13]['name']
                ));
                self::notifyUpdateScores();
            }
        }

        // Mesodae’s Lantern: +3 crystals
        if (isset($endOfRoundPermanent[106])) {
            $cards = $endOfRoundPermanent[106];
            foreach ($cards as $card_id) {
                self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate
            }
            $gain = self::checkMinion(count($cards) * 3, $player_id);

            self::DbQuery("UPDATE player SET player_score=GREATEST( 0,player_score+$gain ) WHERE player_id='$player_id'");
            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                'i18n' => array('card_name'),
                'player_id' => $player_id,
                'points' => $gain,
                'points_disp' => abs($gain),
                'player_name' => $players[$player_id]['player_name'],
                'card_name' => $this->card_types['106']['name']
            ));

            self::notifyUpdateScores();
        }

        // Io's transmuter: +2 crystals
        if (isset($endOfRoundPermanent[109])) {
            $cards = $endOfRoundPermanent[109];
            foreach ($cards as $card_id) {
                self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate
            }
            $gain = self::checkMinion(count($cards) * 2, $player_id);

            self::DbQuery("UPDATE player SET player_score=GREATEST( 0,player_score+$gain ) WHERE player_id='$player_id'");
            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gains ${points} point(s)'), array(
                'i18n' => array('card_name'),
                'player_id' => $player_id,
                'points' => $gain,
                'points_disp' => abs($gain),
                'player_name' => $players[$player_id]['player_name'],
                'card_name' => $this->card_types['109']['name']
            ));
            self::notifyUpdateScores();
        }

        // Fairy Monolith: At the end of the round, you may place 1 energy token from your reserve on the Fairy Monolith.
        if (isset($endOfRoundPermanent[103])) {
            $cards = $endOfRoundPermanent[103];
            foreach ($cards as $card_id) {
                // Has player at least 1 energy ?
                if (self::countPlayerEnergies($player_id, true) > 0) {
                    self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate
                    self::insertEffect($card_id, 'onEndTurn');
                    self::applyCardsEffect('endOfRound', $player_id);
                    return true;
                }
            }
        }

        // Chalice of eternity: same as fairy monolith
        if (isset($endOfRoundPermanent[207])) {
            $cards = $endOfRoundPermanent[207];
            foreach ($cards as $card_id) {
                // Has player at least 1 energy ?
                if (self::countPlayerEnergies($player_id, true) > 0) {
                    self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate
                    self::insertEffect($card_id, 'onEndTurn');
                    self::applyCardsEffect('endOfRound', $player_id);
                    return true;
                }
            }
        }

        // Horn of Plenty: discard one energy token, and get 5 crystals if it's Earth
        // Do this as last effect
        if (isset($endOfRoundPermanent[102])) {
            $cards = $endOfRoundPermanent[102];
            foreach ($cards as $card_id) {
                // Has player at least 1 energy ?
                if (self::countPlayerEnergies($player_id, true) > 0) {
                    self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id'"); // Activate
                    self::insertEffect($card_id, 'onEndTurn');
                    self::applyCardsEffect('endOfRound', $player_id);
                    return true;
                }
            }
        }
    }

    function stEndRound() {
        // First player => next player
        $players = $this->loadPlayersBasicInfos();
        $firstPlayer = self::getGameStateValue('firstPlayer');
        $next_player = self::createNextPlayerTable(array_keys($players));
        $firstPlayer = $next_player[$firstPlayer];
        self::setGameStateValue('firstPlayer', $firstPlayer);

        self::notifyAllPlayers('firstPlayer', clienttranslate('${player_name} is now first player'), array(
            'player_id' => $firstPlayer,
            'player_name' => $players[$firstPlayer]['player_name']
        ));

        self::incStat(1, 'turn_number');

        $this->gamestate->changeActivePlayer($firstPlayer);

        // Move time marker: get number of point on remaining dice
        $currentSeason = self::getCurrentSeason();
        $currentDiceSeason = self::getCurrentDiceSeason();
        $sql = "SELECT dice_id, dice_face FROM dice
                WHERE dice_season='$currentDiceSeason' AND dice_player_id IS NULL";
        $lastDice = self::getObjectFromDB($sql);
        if ($lastDice === null)
            throw new feException("Can't find last dice for time marker progression");

        $timeProgression = $this->dices[$currentDiceSeason][$lastDice['dice_id']][$lastDice['dice_face']]['time'];

        $month = self::getGameStateValue('month');
        $year = self::getGameStateValue('year');

        $bNewYear = false;
        $month += $timeProgression;
        if ($month > 12) {
            $month -= 12;
            $year++;
            $bNewYear = true;

            if ($year == 2 || $year == 3)
                self::giveLibaryCardsToPlayers($year);
        }

        self::setGameStateValue('month', $month);
        self::setGameStateValue('year', $year);

        self::notifyAllPlayers('timeProgression', clienttranslate('The season token moves ${step} spaces forward'), array(
            'step' => $timeProgression,
            'month' => $month,
            'year' => $year
        ));

        //        => Move to stStartYear because effects at the end of seasons should be applied before game end        
        //        if( $year >= 4 )
        //        {
        //            $this->gamestate->nextState( 'endGame' );
        //            return;
        //        }



        $newSeason = self::getCurrentSeason();

        $bSomeEffect = false;

        if ($currentSeason != $newSeason) {
            $bSomeEffect |= self::triggerEffectsOnEvent('onSeasonChange', array(
                11, // Figrim the Avaricious (each opponent gives you a crystal)
                27,  // Hourglass of time => gain 1 energy
                107 //  Statue of Eolis: Whenever the season changes, either collect 1 energy token OR receive 2 crystals and look at the top card of the draw pile.
            ));
        }

        if ($timeProgression == 3) {
            $bSomeEffect |= self::triggerEffectsOnEvent('onSeasonChange', array(
                212 // Chrono-ring
            ));
        }

        if ($bSomeEffect) {
            self::applyCardsEffect($bNewYear ? 'newYear' : 'newRound', self::getActivePlayerId());
            return;
        }


        if ($bNewYear)
            $this->gamestate->nextState('nextYear');
        else
            $this->gamestate->nextState('nextRound');
    }

    function gainPointsOnPowerCards() {
        $players = self::loadPlayersBasicInfos();
        $cards = $this->cards->getCardsInLocation('tableau');
        $player_to_score = array();
        foreach ($players as $player_id => $player) {
            $player_to_score[$player_id] = 0;
        }
        foreach ($cards as $card) {
            $player_id = $card['location_arg'];
            $player_to_score[$player_id] += $this->card_types[self::ot($card['type'])]['points'];
        }

        foreach ($player_to_score as $player_id => $points) {
            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");

            self::notifyAllPlayers('winPoints', clienttranslate('Cards played: ${player_name} gains ${points} points'), array(
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'points' => $points
            ));

            $this->updatePlayer($player_id, "player_score_raw_cards", $points);
            $this->notifyAllPlayers('rawCardsScore', '', [
                'playerId' => $player_id,
                'points' => $points,
            ]);

            self::notifyUpdateScores();

            self::incStat($points, 'points_cards_on_tableau', $player_id);
        }
    }

    function looseRemainingCardsInHand() {
        $players = self::loadPlayersBasicInfos();
        $nbrRemaining = $this->cards->countCardsByLocationArgs('hand');

        foreach ($nbrRemaining as $player_id => $nbrCards) {
            $points = -5 * $nbrCards;
            self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score+$points ) WHERE player_id='$player_id' ");

            self::notifyAllPlayers('winPoints', clienttranslate('Remaining cards in hands: ${player_name} looses ${points_disp} points for ${nbr} cards'), array(
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'points' => $points,
                'points_disp' => abs($points),
                'nbr' => $nbrCards
            ));

            $this->updatePlayer($player_id, "player_score_remaining_cards", $points);
            $this->notifyAllPlayers('scoreRemainingCards', '', [
                'playerId' => $player_id,
                'points' => $points,
            ]);

            self::notifyUpdateScores();

            self::incStat($points, 'points_remaining_cards', $player_id);
        }
    }

    function loosePointsBonus() {
        $players = self::loadPlayersBasicInfos();
        $bonusUsed = self::getCollectionFromDB("SELECT player_id, player_nb_bonus_used FROM player", true);

        foreach ($bonusUsed as $player_id => $nbrUsed) {
            if ($nbrUsed > 0) {
                if ($nbrUsed == 1)
                    $points = -5;
                else if ($nbrUsed == 2)
                    $points = -12;
                else if ($nbrUsed == 3)
                    $points = -20;

                self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score+$points ) WHERE player_id='$player_id' ");

                self::notifyAllPlayers('winPoints', clienttranslate('Bonus used: ${player_name} looses ${points_disp} points for ${nbr} bonus'), array(
                    'player_id' => $player_id,
                    'player_name' => $players[$player_id]['player_name'],
                    'points' => $points,
                    'points_disp' => abs($points),
                    'nbr' => $nbrUsed
                ));

                $this->updatePlayer($player_id, "player_score_bonus_actions", $points);
                $this->notifyAllPlayers('scoreAdditionalActions', '', [
                    'playerId' => $player_id,
                    'points' => $points,
                ]);

                self::notifyUpdateScores();

                self::incStat($points, 'points_bonus', $player_id);
            } else {
                $this->notifyAllPlayers('scoreAdditionalActions', '', [
                    'playerId' => $player_id,
                    'points' => 0,
                ]);
            }
        }
    }

    function gainTokenPoints() {
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $tokens = $this->tokensDeck->getCardsInLocation('used', $player_id);
            $points = 0;
            if (count($tokens) == 1) {

                $token = array_pop($tokens);
                $points = $this->abilityTokens[$token["type"]]['points'];
                $msg = $points < 0 ? clienttranslate('Token used: ${player_name} loses ${points_disp} points') : clienttranslate('Token used: ${player_name} gains ${points_disp} points');
                self::notifyAllPlayers('winPoints', $msg, array(
                    'player_id' => $player_id,
                    'player_name' => $players[$player_id]['player_name'],
                    'points' => $points,
                    'points_disp' => abs($points),
                ));

                $this->updatePlayer($player_id, "player_score_token", $points);
                self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
            }
            $this->notifyAllPlayers('tokenScore', '', [
                'playerId' => $player_id,
                'points' => $points,
            ]);

            self::notifyUpdateScores();

            // self::incStat($points, 'points_bonus', $player_id);

        }
    }

    function stPotionSacrificeChoice() {
        $player_id = self::getActivePlayerId();
        $zira = self::getAllCardsOfTypeInTableau(array(
            113 // Zira's shield
        ), $player_id);

        if (isset($zira[113])) {
            // There is a Zira's shield in play => must remains in this state
        } else {
            // Proceed to next state
            self::useZira(false);
        }
    }

    function stFinalScoring() {
        $finalSituation = self::getCollectionFromDB('SELECT player_id, player_score, player_invocation FROM player');
        foreach ($finalSituation as $player_id => $player) {
            self::setStat($player['player_score'], 'points_crystals', $player_id);
            self::setStat($player['player_invocation'], 'final_summoning', $player_id);
            $this->notifyAllPlayers('cristalsScore', '', [
                'playerId' => $player_id,
                'points' => $player['player_score'],
            ]);
        }

        $finalTableauSize = $this->cards->countCardsByLocationArgs('tableau');
        foreach ($finalTableauSize as $player_id => $size) {
            self::setStat($size, 'final_tableau_size', $player_id);
            self::DbQuery("UPDATE player SET player_score_aux='$size' WHERE player_id='$player_id' ");
        }

        // Score points on power cards
        self::gainPointsOnPowerCards();

        // "end of the game" points from cards effects
        self::gainEndOfGameEffectsPoints();

        // Bonus track penalties
        self::loosePointsBonus();

        // Remaining cards in hands (-5pts)
        self::looseRemainingCardsInHand();

        self::gainTokenPoints();

        //total
        $finalSituation = self::getCollectionFromDB('SELECT player_id, player_score FROM player');
        foreach ($finalSituation as $player_id => $player) {
            $this->notifyAllPlayers('scoreAfterEnd', '', [
                'playerId' => $player_id,
                'points' => $player['player_score'],
            ]);
        }

        /* if ($this->getBgaEnvironment() == 'studio')
            $this->gamestate->nextState('debugEnd'); // debug end
        else
            $this->gamestate->nextState('realEnd');*/ // real end
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Cards effects
    //////////// 


    function air_elemental_play($card_id, $card_name, $notifArgs) {
        // All energies of opponents => air

        $player_id = self::getActivePlayerId();
        $stocks = $this->getResourceStock();
        foreach ($stocks as $opponent_id => $stock) {
            if ($opponent_id != $player_id) {
                $ress_diff = array(1 => 0);
                foreach ($stock as $ress_id => $ress_qt) {
                    if ($ress_id != 1) // Note: 1=air
                    {
                        $ress_diff[1] += $ress_qt;
                        $ress_diff[$ress_id] = -$ress_qt;
                    }
                }

                self::applyResourceDelta($opponent_id, $ress_diff);
            }
        }
        self::notifyAllPlayers('airElemental', clienttranslate('${card_name}: All energies of ${player_name}`s opponents become air'), $notifArgs);
    }

    function amsug_longneck_play($card_id, $card_name, $notifArgs) {
        // Each opponent take back in hand a magical item
        $bContinue = true;
        $amsug_owner = self::getCurrentEffectCardOwner();

        while ($bContinue) {
            $player_id = self::activeNextPlayer();

            if (self::countCardOfCategoryInTableau($player_id, 'mi') > 0 && self::getUniqueValueFromDB("SELECT player_zombie FROM player WHERE player_id='$player_id'") == 0) {
                // Some magical item to take back
                return "amsugTakeback";
            }

            if ($player_id == $amsug_owner) {
                // No one has a magical item => no effect
                return;
            }
        }
    }
    function amsug_longneck_takeBack($card_id, $card_type_id) {
        // Check this is a magical item
        $player_id = self::getActivePlayerId();

        if ($this->card_types[$card_type_id]['category'] != 'mi')
            throw new feException(self::_("You must choose a magical item"), true);

        // Okay, now, let's go to the next player
        $this->gamestate->nextState('nextPlayer');

        $bContinue = true;
        $amsug_owner = self::getCurrentEffectCardOwner();

        if ($amsug_owner == $player_id)    // Current player is the last one
        {
            $this->gamestate->nextState('end');
            return;
        }

        while ($bContinue) {
            $player_id = self::activeNextPlayer();

            if (self::countCardOfCategoryInTableau($player_id, 'mi') > 0 && self::getUniqueValueFromDB("SELECT player_zombie FROM player WHERE player_id='$player_id'") == 0) {
                // Some magical item to take back
                $this->gamestate->nextState('continue');
                return;
            }

            if ($player_id == $amsug_owner) {
                // Current player is the last one
                $this->gamestate->nextState('end');
                return;
            }
        }

        throw new feException("Impossible to find the next player for Amsug Longneck");
    }

    function amulet_of_air_play($card_id, $card_name, $notifArgs) {
        // +2 summoning gauge
        $player_id = self::getActivePlayerId();
        self::increaseSummoningGauge($player_id, $card_name, 2);
    }

    function amulet_of_earth_play($card_id, $card_name, $notifArgs) {
        // +9 points
        $player_id = self::getActivePlayerId();
        $points = self::checkMinion(9, $player_id);
        $sql = "UPDATE player SET player_score=player_score+$points
                WHERE player_id='$player_id' ";
        self::DbQuery($sql);
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);
    }

    function amulet_of_fire_play($card_id, $card_name, $notifArgs) {
        if (self::getGameStateValue('mustDrawPowerCard') == 1)
            throw new feException(self::_("You must draw your power card before using this card"), true);

        // Draw 4 cards to the choice pool
        $player_id = self::getActivePlayerId();
        $cards = $this->cards->pickCardsForLocation(4, 'deck', 'choice', $player_id);
        self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));
        return 'amuletFireChoice';
    }
    function amulet_of_fire_chooseCard($card_id, $card) {
        // Place this card in player's hand
        $player_id = self::getActivePlayerId();
        $this->cards->moveCard($card_id, 'hand', $player_id);
        self::incStat(1, 'cards_drawn', $player_id);
        self::notifyUpdateCardCount();

        $notifArgs = self::getStandardArgs();
        self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} choosed a power card'), $notifArgs);
        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

        // Discard all choice
        $this->cards->moveAllCardsInLocation('choice', 'discard', $player_id, self::incGameStateValue('discardPos', 1));

        $this->gamestate->nextState('chooseCard');
    }

    function amulet_of_time_play($card_id, $card_name, $notifArgs) {
        // => Get 2 energies
        self::setGameStateValue('energyNbr', 2);
        self::setGameStateValue('elementalAmulet2', 0);  // Hack: we use elementalAmulet2 to number of Shield of Zira used

        return "gainEnergy";
    }

    function amulet_of_time_useZira($card_id, $card_name, $notifArgs, $bUseZira, $zira_card_id) {
        if (!$bUseZira)
            throw new feException("You must use Zira");

        $player_id = self::getActivePlayerId();

        $zira_shield = self::incGameStateValue('elementalAmulet2', 1);
        $total_cards_in_hand = $this->cards->countCardInLocation('hand', $player_id);
        if ($zira_shield > $total_cards_in_hand)
            throw new feException(self::_("You do not have enough cards in hand"), true);
    }


    function amulet_of_time_amuletOfTime($card_ids) {
        // Discard X power cards

        $player_id = self::getActivePlayerId();



        // Check these cards are in player's hand
        $cards = $this->cards->getCards($card_ids);
        $total_cards_in_hand = $this->cards->countCardInLocation('hand', $player_id);

        foreach ($cards as $card) {
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id)
                throw new feException("This card is not in your hand");
        }

        $cards_number = count($cards);

        // Add Shield of Zira
        $zira_shield = self::getGameStateValue('elementalAmulet2');
        $cards_number += $zira_shield;
        if ($cards_number > $total_cards_in_hand)
            throw new feException(self::_("You must unselect the card(s) you saved with Shield of Zira"), true);

        if ($cards_number == 0) {
            $this->gamestate->nextState("amuletOfTime");  // Nothing to do here (no selection)
            return;
        }

        // Place these cards in discard
        $this->cards->moveCards($card_ids, 'discard', self::incGameStateValue('discardPos', 1));

        foreach ($card_ids as $card_id) {
            self::notifyPlayer($player_id, 'discard', '', array('card_id' => $card_id, 'player_id' => $player_id));
        }

        $notifArgs = self::getStandardArgs();
        $notifArgs['nbr'] = $cards_number;
        self::notifyAllPlayers('amuletOfTime', clienttranslate('${player_name} discards ${nbr} card(s) and pick ${nbr} card(s)'), $notifArgs);

        $cards_for_player = array();
        for ($i = 0; $i < $cards_number; $i++) {
            $cards_for_player[] = $this->cards->pickCard('deck', $player_id);
        }

        self::notifyUpdateCardCount();
        self::notifyPlayer($player_id, "pickPowerCards", '', array("cards" => $cards_for_player));

        if ($cards_number == 1) {
            $card = reset($cards_for_player);
            self::setGameStateValue('lastCardDrawn', $card['id']);
            self::mayUseEscaped();
        }

        $this->gamestate->nextState('amuletOfTime');
    }

    function amulet_of_water_play($card_id, $card_name, $notifArgs) {
        // => Get 4 energy (note: for amulet of water => managed on gainEnergy method)
        self::setGameStateValue('energyNbr', 4);
        return "gainEnergy";
    }

    function arcane_telescope_active($card_id, $card_name, $notifArgs) {
        if (self::getGameStateValue('mustDrawPowerCard') == 1)
            throw new feException(self::_("You must draw your power card before using this card"), true);

        // Discard 2 crystals  
        // Player should have  2 crystals
        $player_id = self::getActivePlayerId();
        $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
        if ($player_score < 2)
            throw new feException(self::_("You don't have enough crystals to do this action"), true);

        self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score-2 ) WHERE player_id='$player_id' ");
        self::notifyAllPlayers('winPoints', '', array('player_id' => $player_id, 'points' => -2));
        self::notifyUpdateScores();

        // Draw 3 cards to the choice pool (N=number of player)
        $cards = $this->cards->pickCardsForLocation(3, 'deck', 'choice', $player_id);
        self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));

        return "telescopeChoice";
    }
    function arcane_telescope_chooseCard($card_id, $card) {
        // Place this card on top of draw pile
        $player_id = self::getActivePlayerId();

        $this->cards->insertCardOnExtremePosition($card_id, 'deck', true);
        self::notifyUpdateCardCount();

        $notifArgs = self::getStandardArgs();
        $notifArgs['card'] = $card_id;
        self::notifyPlayer($player_id, "removeFromChoice", clienttranslate('${player_name} replace a card on top of the draw pile'), $notifArgs);

        if ($this->cards->countCardInLocation("choice", $player_id) == 0)
            $this->gamestate->nextState("chooseCard");
    }

    function argos_hawk_play($card_id, $card_name, $notifArgs) {
        // +1 summoning gauge
        $player_id = self::getActivePlayerId();
        self::increaseSummoningGauge($player_id, $card_name, 1);

        // +10 crystals
        $points = self::checkMinion(10, $player_id);
        self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
        $notifArgs['points'] = $points;
        self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gets ${points} crystals'), $notifArgs);
        self::notifyUpdateScores();
    }

    function argos_hawk_active($card_id, $card_name, $notifArgs) {
        return "potionSacrificeChoice";
    }
    function argos_hawk_useZira($card_id, $card_name, $notifArgs, $bUseZira) {
        $player_id = self::getActivePlayerId();
        if (!$bUseZira) {
            // ... sacrifice
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);

            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }

        $players = self::loadPlayersBasicInfos();

        foreach ($players as $opponent_id => $opponent) {
            if ($opponent_id != $player_id) {
                $points = self::checkMinion(6, $opponent_id);
                self::DbQuery("UPDATE player SET player_score=GREATEST(0,player_score+$points) WHERE player_id='$opponent_id'");

                self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gets ${points} crystals'), array(
                    'i18n' => array('card_name'),
                    'player_id' => $opponent_id,
                    'card_name' => $this->card_types['117']['name'],
                    'player_name' => $players[$opponent_id]['player_name'],
                    'points' => $points
                ));
                self::notifyUpdateScores();

                // -1 summoning jauge
                $sql = "UPDATE player SET player_invocation=GREATEST( 0, CAST( player_invocation AS SIGNED )-1 )
                        WHERE player_id='$opponent_id' ";
                self::DbQuery($sql);

                self::notifyAllPlayers(
                    "incInvocationLevel",
                    clienttranslate('${card_name}: ${player_name} decreases his summoning gauge by 1'),
                    array(
                        'i18n' => array('card_name'),
                        'nbr' => -1,
                        'player_id' => $opponent_id,
                        'card_name' => $this->card_types['117']['name'],
                        'player_name' => $players[$opponent_id]['player_name']
                    )
                );
            }
        }
    }

    function argosian_tangleweed_play($card_id, $card_name, $notifArgs) {
        // Is there a magical item on opponents hands
        $player_id = self::getActivePlayerId();
        $all_cards = $this->cards->getCardsInLocation('tableau');

        foreach ($all_cards as $card) {
            if ($card['location_arg'] != $player_id) {
                if ($this->card_types[self::ot($card['type'])]['category'] == 'f')
                    return 'argosianChoice';
            }
        }

        self::notifyAllPlayers('noFamiliarItems', clienttranslate('${card_name}: there is no Familiar item to lock'), $notifArgs);
    }

    function argosian_tangleweed_chooseOpponentCard($card_id, $card_type_id, $amuletEnergies) {
        $argosian_id = self::getCurrentEffectCardId();
        $player_id = self::getActivePlayerId();

        $card = $this->cards->getCard($card_id);
        $card_type_id = self::ot($card['type']);  // We need to use original type for this card
        $card_type = $this->card_types[$card_type_id];


        // Check this is a magical item
        if ($card_type['category'] != 'f')
            throw new feException(self::_("You must choose a familiar"), true);

        // Link between Argosian and the item
        self::DbQuery("INSERT INTO argosian (argosian_id,argosian_locked_item) VALUES ('$argosian_id','$card_id') ");

        // Change the type of the target card into the type "argosian" (217)
        self::DbQuery("UPDATE card SET card_type='$card_type_id;217' WHERE card_id='$card_id' ");

        if ($card_type_id == 217) {
            // The target IS an argosian
            // In this case, we destroy immediately its lock, as if it would leave the game
            self::cleanArgosianLock($card_id);
        }

        self::adaptReserveSize($card['location_arg']);    // Just in case we blocked a card with an effect on the reserve size (ex: Raven copied Bespelled Grimoire)

        $notifArgs = self::getStandardArgs();

        $notifArgs['i18n'][] = 'name';
        $notifArgs['name'] = $this->card_types[self::ot($card_type_id)]['name'];
        $notifArgs['card_id'] = $card_id;
        $notifArgs['card_type'] = $card_type_id . ';217';
        $notifArgs['player_id'] = $card['location_arg'];
        self::notifyAllPlayers('ravenCopy', clienttranslate('${card_name}: ${player_name} chooses to lock ${name}'), $notifArgs);

        $this->gamestate->nextState('chooseOpponentCard');
    }



    function arus_s_mimicry_play($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        // => check that there is at least a familiar in tableau OR in hand
        $player_id = self::getActivePlayerId();
        $item_nbr = $this->cards->countCardInLocation('tableau', $player_id);
        $item_nbr += $this->cards->countCardInLocation('hand', $player_id);

        if ($item_nbr == 0)
            self::notifyAllPlayers('noFamilarItems', clienttranslate('${card_name}: there is no power card to discard or to sacrifice'), $notifArgs);
        else {
            return "arusSacrifice";
        }
    }
    function arus_s_mimicry_discard($card_id, $card_type_id) {
        $player_id = self::getActivePlayerId();
        // +12 points
        $points = self::checkMinion(12, $player_id);

        $sql = "UPDATE player SET player_score=player_score+$points
                WHERE player_id='$player_id' ";
        self::DbQuery($sql);
        $notifArgs = self::getStandardArgs();
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);
        $this->gamestate->nextState('discard');
    }
    function arus_s_mimicry_sacrifice($card_id, $card_type_id) {
        $player_id = self::getActivePlayerId();
        // +12 points
        $points = self::checkMinion(12, $player_id);

        $sql = "UPDATE player SET player_score=player_score+$points
                WHERE player_id='$player_id' ";
        self::DbQuery($sql);
        $notifArgs = self::getStandardArgs();
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);
        $this->gamestate->nextState('sacrifice');
    }

    function balance_of_ishtar_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        if (self::getGameStateValue('cards_version', 1) == 1) {
            // Second edition of Balance of Ishtar
            // Check that there are at least 3 identical energy for this player

            $stock = self::getResourceStock($player_id);
            $amStock = self::getAmuletOfWaterStock($player_id);

            $bAtLeastOneOverThree = false;
            if (!self::isStaffWinterActive()) {
                foreach ($stock as $ress_id => $qt) {
                    if (isset($amStock[$ress_id]))
                        $stock[$ress_id] += $amStock[$ress_id];

                    if ($stock[$ress_id] >= 3)
                        $bAtLeastOneOverThree = true;
                }
            } else {
                $total = 0;
                foreach ($stock as $ress_id => $qt) {
                    if (isset($amStock[$ress_id]))
                        $total += $amStock[$ress_id];

                    $total += $qt;
                }

                if ($total >= 3)
                    $bAtLeastOneOverThree = true;
            }

            if (!$bAtLeastOneOverThree)
                throw new feException(self::_("You don't have 3 identical energy tokens"), true);

            return "discardIshtar2";
        } else {
            // Check that there are at least 4 identical energy for this player
            $stock = self::getResourceStock($player_id);
            $amStock = self::getAmuletOfWaterStock($player_id);

            $bAtLeastOneOverFour = false;
            foreach ($stock as $ress_id => $qt) {
                if (isset($amStock[$ress_id]))
                    $stock[$ress_id] += $amStock[$ress_id];


                if ($stock[$ress_id] >= 4)
                    $bAtLeastOneOverFour = true;
            }

            if (!$bAtLeastOneOverFour)
                throw new feException(self::_("You don't have 4 identical resources"), true);

            return "discardIshtar";
        }
    }

    function balance_of_ishtar_discardEnergy($discarded) {
        if (self::getGameStateValue('cards_version', 1) == 1) {
            if (!self::isStaffWinterActive()) {
                // Second edition of Balance of Ishtar
                // Check if energies discarded are identical
                if (count($discarded) != 1)
                    throw new feException(self::_("You must discard 3 identical energies"), true);
                if (reset($discarded) != -3)
                    throw new feException(self::_("You must discard 3 identical energies"), true);
            } else {
                // Just check there are 3 energies
                $total = 0;
                foreach ($discarded as $id => $qt) {
                    $total += $qt;
                }
                if ($total != -3)
                    throw new feException(self::_("You must discard 3 identical energies"), true);
            }

            // Alright => +9 pts  
            $player_id = self::getActivePlayerId();
            $points = 9;

            $transmutationPossible = self::getGameStateValue("transmutationPossible");
            if ($transmutationPossible == 2) // Bonus +1 for transmutation : in the official FAQ published by libellud, it's explicitely written that the bonus of transmute and/or io's purse should be added to the ishtar cristallisation. You can find it there (in French) : www.libellud.com/index.php?option=com_zoo&task=callelement&format=raw&item_id=507&element=66b58ffc-c789-4cef-b9f1-2cb5226ebace&method=download&args[0]=f0aab041a0c90f4d675853ae5b413eb0&Itemid=57        
                $points = 12;
            if ($transmutationPossible == 3)
                $points = 15;
            if ($transmutationPossible == 4)
                $points = 18;

            // +3 points per Purse of Io (cf Purse of Io game help on rules p6)
            $purseOfIo = self::getAllCardsOfTypeInTableau(array(
                8, // Purse of Io
            ), $player_id);

            if (isset($purseOfIo[8])) {
                $points += 3 * count($purseOfIo[8]);
            }

            $points = self::checkMinion($points, $player_id);
            $sql = "UPDATE player SET player_score=player_score+$points
                    WHERE player_id='$player_id' ";
            self::DbQuery($sql);
            $notifArgs = self::getStandardArgs();
            $notifArgs['points'] = $points;
            self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);


            $this->gamestate->nextState('discardEnergy');
        } else {
            // Check if energies discarded are 4 identical
            if (count($discarded) != 1)
                throw new feException(self::_("You must discard 4 identical energies"), true);
            if (reset($discarded) != -4)
                throw new feException(self::_("You must discard 4 identical energies"), true);

            // Alright => +12 pts  
            $player_id = self::getActivePlayerId();
            $points = 12;

            // +4 points per Purse of Io (cf Purse of Io game help on rules p6)
            $purseOfIo = self::getAllCardsOfTypeInTableau(array(
                8, // Purse of Io
            ), $player_id);

            if (isset($purseOfIo[8])) {
                $points += 4 * count($purseOfIo[8]);
            }

            $points = self::checkMinion($points, $player_id);

            $sql = "UPDATE player SET player_score=player_score+$points
                    WHERE player_id='$player_id' ";
            self::DbQuery($sql);
            $notifArgs = self::getStandardArgs();
            $notifArgs['points'] = $points;
            self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);

            $this->gamestate->nextState('discardEnergy');
        }
    }

    function beggar_s_horn_onEndTurn($card_id, $card_name, $notifArgs) {
        // => Get 1 energy
        self::setGameStateValue('energyNbr', 1);
        return "gainEnergy";
    }

    function bespelled_grimoire_play($card_id, $card_name, $notifArgs) {
        // Reserve size => 10
        $player_id = self::getActivePlayerId();
        self::adaptReserveSize($player_id);

        // => Get 2 energies
        self::setGameStateValue('energyNbr', 2);
        return "gainEnergy";
    }

    function chalice_of_eternity_onEndTurn($card_id, $card_name, $notifArgs) {
        return "chaliceEternity";
    }

    function chalice_of_eternity_discardEnergy($energies) {
        $player_id = self::getActivePlayerId();

        if (count($energies) != 1)
            throw new feException(self::_("You must choose exactly 1 energy token"), true);
        if (reset($energies) != -1)
            throw new feException(self::_("You must choose exactly 1 energy token"), true);

        $energy_id = key($energies);

        $card_id = self::getCurrentEffectCardId();

        $energy_on_cauldron = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);

        $notifArgs = self::getStandardArgs();
        $notifArgs['card_id'] = $card_id;

        if (isset($energy_on_cauldron[$energy_id])) {
            self::DbQuery("UPDATE resource_on_card SET roc_qt=roc_qt+1
                            WHERE roc_card='$card_id' AND roc_id='$energy_id' ");
        } else {
            self::DbQuery("INSERT INTO resource_on_card (roc_card,roc_id,roc_qt,roc_player) VALUES
                            ('$card_id','$energy_id','1','$player_id') ");
        }

        $notifArgs = self::getStandardArgs();
        $notifArgs['energy'] = '<div class="sicon energy' . $energy_id . '"></div>';
        $notifArgs['energy_type'] = $energy_id;
        $notifArgs['card_id'] = $card_id;
        self::notifyAllPlayers('placeEnergyOnCard', clienttranslate('${player_name} places a ${energy} on ${card_name}'), $notifArgs);

        $this->gamestate->nextState('discardEnergy');
    }

    function chalice_of_eternity_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        $energy_on_cauldron = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);

        // Check energies are there
        $total_energy = 0;
        $consumed = 0;
        $toconsume = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
        foreach ($energy_on_cauldron as $energy_type => $qt) {
            $total_energy += $qt;

            $can_be_consumed = $qt;
            while ($consumed < 4 && $can_be_consumed > 0) {
                $toconsume[$energy_type]++;
                $can_be_consumed--;
                $consumed++;
            }
        }

        if ($total_energy < 4)
            throw new feException(self::_("There is not enough energies on Chalice of Eternity"), true);

        // Consume energies on card
        foreach ($toconsume as $energy_type => $qt) {
            if ($qt > 0) {
                self::DbQuery("UPDATE resource_on_card SET roc_qt=roc_qt-$qt
                                WHERE roc_card='$card_id' AND roc_id='$energy_type' ");

                for ($i = 0; $i < $qt; $i++) {
                    self::notifyAllPlayers('removeEnergyOnCard', '', array(
                        'player_id' => $player_id,
                        'card_id' => $card_id,
                        'energy_type' => $energy_type
                    ));
                }
            }
        }


        $notifArgs = self::getStandardArgs();

        self::notifyAllPlayers("simpleNote", clienttranslate('${card_name}: ${player_name} discards 4 energies'), $notifArgs);

        // Draw 4 cards to the choice pool
        $player_id = self::getActivePlayerId();
        $cards = $this->cards->pickCardsForLocation(4, 'deck', 'choice', $player_id);
        self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));

        // Trigger effect
        return "chaliceEternityChoice";
    }

    function chalice_of_eternity_chooseCard($card_id, $card) {
        $notifArgs = self::getStandardArgs();
        $player_id = self::getActivePlayerId();
        if (self::checkSummoningGauge()) {
            // Place this card in player's hand & summon it for free
            $this->cards->moveCard($card_id, 'hand', $player_id);
            self::incStat(1, 'cards_drawn', $player_id);
            self::notifyUpdateCardCount();

            self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

            // Discard all other choice
            $this->cards->moveAllCardsInLocation('choice', 'discard', $player_id, self::incGameStateValue('discardPos', 1));

            $this->gamestate->nextState('chooseCard');

            // Summon the new card for free
            self::summon($card_id, array(), true);
        } else {
            $this->cards->moveAllCardsInLocation('choice', 'discard', $player_id, self::incGameStateValue('discardPos', 1));
            self::notifyAllPlayers('divineChaliceCancel', clienttranslate('${card_name}: summoning gauge is not big enough'), $notifArgs);
            $this->gamestate->nextState('chooseCard');
        }
    }


    function chrono_ring_onSeasonChange($card_id, $card_name, $notifArgs) {
        self::setGameStateValue('energyNbr', 1);
        return "chronoRingChoice";
    }

    function crafty_nightshade_play($card_id, $card_name, $notifArgs) {
        // Draw 2 cards
        $player_id = self::getActivePlayerId();

        $card = $this->cards->pickCard('deck', $player_id);
        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card));
        $card = $this->cards->pickCard('deck', $player_id);
        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card));

        self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} draw 2 power cards'), $notifArgs);
        self::notifyUpdateCardCount();
        self::incStat(2, 'cards_drawn', $player_id);

        return "craftyChoice";
    }

    function crafty_nightshade_choosePlayer($opponent_id) {
        $player_id = self::getActivePlayerId();

        $args = self::argCraftyChoice();

        if (in_array($opponent_id, $args['targets'])) {
            self::setGameStateValue('opponentTarget', $opponent_id);

            $this->gamestate->nextState('choosePlayer');
        } else
            throw new feException('You cannot choose this opponnent');
    }

    function crafty_nightshade_chooseCardHand($card_id, $card) {
        // Give this card to target player
        $players = self::loadPlayersBasicInfos();
        $player_id = self::getActivePlayerId();
        $target_id = self::getGameStateValue('opponentTarget');

        $this->cards->moveCard($card_id, 'hand', $target_id);
        $card = $this->cards->getCard($card_id);

        self::notifyUpdateCardCount();
        self::notifyAllPlayers('simpleNote', clienttranslate('${card_name}: ${player_name} gives a card to ${target}'), array(
            'i18n' => array('card_name'),
            'card_name' => $this->card_types[220]['name'],
            'player_name' => self::getActivePlayerName(),
            'target' => $players[$target_id]['player_name']
        ));

        self::notifyPlayer($player_id, 'discard', '', array('player_id' => $player_id, 'card_id' => $card_id));
        self::notifyPlayer($target_id, "pickPowerCards", '', array("cards" => array($card)));

        $this->gamestate->nextState('chooseCardHand');
    }

    function carnivora_strombosea_onEndTurn($card_id, $card_name, $notifArgs) {
        // See first card on the drawpile
        $player_id = self::getActivePlayerId();
        $card = $this->cards->pickCardForLocation('deck', 'choice', $player_id);
        self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => array($card)));
        return "carnivoraChoice";
    }

    function crystal_orb_active($card_id, $card_name, $notifArgs) {
        if (self::getGameStateValue('mustDrawPowerCard') == 1)
            throw new feException(self::_("You must draw your power card before using this card"), true);


        // 2 activations possible
        return "orbChoice";
    }

    function crystal_orb_dualChoice($choice) {
        $player_id = self::getActivePlayerId();
        $notifArgs = self::getStandardArgs();

        if ($choice == 0) {
            // See first card on the drawpile
            $card = $this->cards->pickCardForLocation('deck', 'choice', $player_id);
            self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => array($card)));
            $this->gamestate->nextState('seeNextCard');
        } else {
            // Discard first card on the drawpile for 3 crystals
            $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
            if ($player_score < 3)
                throw new feException(self::_("You don't have enough energy token or crystals to do this action"), true);

            self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score-3 ) WHERE player_id='$player_id' ");
            self::notifyAllPlayers('winPoints', '', array('player_id' => $player_id, 'points' => -3));
            self::notifyUpdateScores();

            self::notifyAllPlayers('discardFirstCard', clienttranslate('${card_name}: the first card of the draw pile is discarded'), $notifArgs);
            $this->cards->pickCardForLocation('deck', 'discard', self::incGameStateValue('discardPos', 1));
            self::notifyUpdateCardCount();

            $this->gamestate->nextState('discardNextCard');
        }
    }
    function crystal_orb_orbChoice($bReplace, $energies) {
        $player_id = self::getActivePlayerId();
        $notifArgs = self::getStandardArgs();

        $cards = $this->cards->getCardsInLocation('choice', $player_id);
        $card = reset($cards);
        $card_id = $card['id'];

        if ($bReplace) {
            // Replace card in the top of the deck
            $this->cards->insertCardOnExtremePosition($card_id, 'deck', true);

            self::notifyAllPlayers('replaceOrb', clienttranslate('${card_name}: ${player_name} replaces the card on the top of the deck'), $notifArgs);
            $this->gamestate->nextState('orbChoice');
        } else {
            if (count($energies) != 4)
                throw new feException(self::_("You must select 4 energy tokens to use for this action"), true);

            // Apply the cost of amulet of waters
            self::applyAmuletOfWaterEnergyCost($energies);
            $originalEnergies = self::mergeEnergyInRealCost($energies);
            $energies = self::filterAmuletOfWaterEnergies($energies);

            $cost = array();
            foreach ($energies as $energy) {
                if (!isset($cost[$energy]))
                    $cost[$energy] = 0;
                $cost[$energy]--;
            }

            // Check & Consume resources
            self::applyResourceDelta($player_id, $cost, true);

            // Then, move the card in hand and summon it immediately
            $this->cards->moveCard($card_id, 'hand', $player_id);
            self::incStat(1, 'cards_drawn', $player_id);
            self::notifyUpdateCardCount();

            self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

            $this->gamestate->nextState('orbChoice');

            // Summon the new card for free
            self::summon($card_id, array(), true);
        }
    }

    function carnivora_strombosea_dualChoice($bReplace) {
        $player_id = self::getActivePlayerId();
        $notifArgs = self::getStandardArgs();

        $cards = $this->cards->getCardsInLocation('choice', $player_id);
        $card = reset($cards);
        $card_id = $card['id'];

        if ($bReplace) {
            // Replace card in the top of the deck
            $this->cards->insertCardOnExtremePosition($card_id, 'deck', true);

            self::notifyAllPlayers('replaceOrb', clienttranslate('${card_name}: ${player_name} replaces the card on the top of the deck'), $notifArgs);
            $this->gamestate->nextState('dualChoice');
        } else {
            // Move the card in hand 
            $this->cards->moveCard($card_id, 'hand', $player_id);
            self::incStat(1, 'cards_drawn', $player_id);
            self::notifyUpdateCardCount();

            $notifArgs['card'] = $card;
            $notifArgs['fromChoice'] = true;
            self::notifyPlayer($player_id, "pickPowerCard", clienttranslate('${card_name}: ${player_name} chooses to keep the card'), $notifArgs);

            // -1 summoning gauge
            $sql = "UPDATE player SET player_invocation=GREATEST( 0, CAST( player_invocation AS SIGNED )-1 )
                    WHERE player_id='$player_id' ";
            self::DbQuery($sql);

            self::notifyAllPlayers("incInvocationLevel", '', array('nbr' => -1, 'player_id' => $player_id));

            self::setGameStateValue('lastCardDrawn', $card_id);
            self::mayUseEscaped();


            $this->gamestate->nextState('dualChoice');
        }
    }

    function cursed_treatise_of_arus_play($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();
        self::increaseSummoningGauge($player_id, $card_name, 1);

        // +10 crystals
        $points = self::checkMinion(10, $player_id);
        self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} crystals'), $notifArgs);

        // +2 energies
        self::setGameStateValue('energyNbr', 2);
        return "gainEnergy";
    }

    function crystal_titan_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();
        if (!self::checkPlayerCanSacrificeCard($player_id))
            throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);

        return 'potionSacrificeChoice';
    }


    function crystal_titan_useZira($card_id, $card_name, $notifArgs, $bUseZira) {
        $player_id = self::getActivePlayerId();


        if (!$bUseZira) {
            // ... sacrifice
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);

            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }


        // Get cards details
        $cards = $this->cards->getCardsInLocation('hand', $player_id);

        foreach ($cards as $card_id => $card) {
            // Okay, card can be discarded
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::notifyUpdateCardCount();

            $card_type = $this->card_types[$card['type']];

            $card_name = self::getCurrentEffectCardName();

            self::notifyAllPlayers('discard', clienttranslate('${card_name}: ${player_name} discards ${discarded}'), array(
                'i18n' => array('card_name', 'sacrified'),
                'card_name' => $card_name,
                'card_id' => $card_id,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'discarded' => $card_type['name']
            ));
        }

        // Discard all crystals
        $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
        self::DbQuery("UPDATE player SET player_score=0 WHERE player_id='$player_id'");
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} looses ${points_disp} crystals'), array(
            'i18n' => array('card_name'),
            'player_id' => $player_id,
            'points' => -$player_score,
            'points_disp' => abs($player_score),
            'player_name' => $players[$player_id]['player_name'],
            'card_name' => $this->card_types[303]['name']
        ));
        self::notifyUpdateScores();

        return "crystalTitanChoice";
    }

    function crystal_titan_chooseOpponentCard($card_id, $card_type_id, $amuletEnergies) {
        $player_id = self::getActivePlayerId();

        $card_types = self::getCardTypes();
        $card_type = $card_types[$card_type_id];
        $card = $this->cards->getCard($card_id);

        // Sacrifice this card
        $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
        self::cleanTableauCard($card_id, $player_id, true, true); // ... and force it



        self::notifyAllPlayers('discardFromTableau', clienttranslate('${card_name}: ${player_name} sacrifices ${sacrified}'), array(
            'i18n' => array('card_name', 'sacrified'),
            'card_name' => "Crystal Titan",
            'card_id' => $card_id,
            'player_id' => $card['location_arg'],
            'player_name' => self::getActivePlayerName(),
            'sacrified' => $card_type['name']
        ));
        self::notifyUpdateCardCount();

        self::adaptReserveSize($card['location_arg']);



        $this->gamestate->nextState("chooseOpponentCard");
    }

    function crystal_titan_dualChoice() {
        $this->gamestate->nextState("chooseOpponentCard");
    }

    function damned_soul_of_onys_play($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        // +1 water
        self::applyResourceDelta($player_id, array(2 => 1));

        // +10 crystals
        $points = self::checkMinion(10, $player_id);
        self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} crystals'), $notifArgs);

        return "checkEnergy";
    }

    function damned_soul_of_onys_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        try {
            // Cost = 1 water
            self::applyResourceDelta($player_id, array(2 => -1));
        } catch (feException $exception) {
            // Try to see if we have an amulet of water with a water on it
            $extrawater = self::getUniqueValueFromDB("SELECT roc_card FROM resource_on_card
                                                       INNER JOIN card ON card_id=roc_card
                                                       WHERE roc_id='2' AND roc_qt>0
                                                       AND card_location='tableau' AND card_location_arg='$player_id'
                                                       AND card_type IN ('4','118;4') ");   // 4 = amulet of water

            if ($extrawater === null)
                throw $exception;

            self::applyAmuletOfWaterEnergyCost(array($extrawater . '2'));
        }

        // This card => to the next player
        $players = self::loadPlayersBasicInfos();
        $next_player_table = self::createNextPlayerTable(array_keys($players));
        $nextPlayer = $next_player_table[$player_id];
        $this->cards->moveCard($card_id, 'tableau', $nextPlayer);

        // Unactive this card
        $sql = "UPDATE card SET card_type_arg='0' WHERE card_id='$card_id' ";
        self::DbQuery($sql);

        $card = $this->cards->getCard($card_id);
        self::notifyAllPlayers('summon', '', array(
            'i18n' => array('card_name'),
            'player_id' => $nextPlayer,
            'card' => $card,
            'fromTableau' => $player_id
        ));

        self::notifyAllPlayers('discardFromTableau', clienttranslate('${player_name} pass ${card_name} to ${player_name2}'), array(
            'i18n' => array('card_name'),
            'card_name' => $card_name,
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'player_name2' => $players[$nextPlayer]['player_name']
        ));
    }

    function demon_of_argos_play($card_id, $card_name, $notifArgs) {
        if (self::getGameStateValue('mustDrawPowerCard') == 1)
            throw new feException(self::_("You must draw your power card before using this card"), true);


        $player_id = self::getActivePlayerId();

        $players = self::loadPlayersBasicInfos();

        self::notifyAllPlayers('demonOfArgos', clienttranslate('${card_name}: opponents of ${player_name} decreases their summoning gauge and draw a card'), $notifArgs);
        $lastCardDrawn = null;
        $lastCardDrawnOpp = null;
        foreach ($players as $opponent_id => $opponent) {
            if ($opponent_id != $player_id) {
                // -1 summoning gauge
                $sql = "UPDATE player SET player_invocation=GREATEST( 0, CAST( player_invocation AS SIGNED )-1 )
                        WHERE player_id='$opponent_id' ";
                self::DbQuery($sql);

                self::notifyAllPlayers("incInvocationLevel", '', array('nbr' => -1, 'player_id' => $opponent_id));

                $card = $this->cards->pickCard('deck', $opponent_id);
                self::notifyUpdateCardCount();

                self::notifyPlayer($opponent_id, "pickPowerCard", '', array("card" => $card));
                $lastCardDrawn = $card['id'];
                $lastCardDrawnOpp = $opponent_id;
            }
        }

        if (count($players) == 2) {
            // Can apply Speedwall the Escaped
            // Note : this is too much work to apply speedwall the escaped with more than 1 opponent...

            self::setGameStateValue('lastCardDrawn', $lastCardDrawn);
            self::mayUseEscaped($lastCardDrawnOpp);
        }
    }

    function dial_of_colof_play($card_id, $card_name, $notifArgs) {
        // +2 summoning gauge
        $player_id = self::getActivePlayerId();
        self::increaseSummoningGauge($player_id, $card_name, 2);
    }

    function dial_of_colof_onEndTurn($card_id, $card_name, $notifArgs) {
        return "dialColofDualChoice";
    }
    function dial_of_colof_dualChoice($choice) {
        self::checkAction('dualChoice');

        if ($choice == 0)
            $this->gamestate->nextState('dualChoice');
        else {
            // Reroll this die
            $newvalue = bga_rand(1, 6);
            $season = self::getCurrentDiceSeason();
            $sql = "UPDATE dice SET dice_face=$newvalue
                    WHERE dice_season='$season' AND dice_player_id IS NULL ";
            self::DbQuery($sql);

            $dice = self::getObjectFromDB("SELECT dice_id id, dice_season season, dice_face face FROM dice WHERE dice_season='$season' AND dice_player_id IS NULL ");

            $notifArgs = self::getStandardArgs();
            $notifArgs['dice'] = $dice;
            self::notifyAllPlayers("rerollSeasonsDice", clienttranslate('${card_name}: ${player_name} rerolls remaining Seasons dice'), $notifArgs);

            $this->gamestate->nextState('dualChoice');
        }
    }

    function die_of_malice_play($card_id, $card_name, $notifArgs) {
        // Can't be used at the first turn => mark it as "active"
        self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$card_id' ");
    }

    function die_of_malice_active($card_id, $card_name, $notifArgs) {
        // Reroll season dice of current player
        $season = self::getCurrentDiceSeason();
        $player_id = self::getActivePlayerId();

        $newvalue = bga_rand(1, 6);
        $sql = "UPDATE dice SET dice_face=$newvalue
                WHERE dice_season='$season' AND dice_player_id='$player_id' ";
        self::DbQuery($sql);

        $dice = self::getObjectFromDB("SELECT dice_id id, dice_season season, dice_face face FROM dice WHERE dice_season='$season' AND dice_player_id='$player_id' ");

        $notifArgs['dice'] = $dice;
        $points = self::checkMinion(2, $player_id);
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("rerollDice", clienttranslate('${card_name}: ${player_name} reroll his dice and gets ${points} crystals'), $notifArgs);

        // +2 crystals
        self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
        self::notifyAllPlayers("score", '', $notifArgs);

        // Is there another die to active ?
        $maliceDice = self::getAllCardsOfTypeInTableau(array(15), $player_id);
        if (isset($maliceDice[15])) {
            // Player has some malice Die => stay at this state to make a choice
        } else
            $this->gamestate->nextState('startTurn');
    }

    function divine_chalice_play($card_id, $card_name, $notifArgs) {
        if (self::getGameStateValue('mustDrawPowerCard') == 1)
            throw new feException(self::_("You must draw your power card before using this card"), true);

        // Draw 4 cards to the choice pool
        $player_id = self::getActivePlayerId();
        $cards = $this->cards->pickCardsForLocation(4, 'deck', 'choice', $player_id);
        self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));
        return 'divineChoice';
    }


    function divine_chalice_chooseCard($card_id, $card) {
        $notifArgs = self::getStandardArgs();
        $player_id = self::getActivePlayerId();
        if (self::checkSummoningGauge()) {
            // Place this card in player's hand & summon it for free
            $this->cards->moveCard($card_id, 'hand', $player_id);
            self::incStat(1, 'cards_drawn', $player_id);
            self::notifyUpdateCardCount();

            self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

            // Discard all other choice
            $this->cards->moveAllCardsInLocation('choice', 'discard', $player_id, self::incGameStateValue('discardPos', 1));

            $this->gamestate->nextState('chooseCard');

            // Summon the new card for free
            self::summon($card_id, array(), true);
        } else {
            $this->cards->moveAllCardsInLocation('choice', 'discard', $player_id, self::incGameStateValue('discardPos', 1));
            self::notifyAllPlayers('divineChaliceCancel', clienttranslate('${card_name}: summoning gauge is not big enough'), $notifArgs);
            $this->gamestate->nextState('chooseCard');
        }
    }

    function dragon_skull_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        // Sacrifice 3 power cards
        if ($this->cards->countCardInLocation('tableau', $player_id) < 3)
            throw new feException(self::_("You don't have enough power cards in your tableau"), true);

        if (!self::checkPlayerCanSacrificeCard($player_id, 3))
            throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);


        return "dragonSkull";
    }
    function dragon_skull_sacrifice($card_id, $card_type_id) {
        if (self::checkAction('lastsacrifice', false)) {
            // Win 15 points
            $player_id = self::getActivePlayerId();
            $notifArgs = self::getStandardArgs();
            $points = self::checkMinion(15, $player_id);
            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
            $notifArgs['points'] = $points;
            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gets ${points} crystals'), $notifArgs);
            self::notifyUpdateScores();
        }

        $this->gamestate->nextState('sacrifice');
    }

    function dragonsoul_active($card_id, $card_name, $notifArgs) {
        // Spend 1 crystal
        $player_id = self::getActivePlayerId();
        $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
        if ($player_score < 1)
            throw new feException(self::_("You don't have enough crystals to do this action"), true);

        // Check at least one card in tableau is activated
        $cards = $this->cards->getCardsInLocation('tableau', $player_id);
        $bAtLeastOneActivated = false;
        foreach ($cards as $card) {
            if ($card['type_arg'] == 0 || self::ct($card['type']) == 201 || self::ct($card['type']) == 215) {
            } else
                $bAtLeastOneActivated = true;
        }

        if (!$bAtLeastOneActivated)
            throw new feException(self::_("No cards can be targeted by Dragonsoul"), true);

        self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score-1 ) WHERE player_id='$player_id' ");
        self::notifyAllPlayers('winPoints', '', array('player_id' => $player_id, 'points' => -1));
        self::notifyUpdateScores();

        return "dragonSoulCardChoice";
    }

    function dragonsoul_chooseTableauCard($card_id, $card_name) {
        // Check this card has been activated
        $card = $this->cards->getCard($card_id);

        if ($card['type_arg'] == 0)
            throw new feException(self::_("Please choose a card that has been activated already"), true);

        if (self::ct($card['type']) == 201)
            throw new feException(self::_("Please choose a card other than Dragonsoul"), true);

        if (self::ct($card['type']) == 215)
            throw new feException(self::_("This card cannot be targeter by Dragonsoul"), true);


        // Unactive this card
        $sql = "UPDATE card SET card_type_arg='0' WHERE card_id='$card_id'";
        self::DbQuery($sql);

        $notifArgs = self::getStandardArgs();
        $notifArgs['i18n'][] = 'card_target_name';
        $notifArgs['card_target_name'] = $this->card_types[$card['type']]['name'];
        $notifArgs['card_id'] = $card_id;
        self::notifyAllPlayers('inactivateCard', clienttranslate('${card_name}: ${player_name} straighten ${card_target_name}'), $notifArgs);

        $this->gamestate->nextState('chooseTableauCard');
    }

    function elemental_amulet_play($card_id, $card_name, $notifArgs) {
        // Need at least 1 energy
        $player_id = self::getActivePlayerId();

        $player_stock = self::getResourceStock($player_id);
        $player_stock_aw = self::getAmuletOfWaterStock($player_id);

        $bAtLeastOneEnergy = false;
        for ($ress_id = 1; $ress_id <= 4; $ress_id++) {
            if (
                $player_stock[$ress_id] > 0
                || (isset($player_stock_aw[$ress_id]) && $player_stock_aw[$ress_id] > 0)
            ) {
                // At least one energy of this type is available 
                $bAtLeastOneEnergy = true;
                self::setGameStateValue('elementalAmulet' . $ress_id, 2);
            } else {
                self::setGameStateValue('elementalAmulet' . $ress_id, 1);
            }
        }

        if (self::isStaffWinterActive()) {
            self::setGameStateValue('elementalAmulet4', 2);   // With staff of Winter, earth is always possible & in stock
        }

        if (self::getGameStateValue('elementalAmuletFree') != 4) // Note: 4 = elemental amulet invoked for free
        {
            $handOfFortune = count(self::getAllCardsOfTypeInTableau(array(20), $player_id));
            self::setGameStateValue('elementalAmuletFree', $handOfFortune);

            if (!$bAtLeastOneEnergy)
                throw new feException(self::_("You need at least one energy"), true);
        } else {
            // Invoked for free
        }
        return "elementalChoice";
    }
    function elemental_amulet_chooseEnergyType($energy_id, $amuletEnergies) {
        if (self::getGameStateValue('elementalAmulet' . $energy_id) == 0)
            throw new feException("You already used this power");
        self::setGameStateValue('elementalAmulet' . $energy_id, 0);

        $player_id = self::getActivePlayerId();
        $notifArgs = self::getStandardArgs();

        if (self::getGameStateValue('elementalAmuletFree') > 0) {
            // Do not consume any resource for this one
            self::incGameStateValue('elementalAmuletFree', -1);
        } else {
            if (count($amuletEnergies) == 1) {
                $amuletEnergy = reset($amuletEnergies);

                if ($amuletEnergy >= 1 && $amuletEnergy <= 3 && $energy_id == 4 && self::isStaffWinterActive()) {
                    // Use Staff of Winter to use a earth energy
                    $cost = array($amuletEnergy => -1);
                    self::applyResourceDelta($player_id, $cost, true);
                } else {
                    self::applyAmuletOfWaterEnergyCost($amuletEnergies);
                    $originalEnergies = self::mergeEnergyInRealCost($amuletEnergies);
                    if (reset($originalEnergies) != $energy_id)
                        throw new feException(self::_("Wrong selected energy"), true);

                    $cost_to_apply = self::filterAmuletOfWaterEnergies($amuletEnergies);
                    if (count($cost_to_apply) == 1) {
                        // Still a cost to apply
                        // Consume this energy
                        $cost = array($energy_id => -1);
                        // Check & Consume resources
                        self::applyResourceDelta($player_id, $cost, true);
                    }
                }
            } else {
                // Consume this energy
                $cost = array($energy_id => -1);
                // Check & Consume resources
                self::applyResourceDelta($player_id, $cost, true);
            }

            $notifArgs['energy'] = '<div class="sicon energy' . $energy_id . '"></div>';
            self::notifyAllPlayers("elementalAmulet", clienttranslate('${card_name}: ${player_name} uses a ${energy}'), $notifArgs);
        }

        if ($energy_id == 1) {
            // Air: increase summoning gauge
            self::increaseSummoningGauge($player_id, $notifArgs['card_name'], 1);
            $this->gamestate->nextState('continue');
        } else if ($energy_id == 2) {
            // Water => 2 energy token
            self::setGameStateValue('energyNbr', 2);
            $this->gamestate->nextState('gainEnergy');
        } else if ($energy_id == 3) {
            // Fire: draw a power card
            $card = $this->cards->pickCard('deck', $player_id);

            self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} draw a power card'), $notifArgs);
            self::notifyUpdateCardCount();
            self::incStat(1, 'cards_drawn', $player_id);

            self::setGameStateValue('lastCardDrawn', $card['id']);
            self::mayUseEscaped();


            self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card));
            $this->gamestate->nextState('continue');
        } else if ($energy_id == 4) {
            // Earth => 5 points
            $points = self::checkMinion(5, $player_id);
            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
            $notifArgs['points'] = $points;
            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);
            self::notifyUpdateScores();
            $this->gamestate->nextState('continue');
        }
    }

    function elemental_amulet_cardEffectEnd() {
        self::setGameStateValue('elementalAmuletFree', 0);

        // Must use at least one energy
        if (
            self::getGameStateValue('elementalAmulet1') > 0
            && self::getGameStateValue('elementalAmulet2') > 0
            && self::getGameStateValue('elementalAmulet3') > 0
            && self::getGameStateValue('elementalAmulet4') > 0
        ) {
            throw new feException(self::_("You must use at least one energy"), true);
        } else {
            $this->gamestate->nextState('chooseEnergyType');
        }
    }

    function eolis_s_replicator_active($card_id, $card_name, $notifArgs) {
        // Check for water energy
        self::checkTotalResourceCost(array(2 => 1));

        if ($this->cards->countCardInLocation('replica_deck') == 0)
            throw new feException(self::_("No more Replica cards"), true);

        if (!self::checkSummoningGauge())
            throw new feException(self::_("You summoning gauge is not big enough to summon this new card"), true);

        // Discard a water energy token
        return 'discardEolis';
    }

    function eolis_s_replicator_discardEnergy($discarded) {
        if (count($discarded) != 1)
            throw new feException(self::_("You must discard 1 Water energy"), true);
        if (reset($discarded) != -1)
            throw new feException(self::_("You must discard 1 Water energy"), true);
        if (key($discarded) != 2)
            throw new feException(self::_("You must discard 1 Water energy"), true);

        // Get a replicator
        if ($this->cards->countCardInLocation('replica_deck') == 0)
            throw new feException(self::_("No more Replica cards"), true);

        // Check summoning gauge  
        if (!self::checkSummoningGauge())
            throw new feException(self::_("You summoning gauge is not big enough to summon this new card"), true);

        $player_id = self::getActivePlayerId();

        $card = $this->cards->pickCardForLocation('replica_deck', 'tableau', $player_id);


        self::notifyUpdateCardCount();

        // Notify all players
        self::notifyAllPlayers('summon', clienttranslate('${player_name} places a new ${card_name} into play'), array(
            'i18n' => array('card_name'),
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $this->card_types[222]['name'],
            'card' => $card,
            'fromNoWhere' => true
        ));

        // Trigger effects of Urmian Psychic Cage
        $onSummonCardsAllPlayer = array(
            215,  // Urmian Psychic Cage
        );
        self::triggerEffectsOnEvent('onSummon', $onSummonCardsAllPlayer, null, true);

        self::insertEffect($card['id'], 'play');

        $this->gamestate->nextState('discardEnergy');
    }

    function estorian_harp_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        // Check that there are at least 2 identical energy for this player

        $stock = self::getResourceStock($player_id);
        $amStock = self::getAmuletOfWaterStock($player_id);

        $bAtLeastOneOverTwo = false;
        if (!self::isStaffWinterActive()) {
            foreach ($stock as $ress_id => $qt) {
                if (isset($amStock[$ress_id]))
                    $stock[$ress_id] += $amStock[$ress_id];

                if ($stock[$ress_id] >= 2)
                    $bAtLeastOneOverTwo = true;
            }
        } else {
            $total = 0;
            foreach ($stock as $ress_id => $qt) {
                if (isset($amStock[$ress_id]))
                    $total += $amStock[$ress_id];

                $total += $qt;
            }

            if ($total >= 2)
                $bAtLeastOneOverTwo = true;
        }

        if (!$bAtLeastOneOverTwo)
            throw new feException(self::_("You don't have 2 identical energy tokens"), true);





        return "discardEstorian";
    }

    function estorian_harp_discardEnergy($discarded) {
        if (!self::isStaffWinterActive()) {
            // Check if energies discarded are 4 identical
            if (count($discarded) != 1)
                throw new feException(self::_("You must discard 2 identical energies"), true);
            if (reset($discarded) != -2)
                throw new feException(self::_("You must discard 2 identical energies"), true);
        } else {
            // Just check there are 2 energies
            $total = 0;
            foreach ($discarded as $id => $qt) {
                $total += $qt;
            }
            if ($total != -2)
                throw new feException(self::_("You must discard 2 identical energies"), true);
        }



        // Alright => +3 pts  
        $player_id = self::getActivePlayerId();
        $points = self::checkMinion(3, $player_id);

        $sql = "UPDATE player SET player_score=player_score+$points
                WHERE player_id='$player_id' ";
        self::DbQuery($sql);
        $notifArgs = self::getStandardArgs();
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);

        // +1 summoning gauge
        self::increaseSummoningGauge($player_id, $notifArgs['card_name'], 1);

        $this->gamestate->nextState('discardEnergy');
    }

    function fairy_monolith_onEndTurn($card_id, $card_name, $notifArgs) {
        return "fairyMonolith";
    }


    function fairy_monolith_discardEnergy($energies) {
        $player_id = self::getActivePlayerId();

        if (count($energies) != 1)
            throw new feException(self::_("You must choose exactly 1 energy token"), true);
        if (reset($energies) != -1)
            throw new feException(self::_("You must choose exactly 1 energy token"), true);

        $energy_id = key($energies);

        $card_id = self::getCurrentEffectCardId();

        $energy_on_cauldron = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);

        $notifArgs = self::getStandardArgs();
        $notifArgs['card_id'] = $card_id;

        if (isset($energy_on_cauldron[$energy_id])) {
            self::DbQuery("UPDATE resource_on_card SET roc_qt=roc_qt+1
                            WHERE roc_card='$card_id' AND roc_id='$energy_id' ");
        } else {
            self::DbQuery("INSERT INTO resource_on_card (roc_card,roc_id,roc_qt,roc_player) VALUES
                            ('$card_id','$energy_id','1','$player_id') ");
        }

        $notifArgs = self::getStandardArgs();
        $notifArgs['energy'] = '<div class="sicon energy' . $energy_id . '"></div>';
        $notifArgs['energy_type'] = $energy_id;
        $notifArgs['card_id'] = $card_id;
        self::notifyAllPlayers('placeEnergyOnCard', clienttranslate('${player_name} places a ${energy} on ${card_name}'), $notifArgs);

        $this->gamestate->nextState('discardEnergy');
    }

    function fairy_monolith_active($card_id, $card_name, $notifArgs) {
        $energy_on_cauldron = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);

        // Check energies are there
        $total_energy = 0;
        foreach ($energy_on_cauldron as $energy_type => $qt) {
            $total_energy += $qt;
        }

        if ($total_energy == 0)
            throw new feException(self::_("There is no energies on Fairy Monolith"), true);


        return 'fairyMonolithActive';
    }

    function fairyMonolithActive($energies) {
        self::checkAction("fairyMonolithActive");

        $player_id = self::getActivePlayerId();
        $card_id = self::getCurrentEffectCardId();
        $energy_on_cauldron = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);
        $resources_delta = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);

        // Check energies are there
        $total_energy = 0;
        foreach ($energies as $energy_type) {
            if (!isset($energy_on_cauldron[$energy_type]))
                throw new feException("Can't found the selected energy");
            if ($energy_on_cauldron[$energy_type] <= 0)
                throw new feException("Can't found the selected energy");
            $energy_on_cauldron[$energy_type]--;
            $resources_delta[$energy_type]++;
            $total_energy++;
        }

        if ($total_energy == 0)
            throw new feException(self::_("You must select at least one energy on Fairy Monolith"), true);


        // Get back these energy in player's hand

        // All energies of cauldron => added to current player energies
        self::applyResourceDelta($player_id, $resources_delta);

        // Remove all energies on monolith
        foreach ($resources_delta as $energy_type => $qt) {
            if ($qt > 0) {
                self::DbQuery("UPDATE resource_on_card SET roc_qt=roc_qt-$qt
                                WHERE roc_card='$card_id' AND roc_id='$energy_type' ");

                for ($i = 0; $i < $qt; $i++) {
                    self::notifyAllPlayers('removeEnergyOnCard', '', array(
                        'player_id' => $player_id,
                        'card_id' => $card_id,
                        'energy_type' => $energy_type
                    ));
                }
            }
        }

        $notifArgs = self::getStandardArgs();
        $notifArgs['nbr'] = $total_energy;
        self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} returns ${nbr} energies in his hand'), $notifArgs);

        $this->gamestate->nextState('fairyMonolithActive');
    }

    function familiar_catcher_play($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        $notifArgs = self::getStandardArgs();

        // See first card on the drawpile
        for ($i = 1; $i < 200; $i++)  // To avoid infinite loop
        {
            $card = $this->cards->pickCardForLocation('deck', 'choice', $player_id);
            if ($this->card_types[self::ot($card['type'])]['category'] == 'f') {
                // We found our familiar !
                self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => array($card)));
                return 'familiarChoice';
            } else {
                // Magical item => discard it
                $notifArgs['i18n'][] = 'reveal';

                $notifArgs['reveal'] = $this->card_types[self::ot($card['type'])]['name'];
                self::notifyAllPlayers('revealCard', clienttranslate('${card_name}: ${player_name} reveals ${reveal}'), $notifArgs);
                $this->cards->moveCard($card['id'], 'discard', self::incGameStateValue('discardPos', 1));
            }
        }
    }

    function familiar_catcher_dualChoice($choice) {
        $player_id = self::getActivePlayerId();
        $notifArgs = self::getStandardArgs();

        $cards = $this->cards->getCardsInLocation('choice', $player_id);
        $card = reset($cards);
        $card_id = $card['id'];

        if ($choice == 0) {
            // Add card to my hand
            $this->cards->moveCard($card_id, 'hand', $player_id);
            self::incStat(1, 'cards_drawn', $player_id);
            self::notifyUpdateCardCount();

            self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

            $notifArgs['i18n'][] = 'card_name2';
            $notifArgs['card_name2'] = $this->card_types[self::ot($card['type'])]['name'];
            self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} places ${card_name2} in his hand'), $notifArgs);


            $this->gamestate->nextState('familiarAddToHand');
        } else {
            // Discard the card
            $this->cards->moveCard($card_id, 'discad');

            // Get the next familiar (see "familiar_catcher_play" above)
            for ($i = 1; $i < 200; $i++)  // To avoid infinite loop
            {
                $card = $this->cards->pickCardForLocation('deck', 'choice', $player_id);
                if ($this->card_types[self::ot($card['type'])]['category'] == 'f') {
                    // We found our familiar !
                    // => add to hand
                    $card_id = $card['id'];
                    $this->cards->moveCard($card_id, 'hand', $player_id);
                    self::incStat(1, 'cards_drawn', $player_id);
                    self::notifyUpdateCardCount();

                    self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => false));

                    $notifArgs['i18n'][] = 'card_name2';
                    $notifArgs['card_name2'] = $this->card_types[self::ot($card['type'])]['name'];
                    self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} places ${card_name2} in his hand'), $notifArgs);

                    $this->gamestate->nextState('familiarAddToHand');
                    return;
                } else {
                    // Magical item => discard it
                    $notifArgs['i18n'][] = 'reveal';
                    $notifArgs['reveal'] = $this->card_types[self::ot($card['type'])]['name'];
                    self::notifyAllPlayers('revealCard', clienttranslate('${card_name}: ${player_name} reveals ${reveal}'), $notifArgs);
                    $this->cards->moveCard($card['id'], 'discard', self::incGameStateValue('discardPos', 1));
                }
            }

            // Note: not supposed to happened
            $this->gamestate->nextState('familiarDiscard');
        }
    }

    function figrim_the_avaricious_onSeasonChange($card_id, $card_name, $notifArgs) {
        $players = self::loadPlayersBasicInfos();
        $opponentIds = array();
        foreach ($players as $player_id => $player) {
            $opponentIds[] = $player_id;
        }


        $player_id = self::getActivePlayerId();
        $player_to_score = self::getCollectionFromDB("SELECT player_id, player_score FROM player", true);
        $points_win = 0;
        foreach ($player_to_score as $opponent_id => $opponent_score) {
            if ($opponent_id != $player_id && $opponent_score > 0) {
                self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score-1 ) WHERE player_id='$opponent_id' ");
                self::notifyAllPlayers('winPoints', '', array('player_id' => $opponent_id, 'points' => -1));
                self::notifyUpdateScores();
                $points_win++;
            }
        }

        if ($points_win > 0) {
            $points_win = self::checkMinion($points_win, $player_id);
            self::DbQuery("UPDATE player SET player_score=player_score+$points_win WHERE player_id='$player_id' ");
            $notifArgs['points'] = $points_win;
            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} steals ${points} points.'), $notifArgs);
            self::notifyUpdateScores();
        }
    }

    function glutton_cauldron_active($card_id, $card_name, $notifArgs) {
        // Discard 1 energy
        if (self::countPlayerEnergies(self::getActivePlayerId(), true) == 0)
            throw new feException(self::_("You don't have any energies"), true);

        $energy_on_cauldron = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);
        $total_energy = 0;
        foreach ($energy_on_cauldron as $ress_id => $ress_qt) {
            $total_energy += $ress_qt;
        }
        if ($total_energy == 6) {
            $player_id = self::getActivePlayerId();
            if (!self::checkPlayerCanSacrificeCard($player_id))
                throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);
        }

        return "cauldronPlace";
    }

    function glutton_cauldron_discardEnergy($energies) {
        $player_id = self::getActivePlayerId();

        if (count($energies) != 1)
            throw new feException(self::_("You must choose exactly 1 energy token"), true);
        if (reset($energies) != -1)
            throw new feException(self::_("You must choose exactly 1 energy token"), true);

        $energy_id = key($energies);

        $card_id = self::getCurrentEffectCardId();

        $energy_on_cauldron = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);
        $total_energy = 0;
        foreach ($energy_on_cauldron as $ress_id => $ress_qt) {
            $total_energy += $ress_qt;
        }

        $notifArgs = self::getStandardArgs();
        $notifArgs['card_id'] = $card_id;

        if ($total_energy == 6) {
            // 6 + new one = 7 => trigger immediately the effect of Cauldron

            // Add last discarded energy to energy_on_cauldron
            if (!isset($energy_on_cauldron[$energy_id]))
                $energy_on_cauldron[$energy_id] = 0;
            $energy_on_cauldron[$energy_id]++;

            // All energies of cauldron => added to current player energies
            self::applyResourceDelta($player_id, $energy_on_cauldron);

            // Remove all energies on cauldron
            self::DbQuery("DELETE FROM resource_on_card WHERE roc_card='$card_id' ");
            self::notifyAllPlayers('removeEnergiesOnCard', '', array('card_id' => $card_id));

            // Gain 15 crystals
            $points = self::checkMinion(15, $player_id);
            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
            $notifArgs['points'] = $points;
            self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} placed 7 energies on Glutton Cauldron and gets ${points} crystals.'), $notifArgs);
            self::notifyUpdateScores();

            $this->gamestate->nextState('potionSacrificeChoice');
        } else {
            if (isset($energy_on_cauldron[$energy_id])) {
                self::DbQuery("UPDATE resource_on_card SET roc_qt=roc_qt+1
                                WHERE roc_card='$card_id' AND roc_id='$energy_id' ");
            } else {
                self::DbQuery("INSERT INTO resource_on_card (roc_card,roc_id,roc_qt,roc_player) VALUES
                                ('$card_id','$energy_id','1','$player_id') ");
            }

            $notifArgs = self::getStandardArgs();
            $notifArgs['energy'] = '<div class="sicon energy' . $energy_id . '"></div>';
            $notifArgs['energy_type'] = $energy_id;
            $notifArgs['card_id'] = $card_id;
            self::notifyAllPlayers('placeEnergyOnCard', clienttranslate('${player_name} places a ${energy} on ${card_name}'), $notifArgs);

            $this->gamestate->nextState('discardEnergy');
        }
    }

    function glutton_cauldron_useZira($card_id, $card_name, $notifArgs, $bUseZira) {
        if (!$bUseZira) {
            // Sacrifice Cauldron
            $player_id = self::getActivePlayerId();
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);
            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }
    }

    function heart_of_argos_onEndTurn() {
        return "checkEnergy";
    }

    function hourglass_of_time_onSeasonChange($card_id, $card_name, $notifArgs) {
        // => Get 1 energy
        self::setGameStateValue('energyNbr', 1);
        return "gainEnergy";
    }
    function horn_of_plenty_onEndTurn($card_id, $card_name, $notifArgs) {
        // => Get 1 energy
        self::setGameStateValue('energyNbr', 1);
        return "discardHornPlenty";
    }
    function horn_of_plenty_discardEnergy($discarded) {
        $player_id = self::getActivePlayerId();

        // Check if 1 energy is discarded
        if (count($discarded) != 1)
            throw new feException(self::_("You must discard 1 energy"), true);
        if (reset($discarded) != -1)
            throw new feException(self::_("You must discard 1 energy"), true);

        if (key($discarded) == 4 || self::isStaffWinterActive()) {
            // +5 crystals
            $points = self::checkMinion(5, $player_id);
            $notifArgs = self::getStandardArgs();
            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
            $notifArgs['points'] = $points;
            self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} crystals'), $notifArgs);
        }

        $this->gamestate->nextState('discardEnergy');
    }

    function idol_of_the_familiar_play($card_id, $card_name, $notifArgs) {
        if (self::getGameStateValue('cards_version', 1) == 1) {
            // New version of Idol of the familiar
            $player_id = self::getActivePlayerId();

            // +10 crystals
            $points = self::checkMinion(10, $player_id);
            self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
            $notifArgs['points'] = $points;
            self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} crystals'), $notifArgs);
        }
    }

    function idol_of_the_familiar_active($card_id, $card_name, $notifArgs) {
        // 1 point for each familiar
        $player_id = self::getActivePlayerId();

        $points = self::checkMinion(self::countCardOfCategoryInTableau($player_id, 'f'), $player_id);
        $sql = "UPDATE player SET player_score=player_score+$points
                WHERE player_id='$player_id' ";
        self::DbQuery($sql);
        $notifArgs = self::getStandardArgs();
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);
    }

    function igramul_the_banisher_play($card_id, $card_name, $notifArgs) {
        return "igramulChoice";
    }

    function igramul_the_banisher_dualChoice($choice_id) {
        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();

        if (!isset($this->card_types[$choice_id]))
            throw new feException('Invalid choice');

        // Opponents reveals their hands
        //      $cards = $this->cards->getCardsInLocation( 'hand' );

        $notifArgs = self::getStandardArgs();
        $notifArgs['i18n'][] = 'choice';
        $notifArgs['choice'] = $this->card_types[$choice_id]['name'];
        self::notifyAllPlayers('simpleNote', clienttranslate('${card_name}: ${player_name} names ${choice}'), $notifArgs);

        $this->setGameStateValue('elementalAmulet1', $choice_id); // Storing card type id
        $this->setGameStateValue('elementalAmulet2', 0);          // No cards discarded for now

        $this->gamestate->nextState('dualChoice');
        self::activeNextPlayer();
        $this->gamestate->nextState('continue');

        /*      
        $bAtLeastOneFound = false;
        
        foreach( $players as $opponent_id => $player )
        {
            if( $player_id != $opponent_id )
            {
                $card_list = array(
                    'i18n' => array(),
                    'log' => array(),
                    'args' => ''
                );
                
                // Reveal cards
                $card_no = 0;
                foreach( $cards as $card )
                {
                    if( $card['location_arg'] == $opponent_id )
                    {
                        $card_list['i18n'][] = 'card'.$card_no;
                        $card_list['log'][] = '${card'.$card_no.'}';
                        $card_list['args']['card'.$card_no] = $this->card_types[ $card['type'] ]['name'];
                        $card_no++;
                        
                        if( $card['type'] == $choice_id )
                        {
                            $bAtLeastOneFound = true;
                            
                            // Discard this card
                            $this->cards->moveCard( $card['id'], 'discard', self::incGameStateValue( 'discardPos', 1 ) );
                            self::notifyAllPlayers( 'discard', clienttranslate('${card_name}: ${player_name} discards ${discarded}'), array(
                                'i18n' => array( 'card_name', 'sacrified' ),
                                'card_name' => $this->card_types[ 221 ]['name'],
                                'card_id' => $card['id'],
                                'player_id' => $opponent_id,
                                'player_name' => $players[ $opponent_id ]['player_name'],
                                'discarded' => $this->card_types[ $card['type'] ]['name']
                            ) ); 

                        }
                    }
                }
                
                $card_list['log'] = implode( ' / ', $card_list['log'] );
                
                self::notifyAllPlayers( 'simpleNote', clienttranslate('${player_name} hand: ${card_list}'), array(
                    'card_list' => $card_list,
                    'player_name' => $player['player_name']
                ) );
            }
        }
        
        if( $bAtLeastOneFound )
        {
            // Gain the energies of the card
            $card_types = self::getCardTypes();
            $to_gain = $card_types[ $choice_id ]['cost'];
            unset( $to_gain[0] );
            self::applyResourceDelta( $player_id, $to_gain );
            
            self::notifyAllPlayers( 'simpleNote', clienttranslate('${card_name}: ${player_name} receive energies on card summoning cost'), $notifArgs );
        }
        
        $this->gamestate->nextState('dualChoice');*/
    }

    function stIgramulDiscard() {
        $player_id = self::getActivePlayerId();
        $igramul_owner = self::getCurrentEffectCardOwner();
        $choice_id = self::getGameStateValue('elementalAmulet1');

        $notifArgs = self::getStandardArgs();
        $notifArgs['i18n'][] = 'choice';
        $notifArgs['choice'] = $this->card_types[$choice_id]['name'];


        if ($player_id == $igramul_owner) {
            // End of this effect

            if (self::getGameStateValue('elementalAmulet2') == 1) {
                // Gain the energies of the card

                $card_types = self::getCardTypes();
                $to_gain = $card_types[$choice_id]['cost'];
                unset($to_gain[0]);
                self::applyResourceDelta($player_id, $to_gain);

                self::notifyAllPlayers('simpleNote', clienttranslate('${card_name}: ${player_name} receive energies on card summoning cost'), $notifArgs);
            }

            $this->gamestate->nextState('end');
            return;
        }


        // Is there a Zira shield ?
        $zira = self::getAllCardsOfTypeInTableau(array(
            113 // Zira's shield
        ), $player_id);


        $choices = $this->cards->getCardsOfTypeInLocation($choice_id, null, 'hand', $player_id);

        if (isset($zira[113]) && count($choices) > 0) {
            // There is a Zira's shield in play => must remains in this state
        } else {
            // Proceed to next state
            self::useZira(false);
        }
    }
    function igramul_the_banisher_useZira($card_id, $card_name, $notifArgs, $bUseZira) {
        $player_id = self::getActivePlayerId();

        $bAtLeastOneFound = false;

        $players = self::loadPlayersBasicInfos();
        $player = $players[$player_id];
        $choice_id = self::getGameStateValue('elementalAmulet1');
        $cards = $this->cards->getCardsInLocation('hand', $player_id);

        $notifArgs = self::getStandardArgs();
        $notifArgs['i18n'][] = 'choice';
        $notifArgs['choice'] = $this->card_types[$choice_id]['name'];

        $card_list = array(
            'log' => array(),
            'args' => array(
                'i18n' => array()
            )

        );

        // Reveal cards
        $card_no = 0;
        foreach ($cards as $card) {
            $card_list['args']['i18n'][] = 'card' . $card_no;
            $card_list['log'][] = '${card' . $card_no . '}';
            $card_list['args']['card' . $card_no] = $this->card_types[$card['type']]['name'];
            $card_no++;

            if ($card['type'] == $choice_id && !$bUseZira) {
                $bAtLeastOneFound = true;

                // Discard this card
                $this->cards->moveCard($card['id'], 'discard', self::incGameStateValue('discardPos', 1));
                self::notifyAllPlayers('discard', clienttranslate('${card_name}: ${player_name} discards ${discarded}'), array(
                    'i18n' => array('card_name', 'sacrified'),
                    'card_name' => $this->card_types[221]['name'],
                    'card_id' => $card['id'],
                    'player_id' => $player_id,
                    'player_name' => $players[$player_id]['player_name'],
                    'discarded' => $this->card_types[$card['type']]['name']
                ));
            }
        }

        if ($bAtLeastOneFound) {
            if (self::getGameStateValue('elementalAmulet2') == 0) {
                self::setGameStateValue('elementalAmulet2', 1);
            }
        }

        $card_list['log'] = implode(' / ', $card_list['log']);

        self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} hand: ${card_list}'), array(
            'card_list' => $card_list,
            'player_name' => $player['player_name']
        ));

        // ... and then, go to next player
        $this->gamestate->nextState('nextPlayer');
        self::activeNextPlayer();
        $this->gamestate->nextState('continue');

        return 'do_not_nextState';
    }

    function io_s_minion_play($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        // +1 air
        $energy_id = 1;
        $nbr = 1;

        self::applyResourceDelta($player_id, array($energy_id => $nbr));
        $notifArgs = self::getStandardArgs();
        $notifArgs['energy'] = '<div class="sicon energy' . $energy_id . '"></div>';
        $notifArgs['nbr'] = $nbr;
        self::notifyAllPlayers('gainEnergy', clienttranslate('${card_name}: ${player_name} gets ${nbr} ${energy}'), $notifArgs);

        // +1 summoning gauge
        self::increaseSummoningGauge($player_id, $card_name, 1);
    }

    function io_s_minion_active($card_id, $card_name, $notifArgs) {
        // Check for air energy
        self::checkTotalResourceCost(array(1 => 1));

        // Select 1 air
        return 'discardMinion';
    }

    function io_s_minion_discardEnergy($discarded) {
        if (count($discarded) != 1)
            throw new feException(self::_("You must discard 1 Air energy"), true);
        if (reset($discarded) != -1)
            throw new feException(self::_("You must discard 1 Air energy"), true);
        if (key($discarded) != 1)
            throw new feException(self::_("You must discard 1 Air energy"), true);

        // This card => to the next player
        $card_id = self::getCurrentEffectCardId();
        $player_id = self::getActivePlayerId();

        $players = self::loadPlayersBasicInfos();
        $next_player_table = self::createNextPlayerTable(array_keys($players));
        $nextPlayer = $next_player_table[$player_id];

        $this->cards->moveCard($card_id, 'tableau', $nextPlayer);

        // Unactive this card
        $sql = "UPDATE card SET card_type_arg='0' WHERE card_id='$card_id' ";
        self::DbQuery($sql);

        $card = $this->cards->getCard($card_id);
        self::notifyAllPlayers('summon', '', array(
            'i18n' => array('card_name'),
            'player_id' => $nextPlayer,
            'card' => $card,
            'fromTableau' => $player_id
        ));

        self::notifyAllPlayers('discardFromTableau', clienttranslate('${player_name} pass ${card_name} to ${player_name2}'), array(
            'i18n' => array('card_name'),
            'card_name' => $this->card_types[218]['name'],
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'player_name2' => $players[$nextPlayer]['player_name']
        ));


        $this->gamestate->nextState('discardEnergy');
    }

    function io_s_transmuter_play($card_id, $card_name, $notifArgs) {
        // If die provide crystals => can transmute
        $notifArgs = self::getStandardArgs();
        $player_id = self::getActivePlayerId();
        $season = self::getCurrentDiceSeason();
        $playerDice = self::getObjectFromDB("SELECT dice_id, dice_face FROM dice
                                              WHERE dice_season='$season' AND dice_player_id='$player_id' ");

        $dice = $this->dices[$season][$playerDice['dice_id']][$playerDice['dice_face']];
        if ($dice['pts'] > 0) {
            if (self::getGameStateValue('transmutationPossible') == 0)
                self::setGameStateValue("transmutationPossible", 1);
            self::notifyAllPlayers('transmutationPossible', clienttranslate('${card_name}: ${player_name} can transmute this turn'), $notifArgs);
        }
    }

    function jewel_of_the_ancients_active($card_id, $card_name, $notifArgs) {
        // Check there are 3 different energy tokens
        $player_id = self::getActivePlayerId();
        $stock = self::getResourceStock($player_id);
        $amStock = self::getAmuletOfWaterStock($player_id);

        $bAtLeastOneOverThree = false;
        if (!self::isStaffWinterActive()) {
            foreach ($stock as $ress_id => $qt) {
                if (isset($amStock[$ress_id]))
                    $stock[$ress_id] += $amStock[$ress_id];

                if ($stock[$ress_id] >= 3)
                    $bAtLeastOneOverThree = true;
            }
        } else {
            $total = 0;
            foreach ($stock as $ress_id => $qt) {
                if (isset($amStock[$ress_id]))
                    $total += $amStock[$ress_id];

                $total += $qt;
            }

            if ($total >= 3)
                $bAtLeastOneOverThree = true;
        }

        if (!$bAtLeastOneOverThree)
            throw new feException(self::_("You don't have 3 identical energy tokens"), true);

        return "discardJewel";
    }
    function jewel_of_the_ancients_discardEnergy($discarded) {
        // Place on energy on the jewel

        if (!self::isStaffWinterActive()) {
            // Second edition of Balance of Ishtar
            // Check if energies discarded are identical
            if (count($discarded) != 1)
                throw new feException(self::_("You must discard 3 identical energies"), true);
            if (reset($discarded) != -3)
                throw new feException(self::_("You must discard 3 identical energies"), true);
        } else {
            // Just check there are 3 energies
            $total = 0;
            foreach ($discarded as $id => $qt) {
                $total += $qt;
            }
            if ($total != -3)
                throw new feException(self::_("You must discard 3 identical energies"), true);
        }


        $energy_id = key($discarded);

        $player_id = self::getActivePlayerId();

        // Allright! => place one on the Jewel
        $card_id = self::getCurrentEffectCardId();
        $energy_on_card = self::getCollectionFromDB("SELECT roc_id, roc_qt FROM resource_on_card WHERE roc_card='$card_id' ", true);

        if (isset($energy_on_card[$energy_id])) {
            self::DbQuery("UPDATE resource_on_card SET roc_qt=roc_qt+1
                            WHERE roc_card='$card_id' AND roc_id='$energy_id' ");
        } else {
            self::DbQuery("INSERT INTO resource_on_card (roc_card,roc_id,roc_qt,roc_player) VALUES
                            ('$card_id','$energy_id','1','$player_id') ");
        }

        $notifArgs = self::getStandardArgs();
        $notifArgs['energy'] = '<div class="sicon energy' . $energy_id . '"></div>';
        $notifArgs['energy_type'] = $energy_id;
        $notifArgs['card_id'] = $card_id;
        self::notifyAllPlayers('placeEnergyOnCard', clienttranslate('${player_name} places a ${energy} on ${card_name}'), $notifArgs);


        $this->gamestate->nextState("discardEnergy");
    }

    function kairn_the_destroyer_active($card_id, $card_name, $notifArgs) {
        // Discard 1 energy
        if (self::countPlayerEnergies(self::getActivePlayerId(), true) == 0)
            throw new feException(self::_("You don't have any energies"), true);

        return 'discardKairn';
    }
    function kairn_the_destroyer_discardEnergy($discarded) {
        // Check if 1 energy is discarded
        if (count($discarded) != 1)
            throw new feException(self::_("You must discard 1 energy"), true);
        if (reset($discarded) != -1)
            throw new feException(self::_("You must discard 1 energy"), true);

        // => each opponent looses 4 points
        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();
        $opponentIds = array();
        foreach ($players as $pid => $player) {
            if ($player_id != $pid)
                $opponentIds[] = $pid;
        }
        $sql = "UPDATE player SET player_score=GREATEST(0, player_score-4) WHERE player_id IN ('" . implode("','", $opponentIds) . "')";
        self::DbQuery($sql);
        foreach ($opponentIds as $opponentId) {
            self::notifyAllPlayers('winPoints', '', array('player_id' => $opponentId, 'points' => -4));
            self::notifyUpdateScores();
        }

        $notifArgs = self::getStandardArgs();
        self::notifyAllPlayers('kairn', clienttranslate('${card_name}: ${player_name} opponents lose 4 points'), $notifArgs);

        $this->gamestate->nextState('discardEnergy');
    }

    function lewis_greyface_play($card_id, $card_name, $notifArgs) {
        return "lewisChoice";
    }

    function lewis_greyface_choosePlayer($opponent_id) {
        $player_id = self::getActivePlayerId();

        if ($player_id == $opponent_id)
            throw new feException("You cannot choose yourself");

        // Copy all this player energies:

        // Get all energies from opponent
        $opponentStock = self::getResourceStock($opponent_id);
        self::applyResourceDelta($player_id, $opponentStock);

        $players = self::loadPlayersBasicInfos();
        $notifArgs = self::getStandardArgs();
        $notifArgs['player_name2'] = $players[$opponent_id]['player_name'];
        self::notifyAllPlayers('lewisAction', clienttranslate('${card_name}: ${player_name} gains all energy tokens that ${player_name2} has in reserve'), $notifArgs);

        $this->gamestate->nextState('choosePlayer');
    }

    function magma_core_onSummon() {
        $player_id = self::getActivePlayerId();

        // Receive 1 fire
        self::applyResourceDelta($player_id, array(3 => 1));

        $notifArgs = self::getStandardArgs();
        self::notifyAllPlayers('simpleNote', clienttranslate('${card_name}: ${player_name} gains 1 fire energy token'), $notifArgs);
    }


    function magma_core_active($card_id, $card_name, $notifArgs) {
        return 'potionSacrificeChoice';
    }
    function magma_core_useZira($card_id, $card_name, $notifArgs, $bUseZira) {
        $player_id = self::getActivePlayerId();

        if (!$bUseZira) {
            // ... sacrifice
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);

            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $card_id,

                'player_id' => $player_id
            ));
        }

        // Receive 3 fire
        self::applyResourceDelta($player_id, array(3 => 3));

        $notifArgs = self::getStandardArgs();
        self::notifyAllPlayers('simpleNote', clienttranslate('${card_name}: ${player_name} gains 3 fire energy token'), $notifArgs);
    }

    function mesodae_s_lantern_play($card_id, $card_name, $notifArgs) {
        // Reserve size => -1
        $player_id = self::getActivePlayerId();
        self::adaptReserveSize($player_id);
    }

    function mirror_of_the_seasons_active($card_id, $card_name, $notifArgs) {
        // Can't active if no crystal or energy
        $player_id = self::getActivePlayerId();
        $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
        if ($player_score == 0)
            throw new feException(self::_("You don't have enough crystals to do this action"), true);

        if (self::countPlayerEnergies($player_id, true) == 0) {
            throw new feException(self::_("You need at least one energy"), true);
        }

        return "discardMirror";
    }
    function mirror_of_the_seasons_chooseXenergy($energy_count) {
        if ($energy_count == 0)
            throw new feException(self::_("You must select at least one energy"), true);
        else {
            self::setGameStateValue('energyNbr', $energy_count);

            $player_id = self::getActivePlayerId();
            $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
            if ($player_score < $energy_count)
                throw new feException(self::_("You don't have enough crystals to do this action"), true);

            self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score-$energy_count ) WHERE player_id='$player_id' ");
            self::notifyAllPlayers('winPoints', '', array('player_id' => $player_id, 'points' => -$energy_count));
            self::notifyUpdateScores();

            $this->gamestate->nextState('chooseXenergy');
        }
    }
    function mirror_of_the_seasons_chooseEnergyType($energy_id) {
        // This energy => player stock
        $nbr = self::getGameStateValue('energyNbr');
        $player_id = self::getActivePlayerId();
        self::applyResourceDelta($player_id, array($energy_id => $nbr));
        $notifArgs = self::getStandardArgs();
        $notifArgs['energy'] = '<div class="sicon energy' . $energy_id . '"></div>';
        $notifArgs['nbr'] = $nbr;

        self::notifyAllPlayers('gainEnergy', clienttranslate('${card_name}: ${player_name} gets ${nbr} ${energy}'), $notifArgs);

        $this->gamestate->nextState('chooseEnergyType');
    }

    function naria_the_prophetess_play($card_id, $card_name, $notifArgs) {
        // Draw N cards to the choice pool (N=number of player)
        $players = self::loadPlayersBasicInfos();
        $player_id = self::getActivePlayerId();
        $cards = $this->cards->pickCardsForLocation(count($players), 'deck', 'choice', $player_id);
        self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));

        // First target = current player
        self::setGameStateValue('opponentTarget', $player_id);

        return 'nariaChoice';
    }

    function naria_the_prophetess_chooseCard($card_id, $card) {
        // Place this card in player's hand
        $players = self::loadPlayersBasicInfos();
        $player_id = self::getActivePlayerId();
        $target_id = self::getGameStateValue('opponentTarget');
        $this->cards->moveCard($card_id, 'hand', $target_id);
        self::incStat(1, 'cards_drawn', $target_id);
        self::notifyUpdateCardCount();

        $notifArgs = self::getStandardArgs();
        $notifArgs['player_id'] = $target_id;
        $notifArgs['player_name2'] = $players[$target_id]['player_name'];
        if ($target_id == $player_id)
            self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} choosed a power card'), $notifArgs);
        else
            self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} gives a power card to ${player_name2}'), $notifArgs);

        self::notifyPlayer($target_id, "pickPowerCard", '', array("card" => $card));
        self::notifyPlayer($player_id, "removeFromChoice", '', array("card" => $card_id));

        if ($this->cards->countCardsInLocation('choice', $player_id) == 0) {
            // This was the last card to take => end effect
            $this->gamestate->nextState("chooseCard");
            return;
        }

        // Next player...
        $next_player_table = self::createNextPlayerTable(array_keys($players));
        $nextPlayer = $next_player_table[$target_id];
        self::setGameStateValue('opponentTarget', $nextPlayer);
        $this->gamestate->nextState("nextPlayer");
    }

    function necrotic_kriss_active($card_id, $card_name, $notifArgs) {
        // Sacrifice a familiar

        // => check that there is at least a familiar in tableau OR in hand
        $player_id = self::getActivePlayerId();
        $familiar_item_nbr = self::countCardOfCategoryInTableau($player_id, 'f');
        $familiar_item_nbr += self::countCardOfCategoryInTableau($player_id, 'f', true);

        if ($familiar_item_nbr == 0)
            throw new feException(self::_("You don't have any familiar"), true);

        if (!self::checkPlayerCanSacrificeCard($player_id))
            throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);

        return "necroticSacrifice";
    }

    function necrotic_kriss_sacrifice($card_id, $card_type_id, $bUseZira) {
        if ($bUseZira) {
            // Check if player has at least one familiar in tableau
            $player_id = self::getActivePlayerId();
            $familiar_item_nbr = self::countCardOfCategoryInTableau($player_id, 'f');
            $familiar_item_nbr += self::countCardOfCategoryInTableau($player_id, 'f', true);

            if ($familiar_item_nbr == 0) {
                // There is no more familiar => necrotic kriss already sacrifice the familiar.
                // This is only possible when a Raven copying Zira is sacrified
                // => in this case work has been done
            }
        } else {
            // Check this is a familiar
            if ($this->card_types[$card_type_id]['category'] != 'f')
                throw new feException(self::_("You must choose a familiar"), true);
        }

        self::setGameStateValue('energyNbr', 4);
        $this->gamestate->nextState('sacrifice');
    }
    function necrotic_kriss_discard($card_id, $card_type_id) {
        // Check this is a familiar
        if ($this->card_types[$card_type_id]['category'] == 'f') {
            self::setGameStateValue('energyNbr', 4);
            $this->gamestate->nextState('discard');
        } else
            throw new feException(self::_("You must choose a familiar"), true);
    }

    function olaf_s_Blessed_Statue_play($card_id, $card_name, $notifArgs) {
        // + 20pts
        $player_id = self::getActivePlayerId();
        $points = self::checkMinion(20, $player_id);
        $sql = "UPDATE player SET player_score=player_score+$points
                WHERE player_id='$player_id' ";
        self::DbQuery($sql);
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);
    }

    function otus_the_oracle_play($card_id, $card_name, $notifArgs) {
        // Draw players_nbr cards to the choice pool
        $players = self::loadPlayersBasicInfos();
        $player_id = self::getActivePlayerId();
        $cards = $this->cards->pickCardsForLocation(count($players), 'deck', 'otus');
        $notifArgs['cards'] = $cards;
        $notifArgs['nbr'] = count($players);
        self::notifyAllPlayers('newOtusChoice', clienttranslate('${card_name}: ${nbr} cards are placed on the center of game area'), $notifArgs);
    }

    function orb_of_ragfield_play($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        // +20 crystals
        $points = self::checkMinion(20, $player_id);
        self::DbQuery("UPDATE player SET player_score=player_score+$points WHERE player_id='$player_id' ");
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} crystals'), $notifArgs);
    }


    function pendant_of_ragnor_play($card_id, $card_name, $notifArgs) {
        // 1 energy for each magic item in play
        $player_id = self::getActivePlayerId();

        $magic_item_nbr = self::countCardOfCategoryInTableau($player_id, 'mi');
        $magic_item_nbr--;  // Pendant of Ragnor does not count
        self::setGameStateValue('energyNbr', $magic_item_nbr);
        return "gainEnergy";
    }

    function potion_of_dreams_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();
        if (!self::checkPlayerCanSacrificeCard($player_id))
            throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);

        // Player must have at least 1 card in hand (that are not Mesodae’s Lantern = 106)

        $cards_in_hand = $this->cards->getCardsInLocation('hand', $player_id);
        $valid_cards = 0;
        foreach ($cards_in_hand as $card) {
            if (self::ot($card['type']) != 106)
                $valid_cards++;
        }

        // + cards from Otus the oracle         
        $cards_in_otus = $this->cards->getCardsInLocation('otus');
        foreach ($cards_in_otus as $card) {
            if (self::ot($card['type']) != 106)
                $valid_cards++;
        }

        if ($valid_cards == 0)
            throw new feException(self::_("You must have at least one card in your hand") . ' ' . self::_("(and not a Mesodae’s Lantern)"), true);


        return 'potionSacrificeChoice';
    }


    function potion_of_dreams_useZira($card_id, $card_name, $notifArgs, $bUseZira) {
        $player_id = self::getActivePlayerId();

        // Player must have at least 1 card in hand (that are not Mesodae’s Lantern = 106)

        $cards_in_hand = $this->cards->getCardsInLocation('hand', $player_id);
        $valid_cards = 0;
        foreach ($cards_in_hand as $card) {
            if (self::ot($card['type']) != 106)
                $valid_cards++;
        }

        // + cards from Otus the oracle         
        $cards_in_otus = $this->cards->getCardsInLocation('otus');
        foreach ($cards_in_otus as $card) {
            if (self::ot($card['type']) != 106)
                $valid_cards++;
        }


        if ($valid_cards == 0)
            throw new feException(self::_("You must have at least one card in your hand") . ' ' . self::_("(and not a Mesodae’s Lantern)"), true);

        if (!$bUseZira) {
            // Sacrifice the potion and all energy to summon a card for free
            // ... sacrifice
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);

            self::notifyAllPlayers('discardFromTableau', '', array(

                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }

        $playerStock = self::getResourceStock($player_id);
        $cost = array();
        foreach ($playerStock as $ress_id => $ress_qt) {
            $cost[$ress_id] = -$ress_qt;
        }
        self::applyResourceDelta($player_id, $cost);

        if (!self::checkSummoningGauge())
            throw new feException(self::_("You summoning gauge is not big enough to active this new card"), true);

        return 'potionDreamChoice';
    }
    function potion_of_dreams_chooseCardHand($card_id, $card) {
        // Summon it for free !
        $this->gamestate->nextState('chooseCardHand');
        self::summon($card_id, array(), true);
    }

    function potion_of_knowledge_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();
        if (!self::checkPlayerCanSacrificeCard($player_id))
            throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);

        return 'potionSacrificeChoice';
    }
    function potion_of_knowledge_useZira($card_id, $card_name, $notifArgs, $bUseZira) {
        $player_id = self::getActivePlayerId();

        // Sacrifice the potion to gain 5 energies

        if (!$bUseZira) {
            // ... sacrifice
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);

            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }

        // Gain energies
        self::setGameStateValue('energyNbr', 5);
        return "gainEnergy";
    }

    function potion_of_life_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();
        if (!self::checkPlayerCanSacrificeCard($player_id))
            throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);

        return 'potionSacrificeChoice';
    }
    function potion_of_life_useZira($card_id, $card_name, $notifArgs, $bUseZira) {
        $player_id = self::getActivePlayerId();

        if (!$bUseZira) {
            // ... sacrifice
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);

            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }

        // Sacrifice the potion to transmute all energies in reserve for 4 points each
        $energies = self::getResourceStock($player_id);
        $to_transmute = array();
        foreach ($energies as $energy_id => $nbr) {
            for ($i = 0; $i < $nbr; $i++) {
                $to_transmute[] = $energy_id;
            }
        }
        self::transmute($to_transmute, true);

        // forbid further cristalization
        // ROLLBACK ON THIS BUGFIX, read this: http://forum.boardgamearena.com/viewtopic.php?f=4&t=3255
        //self::setGameStateValue( "transmutationPossible", 0 );
        //self::notifyPlayer( $player_id, 'potionOfLifeWarning', '', array() );
    }

    function potion_of_power_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();
        if (!self::checkPlayerCanSacrificeCard($player_id))
            throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);

        return 'potionSacrificeChoice';
    }

    function potion_of_power_useZira($card_id, $card_name, $notifArgs, $bUseZira) {
        $player_id = self::getActivePlayerId();

        // Sacrifice the potion to get 1 power card and +2 invoc

        if (!$bUseZira) {
            // ... sacrifice
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);

            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }

        // Draw
        $card = $this->cards->pickCard('deck', $player_id);

        self::notifyUpdateCardCount();
        self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} draw a power card'), $notifArgs);
        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card));
        self::incStat(1, 'cards_drawn', $player_id);

        self::setGameStateValue('lastCardDrawn', $card['id']);
        self::mayUseEscaped();

        // +2 summoning gauge
        self::increaseSummoningGauge($player_id, $card_name, 2);
    }

    function potion_of_the_ancients_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();
        if (!self::checkPlayerCanSacrificeCard($player_id))
            throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);

        return 'potionSacrificeChoice';
    }

    function potion_of_the_ancients_useZira($card_id, $card_name, $notifArgs, $bUseZira, $zira_card_id) {
        $player_id = self::getActivePlayerId();

        if (!$bUseZira) {
            // ... sacrifice
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);

            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }

        // We are using the elementalamulet globals for the 4 choices
        self::setGameStateValue("elementalAmulet1", 1);
        self::setGameStateValue("elementalAmulet2", 1);
        self::setGameStateValue("elementalAmulet3", 1);
        self::setGameStateValue("elementalAmulet4", 1);

        return "potionOfAncientChoice";
    }

    function argPotionOfAncientChoice() {

        return array(
            'i18n' => array('card_name'),
            'card_name' => self::getCurrentEffectCardName(),
            'available' => array(
                1 => (self::getGameStateValue('elementalAmulet1')),
                2 => (self::getGameStateValue('elementalAmulet2')),
                3 => (self::getGameStateValue('elementalAmulet3')),
                4 => (self::getGameStateValue('elementalAmulet4'))
            )
        );
    }

    function stPotionOfAncientChoice() {
        if (self::getGameStateValue("elementalAmulet1") + self::getGameStateValue("elementalAmulet2") + self::getGameStateValue("elementalAmulet3") + self::getGameStateValue("elementalAmulet4") <= 2)
            $this->gamestate->nextState("stop");
    }

    function potion_of_the_ancients_dualChoice($choice_id) {
        $player_id = self::getActivePlayerId();

        if ($choice_id < 1 || $choice_id > 4)
            throw new feException("Wrong choice");

        if (self::getGameStateValue("elementalAmulet" . $choice_id) != 1)
            throw new feException("You already selected this choice");

        // Mark this choice as "selected"
        self::setGameStateValue("elementalAmulet" . $choice_id, 0);

        $nextState = 'continue';
        if (self::getGameStateValue("elementalAmulet1") + self::getGameStateValue("elementalAmulet2") + self::getGameStateValue("elementalAmulet3") + self::getGameStateValue("elementalAmulet4") <= 2)
            $nextState = 'stop';

        $notifArgs = self::getStandardArgs();

        // Now, apply the effect
        if ($choice_id == 1) {
            // Sacrifice the potion to transmute all energies in reserve for 4 points each
            $energies = self::getResourceStock($player_id);
            $to_transmute = array();
            foreach ($energies as $energy_id => $nbr) {
                for ($i = 0; $i < $nbr; $i++) {
                    $to_transmute[] = $energy_id;
                }
            }
            self::transmute($to_transmute, true);
        } else if ($choice_id == 2) {
            // Draw 2 cards to the choice pool
            $player_id = self::getActivePlayerId();
            $cards = $this->cards->pickCardsForLocation(2, 'deck', 'choice', $player_id);
            self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));
            $nextState = 'potionOfAncientCardChoice';
        } else if ($choice_id == 3) {
            // Increase summoning gauge by 2
            self::increaseSummoningGauge($player_id, $notifArgs['card_name'], 2);
        } else if ($choice_id == 4) {
            self::setGameStateValue('energyNbr', 4);
            $nextState = 'gainEnergy';
        }

        $this->gamestate->nextState($nextState);
    }

    function potion_of_the_ancients_chooseCard($card_id, $card) {
        // Place this card in player's hand
        $player_id = self::getActivePlayerId();
        $this->cards->moveCard($card_id, 'hand', $player_id);
        self::incStat(1, 'cards_drawn', $player_id);
        self::notifyUpdateCardCount();

        $notifArgs = self::getStandardArgs();
        self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} choosed a power card'), $notifArgs);
        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

        // Discard all choice
        $this->cards->moveAllCardsInLocation('choice', 'discard', $player_id, self::incGameStateValue('discardPos', 1));

        $this->gamestate->nextState("chooseCard");
    }


    function potion_of_resurrection_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();
        if (!self::checkPlayerCanSacrificeCard($player_id))
            throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);

        return 'potionSacrificeChoice';
    }

    function potion_of_resurrection_useZira($card_id, $card_name, $notifArgs, $bUseZira, $zira_card_id) {
        // Draw 5 last cards discarded => to the choice pool
        $player_id = self::getActivePlayerId();

        if ($bUseZira) {
            // Must place zira away from discard during the discard draw...
            $this->cards->moveCard($zira_card_id, 'zira_aside');
        }

        $cards = $this->cards->pickCardsForLocation(5, 'discard', 'choice', $player_id, true);

        if ($bUseZira) {
            // Replace zira in the discard draw...
            $this->cards->moveCard($zira_card_id,  'discard', self::incGameStateValue('discardPos', 1));
        }


        if (!$bUseZira) {
            // ... sacrifice
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);

            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $card_id,
                'player_id' => $player_id
            ));
        }

        if (count($cards) == 0) {
            $notifArgs = self::getStandardArgs();
            self::notifyAllPlayers('noCardsInDiscard', clienttranslate('${card_name}: there is no discarded cards'), $notifArgs);
        } else {
            self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));
            return 'resurrectionChoice';
        }
    }
    function potion_of_resurrection_chooseCard($card_id, $card) {
        // Place this card in player's hand
        $player_id = self::getActivePlayerId();
        $this->cards->moveCard($card_id, 'hand', $player_id);
        self::incStat(1, 'cards_drawn', $player_id);
        self::notifyUpdateCardCount();

        $notifArgs = self::getStandardArgs();
        self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} choosed a power card'), $notifArgs);
        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

        // Discard all choice on -1 location (bottom of the discard pile)
        $this->cards->moveAllCardsInLocation('choice', 'discard', $player_id, -1);

        $this->gamestate->nextState('chooseCard');
    }

    function ratty_nightshade_play($card_id, $card_name, $notifArgs) {
        return "rattyNightshade";
    }

    function ratty_nightshade_collectEnergy($players_to_energies) {
        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();

        $stock = self::getResourceStock();

        foreach ($players as $opponent_id => $opponent) {
            if ($opponent_id != $player_id) {
                if (isset($stock[$opponent_id])) {
                    // This opponent has some energies => count them
                    $nbr_available = 0;
                    foreach ($stock[$opponent_id] as $type => $qt) {
                        $nbr_available += $qt;
                    }

                    $nbr_to_take = min(2, $nbr_available);

                    // Count energies taken
                    $nbr_taken = 0;
                    $gain = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
                    if (isset($players_to_energies[$opponent_id])) {
                        foreach ($players_to_energies[$opponent_id] as $type => $qt) {
                            $nbr_taken += $qt;
                            $gain[$type] = abs($qt);
                        }
                    }

                    if ($nbr_to_take != abs($nbr_taken)) {
                        throw new feException(sprintf(self::_("You must take %s energies to %s"), $nbr_to_take, $opponent['player_name']), true);
                    }

                    // Okay, apply the cost
                    self::applyResourceDelta($opponent_id, $players_to_energies[$opponent_id]);
                    self::applyResourceDelta($player_id, $gain);
                }
            }
        }

        $this->gamestate->nextState('rattyNightshade');
    }

    function raven_the_usurper_play($card_id, $card_name, $notifArgs) {
        // Is there a magical item on opponents hands
        $player_id = self::getActivePlayerId();
        $all_cards = $this->cards->getCardsInLocation('tableau');

        foreach ($all_cards as $card) {
            if ($card['location_arg'] != $player_id) {
                if ($this->card_types[self::ot($card['type'])]['category'] == 'mi')
                    return 'ravenChoice';
            }
        }

        self::notifyAllPlayers('noMagicalItems', clienttranslate('${card_name}: there is no Magical item to mimic'), $notifArgs);
    }


    function raven_the_usurper_chooseOpponentCard($card_id, $card_type_id, $amuletEnergies) {
        $raven_id = self::getCurrentEffectCardId();
        $player_id = self::getActivePlayerId();

        $card_types = self::getCardTypes();
        $card_type = $card_types[$card_type_id];
        $card = $this->cards->getCard($card_id);

        if (self::ot($card['type']) == 118)
            throw new feException(self::_("You cannot copy Raven"), true);

        // Check this is a magical item
        if ($card_type['category'] != 'mi')
            throw new feException(self::_("You must choose a magical item"), true);

        // Consume invocation cost (see 'playcard')
        $cost = array();
        $point_cost = 0;
        $total_ress = 0;
        foreach ($card_type['cost'] as $ress_id => $ress_qt) {
            if ($ress_id != 0) {
                $cost[$ress_id] = -$ress_qt;
                $total_ress += $ress_qt;
            }
        }
        foreach ($card_type['cost'] as $ress_id => $ress_qt) {
            if ($ress_id == 0)
                $point_cost += $ress_qt;
        }

        $cost_displayed = '';
        foreach ($cost as $ress_id => $ress_qt) {
            for ($i = 0; $i > $ress_qt; $i--) {
                $cost_displayed .= '<div class="sicon energy' . $ress_id . '"></div>';
            }
        }

        if ($point_cost > 0) {
            $cost_displayed .= '<div class="sicon energy0"></div>x' . $point_cost;
        }

        if ($amuletEnergies !== null) {
            // Force the use of some energies from Amulet of Water
            // => reduce the cost of the card to summon
            self::applyAmuletOfWaterEnergyCost($amuletEnergies);

            foreach ($amuletEnergies as $energy) {
                if ($energy > 10) {
                    $energy = $energy % 10;
                    if (!isset($cost[$energy]))
                        throw new feException(self::_("No need to use following resource: ") . self::_($this->energies[$energy]['name']), true);
                    if ($cost[$energy] == 0)
                        throw new feException(self::_("No need to use following resource: ") . self::_($this->energies[$energy]['name']), true);
                    $cost[$energy]++;
                }
            }
        }
        self::applyResourceDelta($player_id, $cost);

        // Cost in points
        $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
        if ($point_cost > 0) {
            if ($player_score < $point_cost)
                throw new feException(self::_("You don't have enough crystals to summon this card"), true);

            self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score-$point_cost ) WHERE player_id='$player_id'");
            $player_score -= $point_cost;
            self::notifyAllPlayers('winPoints', '', array('player_id' => $player_id, 'points' => -$point_cost));
            self::notifyUpdateScores();
        }

        // Link between raven and the item
        self::DbQuery("INSERT INTO raven (raven_id,raven_original_item) VALUES ('$raven_id','$card_id') ");


        // Change the type of Raven into the new card type
        self::DbQuery("UPDATE card SET card_type='118;$card_type_id' WHERE card_id='$raven_id' ");

        $notifArgs = self::getStandardArgs();

        $notifArgs['i18n'][] = 'mi_name';
        $notifArgs['mi_name'] = $this->card_types[$card_type_id]['name'];
        $notifArgs['cost'] = $cost_displayed;
        $notifArgs['card_id'] = $raven_id;
        $notifArgs['card_type'] = '118;' . $card_type_id;
        self::notifyAllPlayers('ravenCopy', clienttranslate('${card_name}: ${player_name} chooses to copy ${mi_name} for ${cost}'), $notifArgs);

        // trigger "play" effect
        $this->gamestate->nextState("chooseOpponentCard");

        self::insertEffect($raven_id, 'play');
        self::applyCardsEffect('playerTurn', $player_id);
    }

    function raven_the_usurper_sacrifice() {
        // Note: copied on dragon_skull_sacrifice to manage the case where Raven is sacrified as first card
        // See bug: http://fr.boardgamearena.com/#!bug?id=56

        $this->gamestate->nextState('sacrifice');
    }


    function scepter_of_greatness_play($card_id, $card_name, $notifArgs) {
        // +3pts per magical items in play
        $player_id = self::getActivePlayerId();

        $magic_item_nbr = self::countCardOfCategoryInTableau($player_id, 'mi');

        // This scepter does not count (but only if it's really a Scepter, not a Raven)
        // See https://boardgamearena.com/bug?id=501
        $card = $this->cards->getCard($card_id);
        if (self::ot($card['type']) == 28) {
            $magic_item_nbr--;
        }

        $points = self::checkMinion(3 * $magic_item_nbr, $player_id);
        $sql = "UPDATE player SET player_score=player_score+$points
                WHERE player_id='$player_id' ";
        self::DbQuery($sql);
        $notifArgs = self::getStandardArgs();
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);
    }

    function selenia_s_codex_play($card_id, $card_name, $notifArgs) {
        // Check if there is an item allowed to take back
        $player_id = self::getActivePlayerId();
        $cards = self::getAllCardsOfTypeInTableau(null, $player_id);
        foreach ($cards as $card_type_id => $card_id) {
            if (self::selenia_s_codex_canTakeBack($card_type_id)) {
                // We found at least 1 item which can be taken back
                return "seleniaCodex";
            }
        }

        // No card to take back
        return;
    }

    // Return true if it's allowed to take back this card with Selenia's Codex
    function selenia_s_codex_canTakeBack($card_type_id) {
        // Check this is a magical item
        $card_types = self::getCardTypes();
        if ($card_types[$card_type_id]['category'] != 'mi')
            return false;

        // Check this is not a Codex
        if ($card_type_id == 104)
            return false;

        // Check this card has a cost
        $bHasEnergyCost = false;
        foreach ($card_types[$card_type_id]['cost'] as $id => $qt) {
            if ($id != 0)
                return true;
        }

        // Exception: elemental amulet DOES have some energy cost
        if ($card_type_id == 31)
            return true;

        return false;
    }

    function selenia_s_codex_takeBack($card_id, $card_type_id) {
        if (!self::selenia_s_codex_canTakeBack($card_type_id)) {
            // Can't take this card back => figure out why
            $card_types = self::getCardTypes();
            if ($card_types[$card_type_id]['category'] != 'mi')
                throw new feException(self::_("You must choose a magical item"), true);

            // Check this is not a Codex
            if ($card_type_id == 104)
                throw new feException(self::_("You can't choose the Selenia’s Codex itself"), true);

            // Don't need to check this, this is the only remaining reason
            throw new feException(self::_("You must choose a Magical item with some energy cost"), true);
        }

        $this->gamestate->nextState("takeback");
    }

    function sepulchral_amulet_play($card_id, $card_name, $notifArgs) {
        // Draw 2 cards to the choice pool
        $player_id = self::getActivePlayerId();
        $cards = $this->cards->pickCardsForLocation(3, 'discard', 'choice', $player_id);
        if (count($cards) == 0) {
            $notifArgs = self::getStandardArgs();
            self::notifyAllPlayers("noCardInDiscard", clienttranslate('${card_name}: there is no cards in discard pile'), $notifArgs);
        } else {
            self::setGameStateValue("elementalAmulet3", 1);   // Hack: using elementalAmulet3 to know in which step of Sepulchral Amulet we are

            self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));
            return 'sepuchralAmuletCardChoice';
        }
    }


    function sepulchral_amulet_chooseCard($card_id, $card) {
        $player_id = self::getActivePlayerId();

        if (self::getGameStateValue("elementalAmulet3") == 1) {
            // First step !
            // Place this card in player's hand
            $this->cards->moveCard($card_id, 'hand', $player_id);
            self::incStat(1, 'cards_drawn', $player_id);
            self::notifyUpdateCardCount();

            $notifArgs = self::getStandardArgs();
            self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} choosed a power card'), $notifArgs);
            self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

            if ($this->cards->countCardInLocation('choice') == 0) {
                self::notifyAllPlayers("noCardInDiscard", clienttranslate('${card_name}: there is no more cards in discard pile'), $notifArgs);
                $this->gamestate->nextState('moMoreCard');
                return;
            } else {
                self::setGameStateValue("elementalAmulet3", 2);
            }
        } else if (self::getGameStateValue("elementalAmulet3") == 2) {
            // Second step
            // Place this card on top of the pile

            $this->cards->insertCardOnExtremePosition($card_id, 'deck', true);
            self::notifyUpdateCardCount();

            // Send a message to all players and send the removeFromChoice notification
            // (without message) to the active player
            $notifArgs = self::getStandardArgs();
            self::notifyAllPlayers("simpleNote", clienttranslate('${player_name} replace a card on top of the draw pile'), $notifArgs);
            $notifArgs['card'] = $card_id;
            self::notifyPlayer($player_id, "removeFromChoice", "", $notifArgs);

            // Then add the other one at the bottom of the draw pile
            $cards = $this->cards->getCardsInLocation('choice');
            if (count($cards) == 1) {
                $bottomcard = reset($cards);
                $this->cards->insertCardOnExtremePosition($bottomcard['id'], 'deck', false);
                self::notifyUpdateCardCount();

                // Send a message to all players and send the removeFromChoice notification
                // (without message) to the active player
                $notifArgs = self::getStandardArgs();
                self::notifyAllPlayers("simpleNote", clienttranslate('${player_name} replace a card on bottom of the draw pile'), $notifArgs);
                $notifArgs['card'] = $card_id;
                self::notifyPlayer($player_id, "removeFromChoice", "", $notifArgs);
            } else {
                self::notifyAllPlayers("noCardInDiscard", clienttranslate('${card_name}: there is no more cards in discard pile'), $notifArgs);
                $this->gamestate->nextState('moMoreCard');
                return;
            }
        }

        $this->gamestate->nextState('chooseCard');
    }

    function servant_of_ragfield_play($card_id, $card_name, $notifArgs) {
        $bContinue = true;
        $servant_owner = self::getCurrentEffectCardOwner();
        $players = self::loadPlayersBasicInfos();

        $player_id = $servant_owner;

        while ($bContinue) {
            if (self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id'") >= 10) {
                // Can draw some card
                self::doDrawPowerCard();

                // +1 summoning gauge
                self::increaseSummoningGauge($player_id, $card_name, 1);

                return 'do_not_nextState';
            }

            $player_id = self::activeNextPlayer();

            if ($player_id == $servant_owner) {
                // No one can apply the effect => no effect
                return;
            }
        }
    }

    function stServantNext() {
        $servant_owner = self::getCurrentEffectCardOwner();

        $player_id = self::activeNextPlayer();

        if ($player_id == $servant_owner) {
            // Back to servant owner
            $this->gamestate->nextState('end');
            return;
        }

        $transition = ($player_id == $servant_owner) ? "end" : "next";

        if (self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id'") >= 10) {
            // Can draw some card
            self::doDrawPowerCard();

            $players = self::loadPlayersBasicInfos();

            // +1 summoning gauge
            $card_name = $this->card_types[216]['name'];
            self::increaseSummoningGauge($player_id, $card_name, 1);

            $transition = 'draw';
        }

        $this->gamestate->nextState($transition);
    }

    function scroll_of_ishtar_play() {
        return 'scrollIshtar';
    }

    function scroll_of_ishtar_chooseEnergyType($energy_id) {
        $notifArgs = self::getStandardArgs();
        $player_id = self::getActivePlayerId();

        self::setGameStateValue('elementalAmulet1', $energy_id);  // Hack: we use elementalAmulet1 to store energy type

        $card_types = self::getCardTypes();

        // See first card on the drawpile
        for ($i = 1; $i < 200; $i++)  // To avoid infinite loop
        {
            $card = $this->cards->pickCardForLocation('deck', 'choice', $player_id);


            if (
                isset($card_types[self::ot($card['type'])]['cost'][$energy_id])
                && $card_types[self::ot($card['type'])]['category'] == 'mi'
            ) {
                // We found our card !
                self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => array($card)));
                $this->gamestate->nextState("scrollIshtarCardChoice");
                return;
            } else {
                // Other card => discard it
                $notifArgs['i18n'][] = 'reveal';
                $notifArgs['reveal'] = $card_types[self::ot($card['type'])]['name'];
                self::notifyAllPlayers('revealCard', clienttranslate('${card_name}: ${player_name} reveals ${reveal}'), $notifArgs);
                $this->cards->moveCard($card['id'], 'discard', self::incGameStateValue('discardPos', 1));
            }
        }
    }

    function scroll_of_ishtar_dualChoice($choice) {
        $player_id = self::getActivePlayerId();
        $notifArgs = self::getStandardArgs();

        $energy_id = self::getGameStateValue("elementalAmulet1"); // Hack: we use elementalAmulet1 to store energy type

        $cards = $this->cards->getCardsInLocation('choice', $player_id);
        $card = reset($cards);
        $card_id = $card['id'];

        $card_types = self::getCardTypes();

        if ($choice == 0) {
            // Add card to my hand
            $this->cards->moveCard($card_id, 'hand', $player_id);
            self::incStat(1, 'cards_drawn', $player_id);
            self::notifyUpdateCardCount();

            self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => true));

            $notifArgs['i18n'][] = 'card_name2';
            $notifArgs['card_name2'] = $card_types[self::ot($card['type'])]['name'];
            self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} places ${card_name2} in his hand'), $notifArgs);


            $this->gamestate->nextState('familiarAddToHand');
        } else {
            // Discard the card
            $this->cards->moveCard($card_id, 'discad');

            // Get the next familiar (see "familiar_catcher_play" above)
            for ($i = 1; $i < 200; $i++)  // To avoid infinite loop
            {
                $card = $this->cards->pickCardForLocation('deck', 'choice', $player_id);
                if (
                    isset($card_types[self::ot($card['type'])]['cost'][$energy_id])
                    && $card_types[self::ot($card['type'])]['category'] == 'mi'
                ) {
                    // We found our card !
                    // => add to hand
                    $card_id = $card['id'];
                    $this->cards->moveCard($card_id, 'hand', $player_id);
                    self::incStat(1, 'cards_drawn', $player_id);
                    self::notifyUpdateCardCount();

                    self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => false));

                    $notifArgs['i18n'][] = 'card_name2';
                    $notifArgs['card_name2'] = $card_types[self::ot($card['type'])]['name'];
                    self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} places ${card_name2} in his hand'), $notifArgs);

                    $this->gamestate->nextState('familiarAddToHand');
                    return;
                } else {
                    // Magical item => discard it
                    $notifArgs['i18n'][] = 'reveal';
                    $notifArgs['reveal'] = $card_types[self::ot($card['type'])]['name'];
                    self::notifyAllPlayers('revealCard', clienttranslate('${card_name}: ${player_name} reveals ${reveal}'), $notifArgs);
                    $this->cards->moveCard($card['id'], 'discard', self::incGameStateValue('discardPos', 1));
                }
            }

            // Note: not supposed to happened
            $this->gamestate->nextState('familiarDiscard');
        }
    }

    function sid_nightshade_play() {
        $player_id = self::getActivePlayerId();
        $player_to_score = self::getCollectionFromDB("SELECT player_id, player_score FROM player", true);

        $maximum_players = getKeysWithMaximum($player_to_score);
        if (count($maximum_players) == 1 && reset($maximum_players) == $player_id) {
            // Player has the biggest number of crystals => steal 5 to each opponent

            $points_win = 0;
            foreach ($player_to_score as $opponent_id => $opponent_score) {
                if ($opponent_id != $player_id && $opponent_score > 0) {
                    self::DbQuery("UPDATE player SET player_score=GREATEST(0, player_score-5) WHERE player_id='$opponent_id' ");
                    self::notifyAllPlayers('winPoints', '', array('player_id' => $opponent_id, 'points' => -5));
                    self::notifyUpdateScores();
                    $points_win += min(5, $opponent_score);
                }
            }

            if ($points_win > 0) {
                $points_win = self::checkMinion($points_win, $player_id);
                self::DbQuery("UPDATE player SET player_score=player_score+$points_win WHERE player_id='$player_id' ");
                $notifArgs = self::getStandardArgs();
                $notifArgs['points'] = $points_win;

                self::notifyAllPlayers('winPoints', clienttranslate('${card_name}: ${player_name} steals ${points} points.'), $notifArgs);
                self::notifyUpdateScores();
            }
        }
    }

    function speedwall_the_escaped_onDrawOne($card_id, $card_name, $notifArgs) {
        return "escaped_choice";
    }
    function speedwall_the_escaped_dualChoice($choice) {
        $player_id = self::getActivePlayerId();
        $notifArgs = self::getStandardArgs();

        if ($choice == 0) {
            // Do not activate
        } else {
            // Take the card from the hand of the other player

            $card_to_steal_id = self::getGameStateValue('lastCardDrawn');

            $card = $this->cards->getCard($card_to_steal_id);

            if ($card['location'] != 'hand')
                throw new feException(self::_("The card to steal is no more in the opponent hand, so you cannot steal it."), true);

            $escaped_card = self::getCurrentEffectCard();
            $escaped_card = $this->cards->getCard($escaped_card['card_id']);

            $this->cards->moveCard($card['id'], 'hand', $player_id);
            $this->cards->moveCard($escaped_card['id'], 'hand', $card['location_arg']);

            // Notifications :

            // ... discarded from hand of the other player
            self::notifyAllPlayers('discard', '', array(
                'card_id' => $card['id'],
                'player_id' => $card['location_arg']
            ));

            // ... added to hand of speedy owner
            self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card, "fromChoice" => false));

            // Discard speedy from tableau
            self::notifyAllPlayers('discardFromTableau', '', array(
                'card_id' => $escaped_card['id'],
                'player_id' => $player_id
            ));


            // And speedy add to the other player hand
            self::notifyPlayer($card['location_arg'], "pickPowerCard", '', array("card" => $escaped_card, "fromChoice" => false));

            // Then notify
            self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} uses ${card_name} to steal the card just picked.'), $notifArgs);

            // Remove all remaining escaped effects as we took this game
            self::setGameStateValue('lastCardDrawn', 0);
            $effect_id = self::getGameStateValue('currentEffect');
            self::DbQuery("DELETE FROM effect WHERE effect_id!='$effect_id' AND effect_type='onDrawOne' ");
        }

        $this->gamestate->nextState('dualChoice');
    }

    function staff_of_winter_active($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        $zira = self::getAllCardsOfTypeInTableau(array(
            113 // Zira's shield
        ), $player_id);

        if (isset($zira[113])) {
            // Okay !
            return "staffWinterDiscard";
        }

        // If no Zira, there must be a magical item in hand
        $cards = $this->cards->getCardsInLocation('hand', $player_id);
        $bMagicalItemInHand = false;

        foreach ($cards as $card) {
            if ($this->card_types[$card['type']]['category'] == 'mi')
                $bMagicalItemInHand = true;
        }

        if ($bMagicalItemInHand) {
            return "staffWinterDiscard";
        } else
            throw new feException(self::_("You do not have any magical item in your hand"), true);
    }

    function staff_of_winter_discard($card_id, $card_type_id) {
        $player_id = self::getActivePlayerId();

        $card_type = $this->card_types[self::ct($card_type_id)];

        if ($card_type['category'] != 'mi')
            throw new feException(self::_("You must choose a magical item"));

        // +3 energy tokens
        self::setGameStateValue('energyNbr', 3);

        $this->gamestate->nextState('gainEnergy');
    }

    function staff_of_winter_useZira($card_id, $card_name, $notifArgs, $bUseZira, $zira_card_id) {
        if (!$bUseZira)
            throw new feException("You must use Zira");

        // => equivalent to a discard
        self::staff_of_winter_discard($card_id, 113);

        return 'do_not_nextState';
    }

    function statue_of_eolis_play($card_id, $card_name, $notifArgs) {
        // Reserve size => -1
        $player_id = self::getActivePlayerId();
        self::adaptReserveSize($player_id);
    }

    function statue_of_eolis_onSeasonChange($card_id, $card_name, $notifArgs) {
        self::setGameStateValue('energyNbr', 1);
        return "statueOfEolisChoice";
    }



    function statue_of_eolis_dualChoice($choice) {
        $player_id = self::getActivePlayerId();
        $notifArgs = self::getStandardArgs();

        $cards = $this->cards->getCardsInLocation('choice', $player_id);
        $card = reset($cards);
        $card_id = $card['id'];

        // Replace card in the top of the deck
        $this->cards->insertCardOnExtremePosition($card_id, 'deck', true);

        self::notifyAllPlayers('replaceOrb', clienttranslate('${card_name}: ${player_name} replaces the card on the top of the deck'), $notifArgs);
        $this->gamestate->nextState('end');
    }

    function steadfast_die_active($card_id, $card_name, $notifArgs) {
    }


    function syllas_the_faithful_play($card_id, $card_name, $notifArgs) {
        // Each opponent sacrify a card
        $bContinue = true;
        $syllas_owner = self::getCurrentEffectCardOwner();
        $players = self::loadPlayersBasicInfos();

        while ($bContinue) {
            $player_id = self::activeNextPlayer();
            if ($player_id == $syllas_owner) {
                // No one has a power card => no effect
                return;
            } else {
                if ($this->cards->countCardInLocation('tableau', $player_id) > 0 && self::checkPlayerCanSacrificeCard($player_id))
                    return "syllasSacrifice";   // here's are some cards to sacrifice !
                else {
                    $notifArgs['player_name'] = $players[$player_id]['player_name'];
                    self::notifyAllPlayers('simpleNote', clienttranslate('${card_name}: ${player_name} cannot sacrifice any card'), $notifArgs);
                }
            }
        }
    }
    function syllas_the_faithful_sacrifice() {
        // Go to next opponent
        $bContinue = true;
        $syllas_owner = self::getCurrentEffectCardOwner();

        $this->gamestate->nextState('nextPlayer');

        while ($bContinue) {
            $player_id = self::activeNextPlayer();
            if ($player_id == $syllas_owner) {
                // this is the end
                $this->gamestate->nextState('end');
                return;
            } else {
                if ($this->cards->countCardInLocation('tableau', $player_id) > 0 && self::checkPlayerCanSacrificeCard($player_id)) {
                    $this->gamestate->nextState('continue');  // here's are some cards to sacrifice !
                    return;
                }
            }
        }

        throw new feException("Can't find next player for Syllas");
    }

    function staff_of_spring_onSummon() {
        // +3 pts
        $player_id = self::getActivePlayerId();
        $points = self::checkMinion(3, $player_id);
        $sql = "UPDATE player SET player_score=player_score+$points
                WHERE player_id='$player_id' ";
        self::DbQuery($sql);
        $notifArgs = self::getStandardArgs();
        $notifArgs['points'] = $points;
        self::notifyAllPlayers("score", clienttranslate('${card_name}: ${player_name} gets ${points} points'), $notifArgs);
    }

    function temporal_boots_play($card_id, $card_name, $notifArgs) {
        return "temporalBoots";
    }

    function throne_of_renewal_play($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();

        if ($this->cards->countCardInLocation('hand', $player_id) == 0) {
            // No card in hand. Is there at least Zira?

            $zira = self::getAllCardsOfTypeInTableau(array(
                113 // Zira's shield
            ), $player_id);

            if (isset($zira[113])) {
                // Okay !
                return "throneDiscard";
            }

            // ... Do nothing
        } else
            return "throneDiscard";
    }

    function throne_of_renewal_discard($card_id, $card_type_id) {
        $player_id = self::getActivePlayerId();

        // Draw
        $card = $this->cards->pickCard('deck', $player_id);

        $notifArgs = self::getStandardArgs();

        self::notifyUpdateCardCount();
        self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} draw a power card'), $notifArgs);
        self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card));
        self::incStat(1, 'cards_drawn', $player_id);

        self::setGameStateValue('lastCardDrawn', $card['id']);
        self::mayUseEscaped();


        // Move sorcerer token back
        $nb_used = self::getUniqueValueFromDB("SELECT player_nb_bonus_used FROM player WHERE player_id='$player_id' ");
        if ($nb_used > 0) {
            // Okay, decrease bonus usage
            $this->decreaseBonusUsage($player_id, $nb_used, $notifArgs);
        }

        $this->gamestate->nextState("discard");
    }

    function decreaseBonusUsage($player_id, $nb_used, $notifArgs) {
        self::DbQuery("UPDATE player SET player_nb_bonus_used=player_nb_bonus_used-1 WHERE player_id='$player_id' ");
        $notifArgs['bonus_used'] = $nb_used - 1;
        self::notifyAllPlayers("bonusBack", clienttranslate('${card_name}: ${player_name} move his Sorcerer token back one space on the bonus track.'), $notifArgs);
    }

    function throne_of_renewal_useZira($card_id, $card_name, $notifArgs, $bUseZira, $zira_card_id) {
        if (!$bUseZira)
            throw new feException("You must use Zira");

        // => equivalent to a discard
        self::throne_of_renewal_discard($card_id, 113);

        return 'do_not_nextState';
    }


    function titus_deepgaze_onEndTurn($card_id, $card_name, $notifArgs) {
        $player_id = self::getActivePlayerId();
        if (!self::checkPlayerCanSacrificeCard($player_id)) {
            // Cannot sacrifice Titus !
            return null;
        }

        return "potionSacrificeChoice";
    }
    function titus_deepgaze_useZira($card_id, $card_name, $notifArgs, $bUseZira) {
        if (!$bUseZira) {
            $player_id = self::getActivePlayerId();

            // We must sacrifice Titus...
            $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
            self::cleanTableauCard($card_id, $player_id);
            self::notifyUpdateCardCount();

            self::notifyAllPlayers('discardFromTableau', clienttranslate('${card_name}: ${player_name} sacrifices ${sacrified}'), array(
                'i18n' => array('card_name', 'sacrified'),
                'card_name' => $this->card_types['39']['name'],
                'card_id' => $card_id,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'sacrified' => $this->card_types['39']['name']
            ));
        }
    }

    function tree_of_light_active($card_id, $card_name, $notifArgs) {
        // Player should have 1 energy token or 3 crystals
        $player_id = self::getActivePlayerId();
        $energies = self::countPlayerEnergies($player_id, true);
        if ($energies == 0) {
            $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
            if ($player_score < 3)
                throw new feException(self::_("You don't have enough energy token or crystals to do this action"), true);
        }

        return "treeOfLifeChoice";
    }

    function tree_of_light_dualChoice($choice_id) {
        $player_id = self::getActivePlayerId();

        if ($choice_id == 0) {
            // Discard 3 crystals and gain 1 energy token
            $player_score = self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id' ");
            if ($player_score < 3)
                throw new feException(self::_("You don't have enough energy token or crystals to do this action"), true);

            self::DbQuery("UPDATE player SET player_score=GREATEST( 0, player_score-3 ) WHERE player_id='$player_id' ");
            self::notifyAllPlayers('winPoints', '', array('player_id' => $player_id, 'points' => -3));
            self::notifyUpdateScores();
            self::setGameStateValue('energyNbr', 1);
            $this->gamestate->nextState('gainEnergy');
        } else {
            // Discard 1 energy token and you can transmute this round
            $energies = self::countPlayerEnergies($player_id, true);
            if ($energies == 0)
                throw new feException(self::_("You don't have enough energy token or crystals to do this action"), true);

            $this->gamestate->nextState('discardEnergy');
        }
    }

    function tree_of_light_discardEnergy($energies) {
        $sum = 0;
        foreach ($energies as $type => $nbr) {
            $sum += abs($nbr);
        }

        if ($sum != 1)
            throw new feException(sprintf(self::_("You must discard exactly %s energy tokens"), 1), true);

        if (self::getGameStateValue('transmutationPossible') == 0)
            self::setGameStateValue("transmutationPossible", 1);
        $this->gamestate->nextState('discardEnergy');
    }

    function urmian_psychic_cage_onSummon() {
        // Make sure the card summoner is the active player
        $this->gamestate->changeActivePlayer(self::getGameStateValue('afterEffectPlayer'));

        return "urmianChoice";
    }

    function urmian_psychic_cage_dualChoice($choice) {
        $player_id = self::getActivePlayerId();

        if ($choice == 0) {   // Discard the last summoned card

            // Get card & effect that is going to be applied
            $card_summoned = self::getObjectFromDB("SELECT effect_id, effect_card, effect_card_type
                FROM effect WHERE effect_type='play' ");


            if ($card_summoned !== null) {
                // Discard the card that has been summoned

                $card_id = $card_summoned['effect_card'];

                // We must sacrifice Titus...
                $this->cards->moveCard($card_id, 'discard', self::incGameStateValue('discardPos', 1));
                self::cleanTableauCard($card_id, $player_id, false);
                self::notifyUpdateCardCount();

                self::notifyAllPlayers('discardFromTableau', clienttranslate('${card_name}: ${player_name} discards ${discarded} and do not apply any effect'), array(
                    'i18n' => array('card_name', 'discarded'),
                    'card_name' => $this->card_types['215']['name'],
                    'card_id' => $card_id,
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'discarded' => $this->card_types[$card_summoned['effect_card_type']]['name']
                ));

                // Remove its effects
                self::DbQuery("DELETE FROM effect WHERE effect_id='" . $card_summoned['effect_id'] . "'");
            }
        } else {   // Must sacrifice a power card

            if (!self::checkPlayerCanSacrificeCard($player_id))
                throw new feException(self::_("You do not have enough crystal to pay Crystal Titan"), true);
        }

        // Remove the trap token on urmian
        $cage_id = self::getCurrentEffectCardId();
        $cage = $this->cards->getCard($cage_id);
        self::DbQuery("UPDATE card SET card_type_arg='1' WHERE card_id='$cage_id'");
        self::notifyAllPlayers('active', '', array(
            'card' => $cage,
            'player_id' => $cage['location_arg']
        ));

        if ($choice == 0)
            $this->gamestate->nextState('urmianChoice');
        else
            $this->gamestate->nextState('urmianSacrifice');
    }

    function urmian_psychic_cage_sacrifice($card_id, $card_type_id) {
        // Check that this is not the card we just played

        $card_summoned = self::getObjectFromDB("SELECT effect_id, effect_card, effect_card_type
            FROM effect WHERE effect_type='play' ");

        if ($card_summoned !== null) {
            if ($card_summoned['effect_card'] == $card_id) {
                // This is the card we just played => must remove the associated effect
                $card_id = $card_summoned['effect_card'];

                // Remove its effects
                self::DbQuery("DELETE FROM effect WHERE effect_id='" . $card_summoned['effect_id'] . "'");
            }
        }

        $this->gamestate->nextState('sacrifice');
    }

    function vampiric_crown_play($card_id, $card_name, $notifArgs) {
        return "vampiricChoice";
    }
    function vampiric_crown_dualChoice($choice_id) {
        $player_id = self::getActivePlayerId();
        $notifArgs = self::getStandardArgs();

        if ($choice_id == 0) {
            // Draw
            $card = $this->cards->pickCard('deck', $player_id);
            $tokengain = max(0, $this->card_types[self::ot($card['type'])]['points']);

            $notifArgs['i18n'][] = 'draw_card_name';
            $notifArgs['draw_card_name'] = $this->card_types[self::ot($card['type'])]['name'];
            $notifArgs['points'] = $tokengain;
            self::notifyAllPlayers("playerPickPowerCard", clienttranslate('${card_name}: ${player_name} draw a power card (${draw_card_name}) and gets ${points} energy tokens'), $notifArgs);
            self::notifyPlayer($player_id, "pickPowerCard", '', array("card" => $card));
            self::notifyUpdateCardCount();
            self::incStat(1, 'cards_drawn', $player_id);

            self::setGameStateValue('lastCardDrawn', $card['id']);
            self::mayUseEscaped();

            if ($tokengain == 0)
                $this->gamestate->nextState('noGain');
            else {
                self::setGameStateValue('energyNbr', $tokengain);
                $this->gamestate->nextState('gainEnergy');
            }
        } else {
            // Discard

            // Should have at least 1 card in hand
            if ($this->cards->countCardInLocation('hand', $player_id) == 0)
                throw new feException(self::_("You have no card in your hand"), true);

            $this->gamestate->nextState('chooseDiscard');
        }
    }
    function vampiric_crown_discard($card_id, $card_type_id) {
        $player_id = self::getActivePlayerId();

        // Discard this card and gets points
        $card = $this->cards->getCard($card_id);

        // Note: card has been checked before     

        $notifArgs = self::getStandardArgs();
        $tokengain = max(0, $this->card_types[self::ot($card['type'])]['points']);
        $notifArgs['points'] = $tokengain;

        self::notifyUpdateCardCount();

        if ($tokengain == 0)
            $this->gamestate->nextState('noGain');
        else {
            self::setGameStateValue('energyNbr', $tokengain);
            $this->gamestate->nextState('gainEnergy');
        }
    }

    function vampiric_crown_useZira($card_id, $card_name, $notifArgs, $bUseZira, $zira_card_id) {
        if (!$bUseZira)
            throw new feException("You must use Zira");

        $player_id = self::getActivePlayerId();

        // => equivalent to a discard of the card with the most points in hand
        $cards_in_hand = $this->cards->getCardsInLocation('hand', $player_id);
        $valid_cards = 0;
        $max_points = 0;
        $card_with_maxpoints = null;
        foreach ($cards_in_hand as $card) {
            $points = $this->card_types[self::ot($card['type'])]['points'];
            if ($points > $max_points) {
                $max_points = max($max_points, $points);
                $card_with_maxpoints = $card;
            }
        }

        if ($card_with_maxpoints === null)
            throw new feException("Can't find any suitable card for Zira");

        self::vampiric_crown_discard($card['id'], $card['type']);

        return 'do_not_nextState';
    }



    function warden_of_argos_play($card_id, $card_name, $notifArgs) {
        return "warden_choice";
    }

    function warden_of_argos_dualChoice($choice_id) {
        $player_id = self::getActivePlayerId();

        if ($choice_id == 0) {
            // Each player discard 4 energy
            $this->gamestate->nextState('discardEnergy');
        } else {
            // Discard 1 energy token and you can transmute this round
            $this->gamestate->nextState('discardCard');
        }
    }

    function stWardenDiscardEnergyNext() {
        $player_id = self::activeNextPlayer();
        $warden_owner = self::getCurrentEffectCardOwner();

        $transition = ($player_id == $warden_owner) ? "end" : "next";

        $player_energies = self::countPlayerEnergies($player_id);

        if ($player_energies == 0)
            $this->gamestate->nextState($transition);
        else if ($player_energies <= 4) {
            // Discard automatically all energies
            $cost = array();
            $playerStock = self::getResourceStock($player_id);
            foreach ($playerStock as $ress_id => $ress_qt) {
                $cost[$ress_id] = -$ress_qt;
            }
            self::applyResourceDelta($player_id, $cost);

            self::notifyAllPlayers('discardallenergies', clienttranslate('${card_name}: ${player_name} discards all his energy tokens'), array(
                'i18n' => array('card_name'),
                'card_name' => self::getCurrentEffectCardName(),
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
            ));

            $this->gamestate->nextState($transition);
        } else {
            // Player can choose
            $this->gamestate->nextState("playerChoice");
        }
    }

    function warden_of_argos_discardEnergy($discarded) {
        $sum = 0;
        foreach ($discarded as $type => $nbr) {
            $sum += abs($nbr);
        }

        if ($sum != 4)
            throw new feException(sprintf(self::_("You must discard exactly %s energy tokens"), 4), true);

        $player_id = self::getActivePlayerId();
        $warden_owner = self::getCurrentEffectCardOwner();

        if ($player_id == $warden_owner)
            $this->gamestate->nextState("end");
        else
            $this->gamestate->nextState("nextPlayer");
    }


    function stWardenDiscardCardNext() {
        $player_id = self::activeNextPlayer();
        $warden_owner = self::getCurrentEffectCardOwner();

        $transition = ($player_id == $warden_owner) ? "end" : "next";

        $player_cardcount = $this->cards->countCardInLocation('hand', $player_id);

        if ($player_cardcount == 0)
            $this->gamestate->nextState($transition);
        else {
            // Discard a card
            $this->gamestate->nextState("playerChoice");
        }
    }
    function warden_of_argos_discard($card_id, $card_type_id) {
        $player_id = self::getActivePlayerId();
        $warden_owner = self::getCurrentEffectCardOwner();

        if ($player_id == $warden_owner)
            $this->gamestate->nextState("end");
        else
            $this->gamestate->nextState("nextPlayer");
    }

    function warden_of_argos_useZira($card_id, $card_name, $notifArgs, $bUseZira, $zira_card_id) {
        if (!$bUseZira)
            throw new feException("You must use Zira");

        // => equivalent to a discard
        self::warden_of_argos_discard($card_id, 113);

        return 'do_not_nextState';
    }

    function yjang_s_forgotten_vase_onSummon() {
        // +1 energy
        self::setGameStateValue('energyNbr', 1);
        return "gainEnergy";
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// End of game management
    ////////////    

    protected function getGameRankInfos() {

        //  $result = array(   "table" => array( "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) ),       // game statistics
        //                     "result" => array(
        //                                     array( "rank" => 1,
        //                                            "tie" => false,
        //                                            "score" => 354,
        //                                            "player" => 45,
        //                                            "name" => "Kara Thrace",
        //                                            "zombie" => 0,
        //                                            "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) ),
        //                                     array( "rank" => 2,
        //                                            "tie" => false,
        //                                            "score" => 312,
        //                                            "player" => 46,
        //                                            "name" => "Lee Adama",
        //                                            "zombie" => 0,
        //                                            "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) )
        //                                     )
        //              )
        //


        // By default, common method uses 'player_rank' field to create this object
        $result = self::getStandardGameResultObject();
        return $result;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    function zombieTurn($state, $active_player) {
        $player_id = $active_player;

        if ($state['name'] == 'draftChoice') {
            $cards = $this->cards->getCardsInLocation('choice', $active_player);
            $card = reset($cards);
            self::draftChooseCard($card['id']);
        } else if ($state['name'] == 'buildLibrary3' || $state['name'] == 'buildLibrary2') {
            $cards = $this->cards->getCardsInLocation('hand', $active_player);

            foreach ($cards as $card) {
                $card_ids[] = $card['id'];
                if (count($card_ids) >= 3)
                    break;
            }
            self::chooseLibrary($card_ids);
        } else if ($state['name'] == 'buildLibraryNew') {
            $cards = $this->cards->getCardsInLocation('hand', $active_player);

            foreach ($cards as $card) {
                $card_ids[] = $card['id'];
            }
            self::chooseLibraryNew($card_ids);
        } else if ($state['name'] == 'diceChoice') {
            $season = self::getCurrentDiceSeason();
            $die = self::getUniqueValueFromDB("SELECT dice_id FROM dice WHERE dice_season='$season' AND dice_player_id IS NULL LIMIT 0,1 ");
            self::chooseDie($die);
        } else if ($state['name'] == 'maliceDie') {
            self::reroll(false);
        } else if ($state['name'] == 'steadfastDie') {
            self::steadfast(0);
        } else if ($state['name'] == 'playerTurn') {
            $this->gamestate->nextState('endOfTurn');
        } else if ($state['name'] == 'checkEnergy' || $state['name'] == 'nextEffectCheckEnergy' || $state['name'] == 'discardHornPlenty') {
            // Discard all energies
            $playerStock = self::getResourceStock($active_player);
            $cost = array();
            foreach ($playerStock as $ress_id => $ress_qt) {
                $cost[$ress_id] = -$ress_qt;
            }
            self::applyResourceDelta($active_player, $cost);
            $this->gamestate->nextState("discardEnergy");
        } else if ($state['name'] == 'summonVariableCost') {
            $this->gamestate->nextState("chooseCost");
        } else if ($state['name'] == 'bonusDrawChoice') {
            $this->gamestate->nextState("zombieTurn");
        } else if ($state['name'] == 'bonusExchangeDiscard') {
            $this->gamestate->nextState("zombieTurn");
        } else if ($state['name'] == 'bonusGainEnergy') {
            $this->gamestate->nextState("zombieTurn");
        } else if ($state['name'] == 'gainEnergy') {
            $this->gamestate->nextState("end");
        } else if ($state['name'] == 'discardIshtar') {
            $this->gamestate->nextState("discardEnergy");
        } else if ($state['name'] == 'discardKairn') {
            $this->gamestate->nextState("discardEnergy");
        } else if ($state['name'] == 'necroticSacrifice') {
            $this->gamestate->nextState("zombieTurn");
        } else if ($state['name'] == 'mirrorDiscard') {
            $this->gamestate->nextState("none");
        } else if ($state['name'] == 'mirrorChoose') {
            $this->gamestate->nextState("chooseEnergyType");
        } else if ($state['name'] == 'elementalChoice') {
            $this->gamestate->nextState("chooseEnergyType");
        } else if ($state['name'] == 'treeOfLifeChoice') {
            $this->gamestate->nextState("discardEnergy");
        } else if ($state['name'] == 'discardTree') {
            $this->gamestate->nextState("discardEnergy");
        } else if ($state['name'] == 'cauldronPlace') {
            $this->gamestate->nextState("discardEnergy");
        } else if ($state['name'] == 'vampiricChoice') {
            $this->gamestate->nextState("noGain");
        } else if ($state['name'] == 'vampiricDiscard') {
            $this->gamestate->nextState("noGain");
        } else if ($state['name'] == 'amuletFireChoice') {
            $this->gamestate->nextState("chooseCard");
        } else if ($state['name'] == 'divineChoice') {
            $this->gamestate->nextState("chooseCard");
        } else if ($state['name'] == 'potionDreamChoice') {
            $this->gamestate->nextState("chooseCardHand");
        } else if ($state['name'] == 'dragonSkull1') {
            $this->gamestate->nextState("sacrifice");
        } else if ($state['name'] == 'dragonSkull2') {
            $this->gamestate->nextState("sacrifice");
        } else if ($state['name'] == 'dragonSkull3') {
            $this->gamestate->nextState("sacrifice");
        } else if ($state['name'] == 'temporalBoots') {
            $this->gamestate->nextState("moveSeason");
        } else if ($state['name'] == 'syllasSacrifice') {
            $this->gamestate->nextState("nextPlayer");
        } else if ($state['name'] == 'nariaChoice') {
            $this->gamestate->nextState("chooseCard");
        } else if ($state['name'] == 'amsugTakeback') {
            $this->gamestate->nextState("nextPlayer");
        } else if ($state['name'] == 'lewisChoice') {
            $this->gamestate->nextState("choosePlayer");
        } else if ($state['name'] == 'orbChoice') {
            $this->gamestate->nextState("discardNextCard");
        } else if ($state['name'] == 'orbChoice2') {
            $this->gamestate->nextState("orbChoice");
        } else if ($state['name'] == 'draftTwist') {
            $cards = $this->cards->getCardsInLocation('choice', $active_player);
            $card = reset($cards);
            self::draftTwist($card['id']);
        } else if ($state['name'] == 'buildLibraryNew') {
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery($sql);

            $this->gamestate->updateMultiactiveOrNextState('chooseLibrarynew');
        } else {
            $this->gamestate->nextState("zombieTurn");
        }
    }



    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */

    function upgradeTableDb($from_version) {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
        if ($from_version <= 1511251434) {
            self::DbQuery("ALTER TABLE  `effect` CHANGE  `effect_type`  `effect_type` ENUM(  'play',  'active',  'permanent',  'onSummon',  'onSeasonChange',  'onEndTurn',  'onDrawOne' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL");
            self::DbQuery("ALTER TABLE  `zz_replay1_effect` CHANGE  `effect_type`  `effect_type` ENUM(  'play',  'active',  'permanent',  'onSummon',  'onSeasonChange',  'onEndTurn',  'onDrawOne' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL");
            self::DbQuery("ALTER TABLE  `zz_replay2_effect` CHANGE  `effect_type`  `effect_type` ENUM(  'play',  'active',  'permanent',  'onSummon',  'onSeasonChange',  'onEndTurn',  'onDrawOne' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL");
            self::DbQuery("ALTER TABLE  `zz_replay3_effect` CHANGE  `effect_type`  `effect_type` ENUM(  'play',  'active',  'permanent',  'onSummon',  'onSeasonChange',  'onEndTurn',  'onDrawOne' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL");
            self::DbQuery("ALTER TABLE  `zz_savepoint_effect` CHANGE  `effect_type`  `effect_type` ENUM(  'play',  'active',  'permanent',  'onSummon',  'onSeasonChange',  'onEndTurn',  'onDrawOne' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL");
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Debugging tool
    ////////////   

    function ac($card_id) {
        $sql = "INSERT INTO `card` (`card_type` ,`card_type_arg` ,`card_location` ,`card_location_arg`) ";
        $sql .= "VALUES ( '$card_id', '0', 'hand', '" . self::getActivePlayerId() . "') ";
        self::DbQuery($sql);

        $card_id = APP_DbObject::DbGetLastId();
        $card = $this->cards->getCard($card_id);
        $this->notifyPlayer(self::getActivePlayerId(), 'pickPowerCard', '', array('card' => $card));
    }
    function ae($resource_id) {
        self::applyResourceDelta(self::getActivePlayerId(), array($resource_id => 1));
    }
}
