<?php

/**
 * seasons.view.php
 *
 * @author Grégory Isabelli <gisabelli@gmail.com>
 * @copyright Grégory Isabelli <gisabelli@gmail.com>
 * @package Game kernel
 *
 *
 * seasons main static view construction
 *
 */

require_once(APP_BASE_PATH . "view/common/game.view.php");

class view_seasonssk_seasonssk extends game_view {
    function getGameName() {
        return "seasonssk";
    }
    function build_page($viewArgs) {
        $this->tpl['CARDS_FOR_YEAR_2'] = self::_("My cards for year II");
        $this->tpl['CARDS_FOR_YEAR_3'] = self::_("My cards for year III");
        $this->tpl['OTUS_TITLE'] = self::_("Otus the Oracle");

        $this->tpl['YEAR_I'] = self::_("Year I (my starting hand)");

        $this->tpl['CONVERT3'] = self::_("Transmute this energy into 3 cristals");
        $this->tpl['CONVERT2'] = self::_("Transmute this energy into 2 cristals");
        $this->tpl['CONVERT1'] = self::_("Transmute one of these energies into 1 cristal");

        $this->tpl['LB_SEASONS_DICES'] = self::_("Seasons dice");
        $this->tpl['LB_TRANSMUTATION_RATE'] = self::_("Transmutation rate");
        $this->tpl['LB_CARDS_NUMBER'] = self::_("Cards nb.");
        $this->tpl['LB_ABILITY_TOKENS'] = self::_("Ability tokens");
        $this->tpl['LB_CARDS_DRAWN'] = self::_("Cards drawn");
        $this->tpl['LB_MY_HAND'] = self::_("My hand");
        $this->tpl['LB_CHOOSE_THIS_PLAYER'] = self::_("Choisir ce joueur");

        // Get players
        $players = $this->game->getPlayersInOrder();
        self::dump("players", $players);
        $player_nbr = count($players);    // Note: number of players = number of rows

        $this->page->begin_block("seasonssk_seasonssk", "player");
        $this->page->begin_block("seasonssk_seasonssk", "tokens");

        global $g_user;

        $this->tpl['CURRENT_PLAYER_ID'] = $g_user->get_id();
        if (isset($players[$g_user->get_id()])) {
            $this->tpl['CURRENT_PLAYER_NAME'] = $players[$g_user->get_id()]['player_name'];
            $this->tpl['CURRENT_PLAYER_COLOR'] = $players[$g_user->get_id()]['player_color'];
        } else {
            $this->tpl['CURRENT_PLAYER_NAME'] = '';
            $this->tpl['CURRENT_PLAYER_COLOR'] = '';
        }

        foreach ($players as $player) {
            if ($player['player_id'] != $g_user->get_id()) {
                $this->page->insert_block("tokens", array(
                    "PLAYER_ID" => $player['player_id'],
                    "PLAYER_NAME" => $player['player_name'],
                    "LB_ABILITY_TOKENS" => $this->tpl['LB_ABILITY_TOKENS'],
                ));

                $this->page->insert_block("player", array(
                    "PLAYER_ID" => $player['player_id'],
                    "PLAYER_NAME" => $player['player_name'],
                    "PLAYER_COLOR" => $player['player_color']
                ));
            }
        }
    }
}
