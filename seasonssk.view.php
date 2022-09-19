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
        // Get players
        $players = $this->game->loadPlayersBasicInfos();
        self::watch("players", $players);

        $player_nbr = count($players);    // Note: number of players = number of rows

        $this->page->begin_block("seasonssk_seasonssk", "player");

        global $g_user;

        $this->tpl['CURRENT_PLAYER_ID'] = $g_user->get_id();
        if (isset($players[$g_user->get_id()])){
            $this->tpl['CURRENT_PLAYER_NAME'] = $players[$g_user->get_id()]['player_name'];
            $this->tpl['CURRENT_PLAYER_COLOR'] = $players[$g_user->get_id()]['player_color'];
        }
        else{
            $this->tpl['CURRENT_PLAYER_NAME'] = '';
            $this->tpl['CURRENT_PLAYER_COLOR'] = '';
        }

        foreach ($players as $player) {
            if ($player['player_id'] != $g_user->get_id()) {
                $this->page->insert_block("player", array(
                    "PLAYER_ID" => $player['player_id'],
                    "PLAYER_NAME" => $player['player_name'],
                    "PLAYER_COLOR" => $player['player_color']
                ));
            }
        }

        $this->tpl['CARDS_FOR_YEAR_2'] = self::_("Your cards for year II");
        $this->tpl['CARDS_FOR_YEAR_3'] = self::_("Your cards for year III");
        $this->tpl['OTUS_TITLE'] = self::_("Otus the Oracle");

        $this->tpl['YEAR_I'] = self::_("Year I (your starting hand)");

        $this->tpl['CONVERT3'] = self::_("Transmute this energy into 3 cristals");
        $this->tpl['CONVERT2'] = self::_("Transmute this energy into 2 cristals");
        $this->tpl['CONVERT1'] = self::_("Transmute one of these energies into 1 cristal");
    }
}
