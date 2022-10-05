

ALTER TABLE  `player` ADD  `player_nb_bonus_used` TINYINT UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE `player` ADD `player_invocation` SMALLINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_reserve_size` SMALLINT UNSIGNED NOT NULL DEFAULT '7';
ALTER TABLE `player` ADD `player_score_cristals` int(10) NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_score_raw_cards` int(10) NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_score_eog_cards` int(10) NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_score_bonus_actions` int(10) NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_score_remaining_cards` int(10) NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `resource` (
  `resource_player` int(10) unsigned NOT NULL,
  `resource_id` mediumint(8) unsigned NOT NULL,
  `resource_qt` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`resource_player`,`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `dice` (
  `dice_season` tinyint(3) unsigned NOT NULL,
  `dice_id` smallint(5) unsigned NOT NULL,
  `dice_face` smallint(5) unsigned NOT NULL,
  `dice_player_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`dice_id`,`dice_face`,`dice_season`)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `effect` (
  `effect_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `effect_card` int(10) unsigned NOT NULL,
  `effect_type` enum('play','active','permanent','onSummon','onSeasonChange','onEndTurn','onDrawOne') NOT NULL,
  `effect_card_type` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`effect_id`)
) ENGINE=InnoDB  ;


CREATE TABLE IF NOT EXISTS `resource_on_card` (
  `roc_card` int(10) unsigned NOT NULL,
  `roc_id` mediumint(8) unsigned NOT NULL,
  `roc_qt` mediumint(8) unsigned NOT NULL,
  `roc_player` int(10) unsigned NOT NULL,
  PRIMARY KEY (`roc_card`,`roc_id`),
  KEY `roc_player` (`roc_player`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `raven` (
  `raven_id` int(10) unsigned NOT NULL,
  `raven_original_item` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `argosian` (
  `argosian_id` int(10) unsigned NOT NULL,
  `argosian_locked_item` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ability_token` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;



