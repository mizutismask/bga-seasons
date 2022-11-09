<?php

/**
 * seasons.action.php
 *
 * @author Grégory Isabelli <gisabelli@gmail.com>
 * @copyright Grégory Isabelli <gisabelli@gmail.com>
 * @package Game kernel
 *
 *
 * seasons main action entry point
 *
 */


class action_seasonssk extends APP_GameAction {
    public function __default() {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "seasonssk_seasonssk";
            self::trace("Complete reinitialization of board game");
        }
    }

    public function chooseLibrary() {
        self::setAjaxMode();

        $cards_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';')
            $cards_raw = substr($cards_raw, 0, -1);
        if ($cards_raw == '')
            $cards = array();
        else
            $cards = explode(';', $cards_raw);

        $this->game->chooseLibrary($cards);
        self::ajaxResponse();
    }
    public function chooseLibrarynew() {
        self::setAjaxMode();

        $cards_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';')
            $cards_raw = substr($cards_raw, 0, -1);
        if ($cards_raw == '')
            $cards = array();
        else
            $cards = explode(';', $cards_raw);

        $this->game->chooseLibrarynew($cards);
        self::ajaxResponse();
    }

    public function chooseDie() {
        self::setAjaxMode();
        $die_id = self::getArg("die", AT_posint, true);
        $this->game->chooseDie($die_id);
        self::ajaxResponse();
    }
    public function endTurn() {
        self::setAjaxMode();
        $this->game->endTurn();
        self::ajaxResponse();
    }

    public function transmute() {
        self::setAjaxMode();

        $energy_raw = self::getArg("energies", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->transmute($energy);
        self::ajaxResponse();
    }

    public function discardEnergy() {
        self::setAjaxMode();

        $energy_raw = self::getArg("energies", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->discardEnergy($energy, false);
        self::ajaxResponse();
    }
    public function discardEnergyEffect() {
        self::setAjaxMode();

        $energy_raw = self::getArg("energies", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->discardEnergy($energy, true);
        self::ajaxResponse();
    }
    public function discardEnergyBonus() {
        self::setAjaxMode();

        $energy_raw = self::getArg("energies", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->discardEnergy($energy, false, true);
        self::ajaxResponse();
    }

    public function summon() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);

        $energy_raw = self::getArg("forceuse", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->summon($card_id, null, false, $energy);
        self::ajaxResponse();
    }
    public function active() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);
        $this->game->active($card_id);
        self::ajaxResponse();
    }

    public function gainEnergy() {
        self::setAjaxMode();
        $energy_id = self::getArg("id", AT_posint, true);
        $this->game->gainEnergy($energy_id);
        self::ajaxResponse();
    }

    public function sacrifice() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);
        $this->game->sacrifice($card_id);
        self::ajaxResponse();
    }

    public function takeBack() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);
        $this->game->takeBack($card_id);
        self::ajaxResponse();
    }
    public function discard() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);
        $this->game->discard($card_id);
        self::ajaxResponse();
    }
    public function chooseCardHand() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);
        $this->game->chooseCardHand($card_id);
        self::ajaxResponse();
    }
    public function chooseCardHandcrafty() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);
        $this->game->chooseCardHandcrafty($card_id);
        self::ajaxResponse();
    }
    public function choosePlayer() {
        self::setAjaxMode();
        $card_id = self::getArg("player", AT_posint, true);
        $this->game->choosePlayer($card_id);
        self::ajaxResponse();
    }
    public function chooseXenergy() {
        self::setAjaxMode();

        $energy_raw = self::getArg("energies", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->chooseXenergy($energy);
        self::ajaxResponse();
    }

    public function chooseEnergyType() {
        self::setAjaxMode();
        $energy_id = self::getArg("id", AT_posint, true);

        $energy_raw = self::getArg("energies", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->chooseEnergyType($energy_id, $energy);
        self::ajaxResponse();
    }

    public function dualChoice() {
        self::setAjaxMode();
        $choice_id = self::getArg("choice", AT_posint, true);
        $this->game->dualChoice($choice_id);
        self::ajaxResponse();
    }
    public function useZira() {
        self::setAjaxMode();
        $choice_id = self::getArg("choice", AT_posint, true);
        $this->game->useZira($choice_id);
        self::ajaxResponse();
    }


    public function keepOrDiscard() {
        self::setAjaxMode();
        $choice_id = self::getArg("choice", AT_posint, true);
        $this->game->keepOrDiscard($choice_id);
        self::ajaxResponse();
    }

    public function chooseCost() {
        self::setAjaxMode();

        $cost_id = self::getArg("cost", AT_posint, true);

        $energy_raw = self::getArg("energies", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->chooseCost($cost_id, $energy);
        self::ajaxResponse();
    }
    public function chooseCostCancel() {
        self::setAjaxMode();
        $this->game->chooseCostCancel();
        self::ajaxResponse();
    }
    public function cardEffectEnd() {
        self::setAjaxMode();
        $this->game->cardEffectEnd();
        self::ajaxResponse();
    }
    public function draftChooseCard() {
        self::setAjaxMode();

        $card_id = self::getArg("id", AT_posint, true);

        $this->game->draftChooseCard($card_id);
        self::ajaxResponse();
    }
    public function draftTwist() {
        self::setAjaxMode();

        $card_id = self::getArg("id", AT_posint, true);

        $this->game->draftTwist($card_id);
        self::ajaxResponse();
    }
    public function chooseCard() {
        self::setAjaxMode();

        $card_id = self::getArg("id", AT_posint, true);

        $this->game->chooseCard($card_id);
        self::ajaxResponse();
    }
    public function chooseTableauCard() {
        self::setAjaxMode();

        $card_id = self::getArg("id", AT_posint, true);

        $this->game->chooseTableauCard($card_id);
        self::ajaxResponse();
    }
    public function cancel() {
        self::setAjaxMode();
        $this->game->cancel();
        self::ajaxResponse();
    }
    public function moveSeason() {
        self::setAjaxMode();
        $month = self::getArg("month", AT_posint, true);
        $this->game->moveSeason($month);
        self::ajaxResponse();
    }
    public function reroll() {
        self::setAjaxMode();
        $bReroll = self::getArg("reroll", AT_bool, true);
        $this->game->reroll($bReroll);
        self::ajaxResponse();
    }
    public function steadfast() {
        self::setAjaxMode();
        $action_id = self::getArg("action_id", AT_posint, true);
        $this->game->steadfast($action_id);
        self::ajaxResponse();
    }
    public function orbChoice() {
        self::setAjaxMode();
        $bReplace = self::getArg("bReplace", AT_bool, true);

        $energy_raw = self::getArg("energy", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->orbChoice($bReplace, $energy);
        self::ajaxResponse();
    }



    public function useBonus() {
        self::setAjaxMode();
        $bonusId = self::getArg("id", AT_posint, true);
        $this->game->useBonus($bonusId);
        self::ajaxResponse();
    }
    public function drawPowerCard() {
        self::setAjaxMode();
        $this->game->drawPowerCard();
        self::ajaxResponse();
    }
    public function amuletOfTime() {
        self::setAjaxMode();

        $cards_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';')
            $cards_raw = substr($cards_raw, 0, -1);
        if ($cards_raw == '')
            $cards = array();
        else
            $cards = explode(';', $cards_raw);

        $this->game->amuletOfTime($cards);
        self::ajaxResponse();
    }

    public function collectEnergy() {
        self::setAjaxMode();

        $items_raw = self::getArg("energies", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($items_raw, -1) == ';')
            $items_raw = substr($items_raw, 0, -1);
        if ($items_raw == '')
            $items = array();
        else
            $items = explode(';', $items_raw);

        $player_to_energies = array();
        foreach ($items as $item) {
            $parts = explode(',', $item);
            $player_id = $parts[0];
            $energy_type = $parts[1];

            if (!isset($player_to_energies[$player_id]))
                $player_to_energies[$player_id] = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);

            $player_to_energies[$player_id][$energy_type]--;
        }

        $this->game->collectEnergy($player_to_energies);
        self::ajaxResponse();
    }

    public function doNotUse() {
        self::setAjaxMode();
        $this->game->doNotUse();
        self::ajaxResponse();
    }


    public function fairyMonolithActive() {
        self::setAjaxMode();
        $energy_raw = self::getArg("energy", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->fairyMonolithActive($energy);
        self::ajaxResponse();
    }

    public function chooseOpponentCard() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);

        $energy_raw = self::getArg("forceuse", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($energy_raw, -1) == ';')
            $energy_raw = substr($energy_raw, 0, -1);
        if ($energy_raw == '')
            $energy = array();
        else
            $energy = explode(';', $energy_raw);

        $this->game->chooseOpponentCard($card_id, $energy);
        self::ajaxResponse();
    }
    public function score() {
        self::setAjaxMode();
        $this->game->score();
        self::ajaxResponse();
    }
    
    public function chooseToken() {
        self::setAjaxMode();
        $card_id = self::getArg("tokenId", AT_posint, true);
        $this->game->chooseToken($card_id);
        self::ajaxResponse();
    }

    public function playToken() {
        self::setAjaxMode();
        $optCardId = self::getArg("optCardId", AT_posint, false);
        $this->game->playToken($optCardId);
        self::ajaxResponse();
    }
    public function endSeeOpponentsHands() {
        self::setAjaxMode();
        $this->game->endSeeOpponentsHands();
        self::ajaxResponse();
    }
    
}
