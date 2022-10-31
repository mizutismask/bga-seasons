/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SeasonsSK implementation : Grégory Isabelli <gisabelli@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * seasonssk.js
 *
 * SeasonsSK user interface script
 */
const CURRENT_SEASON_OPACITY = 0.0;
const OTHER_SEASON_OPACITY = 0.4;
const SVG_SIZE = 400;

define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    g_gamethemeurl + "modules/bga-cards.js",
],
    function (dojo, declare, bgaCards) {
        return declare("bgagame.seasonssk", ebg.core.gamegui, {
            constructor: function () {
                console.log('seasonssk constructor');

                this.seasonDices = null;
                this.playerHand = null;
                this.cardChoice = null;
                this.otusChoice = null;
                this.playerTableau = {};
                this.underlayerPlayerTableau = {};
                this.tokensStock = {};

                this.cardwidth = 124;
                this.cardHeight = 173;

                this.energies = {};
                this.energies_reserve = {};
                this.energies_on_card = {};
                this.amulet_of_water_ingame = {};
                this.library = {};
                this.libraryBuild = {};

                this.nextInvocCardId = -1;

                this.isDebug = window.location.host == 'studio.boardgamearena.com';
                this.log = this.isDebug ? console.log.bind(window.console) : function () { };
                //this.animationDuration = 500;
                this.scoreAnimationDuration = 1500;

                dojo.connect(window, "onresize", this, dojo.hitch(this, "updateScrollButtonsVisibility"));
            },

            setup: function (gamedatas) {

                this.setupSeasonHighlighter();
                this.leftPlayerBoardsCristalCounters = [];

                console.log("gamedatas", gamedatas);
                if (Number(gamedatas.gamestate.id) == 98 || Number(gamedatas.gamestate.id) == 99 || Number(gamedatas.gamestate.id) == 100) { // score or end
                    this.onEnteringShowScore(true);

                }
                for (var player_id in gamedatas.players) {
                    var player = gamedatas.players[player_id];
                    var player_board_div = $('player_board_' + player_id);

                    if (player_id != this.player_id) { player.choose_opponent = 'choose_opponent'; }
                    else { player.choose_opponent = ''; }

                    dojo.place(this.format_block('jstpl_player_board', {
                        player: player,
                        maxInfo: _(" (15 max)"),
                    }), player_board_div);

                    this.setupPlayerOrderHints(player_id, gamedatas);

                    dojo.addClass('overall_player_board_' + player_id, 'avatarBorder');
                    var nameDiv = "overall_player_board_" + player_id;
                    dojo.style(nameDiv, "border-color", '#' + player['color']);
                    dojo.create('img', { id: "player_board_avatar_" + player_id, class: 'ssn-avatar avatarBorder', style: 'border-color:inherit' }, nameDiv, 'last');
                    dojo.attr("player_board_avatar_" + player_id, "src", this.getPlayerAvatarWithSize(player_id, 92));

                    $('invocation_level_' + player_id).innerHTML = player.invocation;
                    if (gamedatas.handcount[player_id]) { $('handcount_' + player_id).innerHTML = gamedatas.handcount[player_id]; }
                    else { $('handcount_' + player_id).innerHTML = 0; }

                    this.energies[player_id] = new ebg.stock();
                    this.energies[player_id].create(this, $('energies_' + player_id), 25, 25);
                    if (player_id != this.player_id) {
                        this.energies[player_id].setSelectionMode(0);
                    }
                    for (var ress_id = 1; ress_id <= 4; ress_id++) {
                        this.energies[player_id].addItemType(ress_id, ress_id, g_gamethemeurl + 'img/icons.png', ress_id - 1);
                    }

                    dojo.place("bonusused_" + player_id, "icon_point_" + player_id, "after");
                    if (toint(player.nb_bonus) == 0) { dojo.addClass("bonusused_" + player_id, "invisible"); }

                    this.energies_reserve[player_id] = new ebg.stock();
                    this.energies_reserve[player_id].create(this, $('energy_reserve_' + player_id), 25, 25);
                    this.energies_reserve[player_id].addItemType(0, 0, g_gamethemeurl + 'img/icons.png', 4);
                    this.energies_reserve[player_id].setSelectionMode(0);

                    var ress_total = 0;
                    if (this.gamedatas.resource[player_id]) {
                        for (var ress_id in this.gamedatas.resource[player_id]) {
                            var qt = this.gamedatas.resource[player_id][ress_id];
                            for (var i = 0; i < qt; i++) {
                                this.energies[player_id].addToStock(ress_id);
                            }
                            ress_total += toint(qt);
                        }
                    }
                    if (player_id != this.player_id) {
                        this.energies[player_id].setSelectionMode(0);
                    }

                    // Void items (reserve size)
                    this.setReserveSize(player_id, player.reserve_size);

                    //cards stocks
                    var itemMargin = 25;
                    var itemsPerRow = 10;
                    //underlayer for empty slots
                    this.underlayerPlayerTableau[player_id] = new ebg.stock();
                    this.underlayerPlayerTableau[player_id].item_margin = itemMargin;
                    this.underlayerPlayerTableau[player_id].create(this, $('underlayer_player_tableau_' + player_id), this.cardwidth, this.cardHeight);
                    this.underlayerPlayerTableau[player_id].image_items_per_row = itemsPerRow;
                    this.underlayerPlayerTableau[player_id].extraClasses = 'thickness empty-slot';
                    this.underlayerPlayerTableau[player_id].onItemCreate = dojo.hitch(this, 'setupNewCard');
                    this.underlayerPlayerTableau[player_id].setSelectionMode = 0;
                    this.underlayerPlayerTableau[player_id].addItemType(0, 9999, g_gamethemeurl + 'img/voidcard.png', 0);
                    for (let i = 0; i < 15; i++) {//insert empty slots
                        this.underlayerPlayerTableau[player_id].addToStockWithId(0, this.nextInvocCardId);
                        this.nextInvocCardId--;
                    }
                    this.underlayerPlayerTableau[player_id].extraClasses = 'thickness ssn-loc-available ';


                    this.playerTableau[player_id] = new ebg.stock();
                    this.playerTableau[player_id].item_margin = itemMargin;
                    //console.log("************", player_id, this.playerTableau);
                    this.playerTableau[player_id].create(this, $('player_tableau_' + player_id), this.cardwidth, this.cardHeight);
                    this.playerTableau[player_id].image_items_per_row = itemsPerRow;
                    this.playerTableau[player_id].extraClasses = 'thickness';

                    for (var card_id in this.gamedatas.card_types) {
                        this.playerTableau[player_id].addItemType(card_id, 0, g_gamethemeurl + 'img/cards.jpg', this.getCardImageIndex(card_id));
                    }

                    this.playerTableau[player_id].onItemCreate = dojo.hitch(this, 'setupNewCard');

                    if (player_id != this.player_id) {
                        dojo.connect(this.playerTableau[player_id], 'onChangeSelection', this, 'onOpponentCardSelection');
                    }
                    else {
                        dojo.connect(this.playerTableau[player_id], 'onChangeSelection', this, 'onPowerCardActivation');
                    }

                    //left player board
                    dojo.query("#bonusUsedCube_" + player_id).addClass('bonusUsed' + player.nb_bonus);
                    this.disableBonusActions(player_id, toint(player.nb_bonus) == 3);

                    dojo.query('.age2').connect('onclick', this, 'onShowAgeCards');
                    dojo.query('.age3').connect('onclick', this, 'onShowAgeCards');

                    dojo.attr("left_avatar_" + player_id, "src", this.getPlayerAvatarWithSize(player_id, 92));

                    this.leftPlayerBoardsCristalCounters[player_id.toString()] = new ebg.counter();
                    this.leftPlayerBoardsCristalCounters[player_id.toString()].create('cristals_counter_' + player_id);

                    //tokens
                    this.tokensStock[player_id] = new ebg.stock();
                    this.tokensStock[player_id].create(this, $('tokens_' + player_id), 99, 99);
                    this.tokensStock[player_id].setSelectionMode(0);
                    this.tokensStock[player_id].image_items_per_row = 6;
                    this.tokensStock[player_id].autowidth = true;
                    this.tokensStock[player_id].onItemCreate = dojo.hitch(this, 'setupNewToken');

                    if (gamedatas.tokens) {
                        for (var tokenType in this.gamedatas.abilityTokens) {
                            this.tokensStock[player_id].addItemType(tokenType, tokenType, g_gamethemeurl + 'img/abilityTokens.png', parseInt(tokenType) * 2 - 2);//recto
                            this.tokensStock[player_id].addItemType(tokenType + "2", tokenType, g_gamethemeurl + 'img/abilityTokens.png', parseInt(tokenType) * 2 - 1);//verso
                        }

                        for (const [tokenId, token] of Object.entries(this.gamedatas.tokens[player_id])) {
                            this.tokensStock[player_id].addToStockWithId(token.type, tokenId);
                        }

                        if (Object.keys(gamedatas.tokens[player_id]).length == 1) {
                            const notif = {};
                            notif.args = {};
                            notif.args.player_id = player_id;
                            notif.args.token_id = Object.keys(gamedatas.tokens[player_id])[0];
                            this.notif_tokenChosen(notif);
                        }
                    } else {
                        $(abilityTokens).style.display = "none";
                    }
                }
                this.updateCounters(gamedatas.counters);

                this.addTooltipToClass('tinvocationlevel', _('Summoning gauge: maximum number of cards this player can have in play (maximum value: 15)'), '');
                this.addTooltipToClass('tthand', _('Number of power cards in hand'), '');
                this.addTooltipToClass('ttbonusused', _('Bonus used penalty: -5 points for 1 bonus, -12 points for 2 bonus, -20 points for 3 bonus.'), '');

                // Libraries
                this.agePopins = [];
                for (var i = 2; i <= 3; i++) {
                    this.createYearCardsPopin(i);
                    this.library[i] = new ebg.stock();
                    this.library[i].create(this, $('library_' + i), 124, 173);
                    this.library[i].image_items_per_row = 10;
                    this.library[i].extraClasses = 'thickness';
                    this.library[i].onItemCreate = dojo.hitch(this, 'setupNewCard');
                    this.library[i].setSelectionMode(0);
                    this.library[i].autowidth = true;
                }

                for (var card_id in this.gamedatas.card_types) {
                    var card = this.gamedatas.card_types[card_id];
                    this.library[2].addItemType(card_id, card_id, g_gamethemeurl + 'img/cards.jpg', this.getCardImageIndex(card_id));
                    this.library[3].addItemType(card_id, card_id, g_gamethemeurl + 'img/cards.jpg', this.getCardImageIndex(card_id));
                }

                for (var i in this.gamedatas.libraries[2]) {
                    var card = this.gamedatas.libraries[2][i];
                    this.library[2].addToStockWithId(card.type, card.id);
                }
                for (var i in this.gamedatas.libraries[3]) {
                    var card = this.gamedatas.libraries[3][i];
                    this.library[3].addToStockWithId(card.type, card.id);
                }

                // Library build
                for (var l = 1; l <= 3; l++) {
                    this.libraryBuild[l] = new ebg.stock();
                    this.libraryBuild[l].create(this, $('library_build_' + l), 124, 173);
                    this.libraryBuild[l].image_items_per_row = 10;
                    this.libraryBuild[l].onItemCreate = dojo.hitch(this, 'setupNewCard');
                    this.libraryBuild[l].setSelectionMode(1);
                    this.libraryBuild[l].extraClasses = 'thickness';

                    for (var card_id in this.gamedatas.card_types) {
                        var card = this.gamedatas.card_types[card_id];
                        this.libraryBuild[l].addItemType(card_id, card_id, g_gamethemeurl + 'img/cards.jpg', this.getCardImageIndex(card_id));
                    }
                    this.libraryBuild[l].addItemType(0, 9999, g_gamethemeurl + 'img/voidcard.png', 0);
                    for (var i = 1; i <= 3; i++) {
                        this.libraryBuild[l].addToStockWithId(0, this.nextInvocCardId);
                        this.nextInvocCardId--;
                    }
                    dojo.connect(this.libraryBuild[l], 'onChangeSelection', this, 'onLibraryBuildchange');
                }

                // Init seasondices
                this.seasonDices = new ebg.stock();
                this.seasonDices.create(this, $('seasons_dices'), 54, 54);
                this.seasonDices.image_items_per_row = 20;
                this.seasonDices.onItemCreate = dojo.hitch(this, 'setupNewDie');
                this.seasonDices.extraClasses = 'die';
                // this.seasonDices.autowidth=true;//todo check
                for (var season_id in this.gamedatas.dices) {
                    for (var dice_id in this.gamedatas.dices[season_id]) {
                        for (var face_id = 1; face_id <= 6; face_id++) {
                            var dice_face_id = season_id + '' + dice_id + '' + face_id;
                            var image_id = (toint(season_id) - 1) * 5;
                            image_id += (toint(dice_id) - 1);
                            image_id += (toint(face_id) - 1) * 20;
                            this.seasonDices.addItemType(dice_face_id, toint(dice_face_id), g_gamethemeurl + 'img/dices.png', image_id);
                        }
                    }
                }
                dojo.connect(this.seasonDices, 'onChangeSelection', this, 'onDiceSelectionChanged');

                this.showSeasonDices(this.gamedatas.seasondices, this.gamedatas.dice_season > 0);

                // Year and month
                this.setSeasonDate(this.gamedatas.year, this.gamedatas.month);

                // Init scrollable player hand
                //todo
                //this.playerHand.apparenceBorderWidth = '2px';
                //this.playerHand.onItemCreate = dojo.hitch(this, 'setupNewCard');
                // create the card manager
                this.handManager = new CardManager(this, {
                    getId: (card) => `card-${card.id}`,
                    setupDiv: (card, div) => {
                        div.classList.add('thickness');
                        div.style.width = '124px';
                        div.style.height = '173px';
                        div.style.position = 'relative';
                    },
                    setupFrontDiv: (card, div) => {
                        //calculates background data to see the correct picture, like in the old stock component
                        div.style.backgroundImage = "url(" + g_gamethemeurl + 'img/cards.jpg' + ")"
                        const imageItemsPerRow = 10;
                        var imagePosition = this.getCardImageIndex(card.type);
                        var row = Math.floor(imagePosition / imageItemsPerRow);
                        img_dy = (imagePosition - (row * imageItemsPerRow)) * 100;
                        img_dx = row * 100;
                        div.style.backgroundPosition = "-" + img_dy + "% -" + img_dx + "%";

                        div.classList.add('seasons-card-front');
                        div.id = `card-${card.id}-front`;

                        //adds the name of the card
                        var cardDesc = this.gamedatas.card_types[card.type];
                        dojo.place(this.format_block('jstpl_card_content', {
                            id: card.id,
                            type: card.type,
                            name: _(cardDesc.name),
                            cardactivation: cardDesc.activation ? "cardactivation" : "",
                        }), div.id);

                        var html = this.getCardTooltip(card.type, false);
                        this.addTooltipHtml(div.id, html);
                    },
                    setupBackDiv: (card, div) => {
                        //no back in seasons
                    },
                });
                var settings = {
                    "width": "300px", "height": "300px", "shift": "2px", "center": false, "scrollbarVisible": false, "scrollStep": 130, "buttonGap": "5px", "leftButton": { "classes": "scroll-button" }, "rightButton": { "classes": "scroll-button" }
                }
                this.playerHand = new ScrollableStock(this.handManager, document.getElementById(`player_hand`), settings);
                this.playerHand.setSelectionMode("single");
                this.playerHand.onSelectionChange = (selection, lastChange) => this.onPlayerHandSelectionChanged(selection, lastChange);
                for (var i in this.gamedatas.hand) {
                    var card = this.gamedatas.hand[i];
                    this.addCardToPlayerHand(card);
                }

                // Init card choice
                this.cardChoice = new ebg.stock();
                this.cardChoice.create(this, $('choiceCardsStock'), 124, 173);
                this.cardChoice.image_items_per_row = 10;
                this.cardChoice.onItemCreate = dojo.hitch(this, 'setupNewCard');
                this.cardChoice.extraClasses = 'thickness';
                for (var card_id in this.gamedatas.card_types) {
                    var card = this.gamedatas.card_types[card_id];

                    this.cardChoice.addItemType(card_id, card_id, g_gamethemeurl + 'img/cards.jpg', this.getCardImageIndex(card_id));
                }

                this.otusChoice = new ebg.stock();
                this.otusChoice.create(this, $('otus'), 124, 173);
                this.otusChoice.image_items_per_row = 10;
                this.otusChoice.extraClasses = 'thickness';
                this.otusChoice.onItemCreate = dojo.hitch(this, 'setupNewCard');
                for (var card_id in this.gamedatas.card_types) {
                    var card = this.gamedatas.card_types[card_id];

                    this.otusChoice.addItemType(card_id, card_id, g_gamethemeurl + 'img/cards.jpg', this.getCardImageIndex(card_id));
                }

                // Initial cards in card choice
                for (var i in this.gamedatas.cardChoice) {
                    var card = this.gamedatas.cardChoice[i];
                    this.cardChoice.addToStockWithId(card.type, card.id);
                }
                dojo.style('choiceCards', 'display', 'none');

                for (var i in this.gamedatas.otusChoice) {
                    dojo.style('otus_wrap', 'display', 'block');
                    var card = this.gamedatas.otusChoice[i];
                    this.otusChoice.addToStockWithId(card.type, card.id);
                }
                dojo.style('choiceCards', 'display', 'none');

                // Initial cards in player's tableau
                for (var i in this.gamedatas.tableau) {
                    var card = this.gamedatas.tableau[i];
                    this.playerTableau[card.location_arg].addToStockWithId(this.ot(card.type), card.id);
                    if (toint(card.type_arg) == 1) { this.markCardActivated(card.location_arg, card.id); }
                    this.setupNewCardOnTableau(card.type, card.id, card.location_arg);
                }

                // Add invocation card on tableau
                for (player_id in gamedatas.players) {
                    this.updateInvocationLevelOnSlots(player_id);
                }

                // Resources on cards
                for (var card_id in this.gamedatas.roc) {
                    for (var ress_id in this.gamedatas.roc[card_id]) {
                        for (i = 0; i < this.gamedatas.roc[card_id][ress_id].qt; i++) {
                            this.placeEnergyOnCard(card_id, ress_id, this.gamedatas.roc[card_id][ress_id].player);
                        }
                    }
                }

                this.setFirstPlayer(gamedatas.firstplayer);
                this.addTooltip('firstplayer', _('First player'), '');

                dojo.connect(this.cardChoice, 'onChangeSelection', this, 'onChoiceCardsSelectionChanged');
                dojo.connect(this.otusChoice, 'onChangeSelection', this, 'onOtusChoiceCardsSelectionChanged');

                // Player choice
                dojo.query('.choose_player').connect('onclick', this, 'onChoosePlayer');

                dojo.query('.monthplace').connect('onclick', this, 'onMoveSeason');

                dojo.query('.bonus').connect('onclick', this, 'onUseBonus');

                this.addTooltip('current_month', _('Season token: indicate current time and season.'), '');
                this.addTooltip('current_year', _('Year indicator (game end after the third year)'), '');

                this.addTooltip('convertFor3', '', _('Transmute one token of this energy into 3 cristals'));
                this.addTooltip('convertFor2', '', _('Transmute one token of this energy into 2 cristals'));
                this.addTooltip('convertFor1', '', _('Transmute one of these energies into 1 cristal'));

                this.ensureSpecificImageLoading(['../common/point.png']);

                dojo.query(".fa-star").removeClass("fa fa-star").addClass("sicon icon_cristal").style("vertical-align", "middle");

                this.setupNotifications();
            },

            /** adds previous and next player color and name in a tooltip */
            setupPlayerOrderHints(playerId, gamedatas) {
                var nameDiv = this.queryFirst('#player_name_' + playerId + ' a');
                var playerIndex = gamedatas.playerorder.indexOf(parseInt(playerId)); //playerorder is a mixed types array
                if (playerIndex == -1) playerIndex = gamedatas.playerorder.indexOf(playerId.toString());

                var previousId = playerIndex - 1 < 0 ? gamedatas.playerorder[gamedatas.playerorder.length - 1] : gamedatas.playerorder[playerIndex - 1];
                var nextId = playerIndex + 1 >= gamedatas.playerorder.length ? gamedatas.playerorder[0] : gamedatas.playerorder[playerIndex + 1];
                dojo.create('div', { class: 'playerOrderHelp', title: gamedatas.players[previousId].name, style: 'color:#' + gamedatas.players[previousId]['color'], innerHTML: "&gt;" }, nameDiv, 'before');
                dojo.create('div', { class: 'playerOrderHelp', title: gamedatas.players[nextId].name, style: 'color:#' + gamedatas.players[nextId]['color'], innerHTML: "&gt;" }, nameDiv, 'after');
            },

            ///////////////////////////////////////////////////
            //// Utilities

            addCardToPlayerHand(card) {
                this.playerHand.addCard(card);
                this.updateScrollButtonsVisibility();
            },

            removeCardFromPlayerHand(card) {
                this.playerHand.removeCard(card);
                this.updateScrollButtonsVisibility();
            },

            /*
            * Play a given sound that should be first added in the tpl file
            */
            playSound(sound, playNextMoveSound = true) {
                if (soundManager.bMuteSound == false) {
                    playSound(sound);
                    playNextMoveSound && this.disableNextMoveSound();
                }
            },

            createYearCardsPopin(age) {
                this.agePopins[age] = new ebg.popindialog();
                this.agePopins[age].create('age' + age + 'Popin');
                this.agePopins[age].setTitle(age == 2 ? _("<h3>Your cards for year II</h3>") : _("<h3>Your cards for year III</h3>"));
                var html = this.format_block('jstpl_year' + age, {});
                this.agePopins[age].setContent(html);
                // allows to reopen the popin several times
                this.agePopins[age].replaceCloseCallback(() => this.agePopins[age].hide());
            },

            showAgeCardsPopin(age) {
                this.agePopins[age].show();
            },

            getPlayerAvatar(pId) {
                return $('avatar_' + pId)
                    ? dojo.attr('avatar_' + pId, 'src')
                    : 'https://en.studio.boardgamearena.com:8083/data/avatar/noimage.png';
            },

            /** 184, 92, 50, 32 are valid sizes. */
            getPlayerAvatarWithSize(pId, size) {
                let url = this.getPlayerAvatar(pId);
                return url.replace(/_[0-9]{2}./, "_" + size + ".");
            },
            /**
             * Creates four circle quarters with different opacities to highlight the current season.
             */
            setupSeasonHighlighter() {
                const svgRoot = document.getElementById("seasonHighlighter");
                const svgHighlightSeason = `<svg width="${SVG_SIZE}" height="${SVG_SIZE}">
                    <path
                        d="${this.getSectorPath(SVG_SIZE / 2, SVG_SIZE / 2, SVG_SIZE, 0, 90)}"
                        fill-opacity="${CURRENT_SEASON_OPACITY}"
                    />
                    <path
                        d="${this.getSectorPath(SVG_SIZE / 2, SVG_SIZE / 2, SVG_SIZE, 270, 0)}"
                        fill-opacity="${CURRENT_SEASON_OPACITY}"
                    />
                     <path
                        d="${this.getSectorPath(SVG_SIZE / 2, SVG_SIZE / 2, SVG_SIZE, 180, 270)}"
                        fill-opacity="${CURRENT_SEASON_OPACITY}"
                    />
                    <path
                        d="${this.getSectorPath(SVG_SIZE / 2, SVG_SIZE / 2, SVG_SIZE, 90, 180)}"
                        fill-opacity="${CURRENT_SEASON_OPACITY}"
                    />
                </svg>`;
                const svgNode = document.createRange().createContextualFragment(svgHighlightSeason);
                svgRoot.appendChild(svgNode);
            },

            changeCurrentSeason(currentSeason) {
                dojo.query(`#seasonHighlighter svg path`).forEach(quarter => {
                    dojo.attr(quarter, "fill-opacity", OTHER_SEASON_OPACITY);
                });
                dojo.query(`#seasonHighlighter svg path:nth-child(${currentSeason})`).forEach(quarter => {
                    dojo.attr(quarter, "fill-opacity", CURRENT_SEASON_OPACITY);
                });
                this.playSound("season_" + currentSeason, false);
            },

            getSectorPath: function (x, y, outerDiameter, a1, a2) {
                const degtorad = Math.PI / 180;
                const cr = outerDiameter / 2;
                const cx1 = Math.cos(degtorad * a2) * cr + x;
                const cy1 = -Math.sin(degtorad * a2) * cr + y;
                const cx2 = Math.cos(degtorad * a1) * cr + x;
                const cy2 = -Math.sin(degtorad * a1) * cr + y;

                return `M${x} ${y} ${cx1} ${cy1} A${cr} ${cr} 0 0 1 ${cx2} ${cy2}Z`;
            },

            queryFirst: function (query) {
                return document.querySelector(query);
            },

            queryFirstId: function (query, defaultValue) {
                var res = document.querySelector(query);
                if (!res) return defaultValue;
                return res.id;
            },

            // Get card original type (see "Raven")
            ot: function (card_type_id) {
                var sep = card_type_id.indexOf(';');
                if (sep != -1) {
                    //... take original type
                    return card_type_id.substr(0, sep);
                }
                else
                    return card_type_id;
            },
            ct: function (card_type_id) {
                var sep = card_type_id.indexOf(';');
                if (sep != -1) {
                    //... take current type
                    return card_type_id.substr(sep + 1);
                }
                else
                    return card_type_id;
            },

            getCardImageIndex: function (typeId) {
                if (typeof this.gamedatas.card_types[typeId].imageindex != 'undefined') {
                    return this.gamedatas.card_types[typeId].imageindex;
                }
                return toint(typeId) - 1;
            },

            setFirstPlayer: function (player_id) {
                console.log('setFirstPlayer');

                var firstplayer = $('firstplayer');
                if (!firstplayer) {
                    dojo.place(this.format_block('jstpl_firstplayer', {}), $('firstplayer_' + player_id));
                }
                else {
                    firstplayer = this.attachToNewParent(firstplayer, $('firstplayer_' + player_id));
                    this.slideToObject(firstplayer, $('firstplayer_' + player_id)).play();
                    this.addTooltip('firstplayer', _('First player'), '');
                }
            },

            setSeasonDate: function (year, month) {
                if (toint(year) == 0) { year = 1; }
                if (toint(year) > 3) { year = 3; } this.slideToObject($('current_year'), 'yearplace_' + year, 1000).play();

                this.currentMonth = parseInt(month);
                var currentSeason = this.getSeasonFromMonth(month);
                var monthAnimation = this.slideToObject($('current_month'), 'monthplace_' + month, 1000);
                dojo.connect(monthAnimation, 'onEnd', dojo.hitch(this, 'changeCurrentSeason', currentSeason));
                monthAnimation.play();

                dojo.query("html").removeClass("season_1 season_2 season_3 season_4").addClass("season_" + currentSeason);

                switch (currentSeason) {
                    //red1 blue2 yellow3 green4
                    case 1:
                        this.updateConversionReminder(["energy4", "energy3", "energy2", "energy1"]);
                        break;
                    case 2:
                        this.updateConversionReminder(["energy3", "energy1", "energy4", "energy2"]);
                        break;
                    case 3:
                        this.updateConversionReminder(["energy1", "energy2", "energy3", "energy4"]);
                        break;
                    case 4:
                        this.updateConversionReminder(["energy2", "energy4", "energy1", "energy3"]);
                        break;
                }

                if (toint(year) > 1) { dojo.style('library_2_wrap', 'display', 'none'); }
                if (toint(year) > 2) { dojo.style('library_3_wrap', 'display', 'none'); }

            },

            updateConversionReminder: function (energies) {
                dojo.query("#convertFor3 .sicon:first-child").removeClass("energy4 energy1 energy2 energy3").addClass(energies[0]);
                dojo.query("#convertFor2 .sicon:first-child").removeClass("energy4 energy1 energy2 energy3").addClass(energies[1]);
                dojo.query("#convertFor1 #energyType1").removeClass("energy4 energy1 energy2 energy3").addClass(energies[2]);
                dojo.query("#convertFor1 #energyType2").removeClass("energy4 energy1 energy2 energy3").addClass(energies[3]);
            },

            getSeasonFromMonth: function (month) {
                return Math.floor((month - 1) / 3) + 1;
            },

            setupNewDie: function (die_div, die_type_id, die_id) {
                //adds die faces to make die roll animation
                let face = die_type_id[2];
                dojo.attr(die_div, "data-die-face", face);
                let html = `<ol class="die-list" data-roll="${face}">`;
                for (let dieFace = 1; dieFace <= 6; dieFace++) {
                    html += `<li class="die-item" data-side="${dieFace}" style="background-position:${this.getBackgroundPosition(die_type_id, dieFace)}"></li>`;
                }
                html += `   </ol>
                        </div>`;
                dojo.place(html, die_div);

                this.addTooltipHtml(die_div.id, this.getDieTooltip(die_type_id), _('Choose this die'));
            },

            getBackgroundPosition: function (dice_type, expectedFace = undefined) {
                var season = dice_type[0];
                var dice = dice_type[1];
                var realFace = expectedFace;
                if (!realFace) {
                    realFace = dice_type[2];
                }
                var backx = 54 * (toint(dice) - 1) + (5 * 54 * (toint(season) - 1));
                var backy = 54 * (toint(realFace) - 1);
                return '-' + backx + 'px -' + backy + 'px';
            },

            getDieTooltip: function (die_type_id) {
                var tpl = {};

                var dieinfos = this.gamedatas.dices[die_type_id[0]][die_type_id[1]][die_type_id[2]];

                tpl.nrj = '';
                for (var nrj_id in dieinfos.nrj) {
                    for (var i = 0; i < dieinfos.nrj[nrj_id]; i++) {
                        tpl.nrj += '<div class="sicon energy' + nrj_id + '"></div>';
                    }
                }
                if (tpl.nrj != '') {
                    tpl.nrj = '<li>' + _('Gain energy:') + ' ' + tpl.nrj + '</li>';
                }

                tpl.transmute = '';
                if (dieinfos.trans) {
                    tpl.transmute = '<li>' + _('You can transmute during this turn') + '</li>';
                }

                tpl.points = '';
                if (dieinfos.pts > 0) {
                    tpl.points = '<li>' + dojo.string.substitute(_('Gain ${points} crystals'), { points: dieinfos.pts }) + '</li>';
                }

                tpl.card = '';
                if (dieinfos.card) {
                    tpl.transmute = '<li>' + _('Draw a power card') + '</li>';
                }

                tpl.summon = '';
                if (dieinfos.inv) {
                    tpl.summon = '<li>' + _('+1 to your summoning gauge') + '</li>';
                }

                tpl.timeprogress = '<li>' + dojo.string.substitute(_('If this die is not chosen, season token moves forward by ${spaces} spaces'), { spaces: dieinfos.time }) + '</li>';

                tpl.all_die_faces = _("All faces of this die");

                tpl.dicex = 54 * toint((die_type_id[0] - 1) * 5 + (die_type_id[1] - 1));

                return this.format_block('jstpl_die_tooltip', tpl);
            },

            getCardTooltip: function (card_type_id, bUsurpator) {
                var card = this.gamedatas.card_types[card_type_id];

                if (bUsurpator) {
                    card.categoryd = _('Familiar');
                    card.points = 2;
                }
                else if (card.category == 'f') { card.categoryd = _('Familiar'); }
                else { card.categoryd = _('Magic item'); }

                card.text = this.nl2br(_(card.text), false);
                card.text = card.text.replace('**', '<div class="sicon icon_active"></div>');
                card.text = card.text.replace('**', '<div class="sicon icon_active"></div>');
                card.text = card.text.replace('§§', '<div class="sicon icon_play"></div>');
                card.text = card.text.replace('øø', '<div class="sicon icon_permanent"></div>');
                card.text = card.text.replace(new RegExp("\\. ", 'g'), '.<br/><br/>');
                card.text = card.text.replace(new RegExp(" -", 'g'), '<br/>-');

                card.costd = '';
                for (var ress_id in card.cost) {
                    var qt = card.cost[ress_id];
                    if (toint(ress_id) > 0) {
                        for (var i = 0; i < qt; i++) {
                            card.costd += "<div class='sicon energy" + ress_id + "'></div> ";
                        }
                    }
                    else {
                        card.costd += "<div class='sicon energy" + ress_id + "'></div>x" + qt + " ";
                    }
                }

                var card_imageindex = this.getCardImageIndex(card_type_id);
                card.artx = 248 * ((toint(card_imageindex)) % 10);
                card.arty = 346 * (Math.floor((toint(card_imageindex)) / 10));

                card.named = _(card.name);
                if (bUsurpator) {
                    card.named = _("Raven the Usurper") + ' (' + card.named + ')';
                }

                return this.format_block('jstpl_card_tooltip', card);
            },

            getTokenTooltip: function (card_div, card_id, card_type_id) {
                var card = this.gamedatas.abilityTokens[card_type_id];
                card.text = this.nl2br(_(card.desc), false);
                card.text = card.text.replace(new RegExp("\\. ", 'g'), '.<br/><br/>');
                card.text = card.text.replace(new RegExp(" -", 'g'), '<br/>-');

                // Get the background position information 
                backPos = dojo.style(card_div, 'backgroundPosition');

                return this.format_block('jstpl_token_tooltip', {
                    "text": card.text,
                    "points": card.points,
                    "backPos": backPos,
                });
            },

            setupNewToken: function (card_div, card_type_id, card_id) {
                if (card_type_id != 0) {
                    var html = this.getTokenTooltip(card_div, card_id, card_type_id);
                    this.addTooltipHtml(card_div.id, html, 100);
                }
            },


            setupNewCard: function (card_div, card_type_id, card_id) {
                if (card_type_id != 0) {
                    var card = this.gamedatas.card_types[card_type_id];
                    var html = this.getCardTooltip(card_type_id, false);

                    this.addTooltipHtml(card_div.id, html, 100);

                    dojo.place(this.format_block('jstpl_card_content', {
                        id: card_id,
                        type: card_type_id,
                        name: _(card.name),
                        cardactivation: card.activation ? "cardactivation" : "",
                    }), card_div.id);
                }
            },

            setupNewCardOnTableau: function (card_type_id, tcard_id, player_id) {
                var original_card_type_id = this.ot(card_type_id);
                var card_type_id = this.ct(card_type_id);


                //remove blank
                this.playerTableau[player_id].removeFromStock(0);

                // Note: Cauldron and Amulet of water and Heart of Argos and jewel of the Ancients and monolith
                if (toint(card_type_id) == 35 || toint(card_type_id) == 4 || toint(card_type_id) == 101
                    || toint(card_type_id) == 112 || toint(card_type_id) == 103 || toint(card_type_id) == 207) {
                    // => these cards can store energy ON the card
                    this.energies_on_card[tcard_id] = new ebg.stock();
                    this.energies_on_card[tcard_id].create(this, $('cardenergies_' + 'player_tableau_' + player_id + '_item_' + tcard_id), 25, 25);
                    for (var ress_id = 1; ress_id <= 4; ress_id++) {
                        this.energies_on_card[tcard_id].addItemType(ress_id, ress_id, g_gamethemeurl + 'img/icons.png', ress_id - 1);
                    }

                    if (toint(card_type_id) != 4 && toint(card_type_id) != 103 && toint(card_type_id) != 207) {
                        this.energies_on_card[tcard_id].setSelectionMode(0);
                    }
                }

                if (toint(original_card_type_id) == 118 && original_card_type_id != card_type_id) {
                    var html = this.getCardTooltip(card_type_id, true);
                    this.addTooltipHtml('player_tableau_' + player_id + '_item_' + tcard_id, html, 0);
                }

                if (toint(card_type_id) == 217 && original_card_type_id != card_type_id) {
                    // This is a card locked by the Argosian
                    // => add a lock on it
                    dojo.place(this.format_block('jstpl_deadlock', { id: tcard_id }), 'cardcontent_player_tableau_' + player_id + '_item_' + tcard_id);
                }

                if (toint(card_type_id) == 215) {
                    // This is a psychic cage => display a trap token on it
                    dojo.place(this.format_block('jstpl_trap', { id: tcard_id }), 'cardcontent_player_tableau_' + player_id + '_item_' + tcard_id);
                }

                // Specific: Amulet of Water
                if (toint(player_id) == this.player_id) {
                    if (toint(this.ct(card_type_id)) == 4) {
                        this.amulet_of_water_ingame[tcard_id] = 1;
                    }
                }

                // Ensure card is set with the right type
                dojo.addClass('cardcontent_player_tableau_' + player_id + '_item_' + tcard_id, 'cardtype_' + card_type_id);
            },

            showSeasonDices: function (dices, with_id) {
                // Remove all previous dices
                this.seasonDices.removeAll();

                dojo.query('.playerdie').style('display', 'none');

                for (var i in dices) {
                    var die = dices[i];
                    var die_type = die.season + '' + die.id + '' + die.face;
                    var changed = false;//todo ?
                    if (with_id) {
                        this.seasonDices.addToStockWithId(die_type, die.id);
                        this.addRollToDiv(this.seasonDices.getItemDivId(die.id), changed ? 'change' : (Math.random() > 0.5 ? 'odd' : 'even'));
                    }
                    else {
                        // During the initial game setup, show all dices without ID
                        this.seasonDices.addToStock(die_type);
                    }

                    if (die.player !== null) {
                        // Player die
                        this.giveDiceToPlayer(die_type, die.player);
                    }
                }
            },

            giveDiceToPlayer: function (dice_type, dice_player) {
                let dice = dice_type[1];
                this.updatePlayerDie(dice_type, dice_player);
                this.seasonDices.removeFromStockById(dice);
            },

            updatePlayerDie(dice_type, dice_player) {
                var dieLocation = ['playerdie_' + dice_player, 'playerdie_left_' + dice_player];
                var backgroundPos = this.getBackgroundPosition(dice_type);
                let dice = dice_type[1];

                dieLocation.forEach((loc, index) => {
                    dojo.style(loc, 'display', 'block');
                    dojo.style(loc, 'backgroundPosition', backgroundPos);
                    this.addTooltipHtml(loc, this.getDieTooltip(dice_type), _('Chosen die'));
                    if (index === 1) {   //animation only on left
                        let from = $('seasons_dices_item_' + dice) ? 'seasons_dices_item_' + dice : "generalactions";//either season die or from reroll button if the player die was already chosen
                        this.placeOnObject(loc, from);
                        this.slideToObject(loc, 'playerdie_wrap_left_' + dice_player).play();
                    }
                })
            },

            setNewFace: function (dieId, addChangeDieRoll = false) {
                const dieDiv = $(dieId);

                if (dieDiv) {
                    console.log("setNewFace", dieDiv);
                    //dieDiv.dataset.dieValue = '' + die.value;
                    const currentFace = Number(dieDiv.dataset.dieFace);
                    if (currentFace != die.face) {
                        dieDiv.dataset.dieFace = '' + die.face;

                        if (addChangeDieRoll) {
                            this.addRollToDiv(dieDiv, 'change');
                        }
                    }
                }
            },

            addRollToDiv: function (dieDiv, rollClass, attempt = 0) {
                var divElement = $(dieDiv);
                divElement.classList.remove('rolled');
                if (rollClass === 'odd' || rollClass === 'even') {
                    divElement.addEventListener('animationend', () => {
                        divElement.classList.remove('rolled');
                    })
                    setTimeout(() => divElement.classList.add('rolled'), 50);
                }

                const dieList = divElement.getElementsByClassName('die-list')[0];
                if (dieList) {
                    dieList.dataset.rollType = '-';
                    dieList.dataset.roll = divElement.dataset.dieFace;
                    setTimeout(() => dieList.dataset.rollType = rollClass, 50);
                } else if (attempt < 5) {
                    setTimeout(() => this.addRollToDiv(dieDiv, rollClass, attempt + 1), 200);
                }

                this.playSound("dice", false);
            },

            markCardActivated: function (player_id, card_id) {
                var card_div_id = 'player_tableau_' + player_id + '_item_' + card_id;
                if ($(card_div_id)) {
                    dojo.addClass(card_div_id, 'activated');

                    if ($('trap_' + card_id)) {
                        dojo.destroy('trap_' + card_id);
                    }
                }
            },

            addEnergyToPlayerStock: function (player_id, energy_id) {
                this.energies[player_id].addToStock(energy_id);
            },
            removeEnergyToPlayerStock: function (player_id, energy_id) {
                this.energies[player_id].removeFromStock(energy_id);
            },

            setReserveSize: function (player_id, reserve_size) {
                this.energies_reserve[player_id].removeAll();

                for (var i = 0; i < reserve_size; i++) {
                    this.energies_reserve[player_id].addToStock(0);
                }
            },

            placeEnergyOnCard: function (card_id, energy_id, player_id) {
                if (typeof this.energies_on_card[card_id] == 'undefined') {
                    this.showMessage("Cannot place energy on card " + card_id + ": card do not exists", "error");
                }
                else {
                    this.energies_on_card[card_id].addToStock(energy_id, $('energy_reserve_' + player_id));
                }
            },

            /** Shows available slots on a player tableau. */
            updateInvocationLevelOnSlots: function (player_id) {
                var invoc_level = toint($('invocation_level_' + player_id).innerHTML) + 1;
                dojo.query(`#underlayer_player_tableau_${player_id} .stockitem:nth-child(1n+${invoc_level})`).removeClass("ssn-loc-available");
                dojo.query(`#underlayer_player_tableau_${player_id} .stockitem:not(:nth-child(1n+${invoc_level}))`).addClass("ssn-loc-available");
            },

            ///////////////////////////////////////////////////
            //// UI actions

            onShowAgeCards: function (evt) {
                let year = dojo.attr(evt.target, "data-year");
                this.showAgeCardsPopin(year);
            },

            onDiceSelectionChanged: function (evt) {
                console.log('onDiceSelectionChanged');
                console.log(evt);

                var selected = this.seasonDices.getSelectedItems();
                if (selected.length == 1) {
                    if (this.checkAction('chooseDie')) {
                        var die_id = selected[0].id;

                        this.ajaxcall("/seasonssk/seasonssk/chooseDie.html", { die: die_id, lock: true }, this, function (result) {
                            this.seasonDices.unselectAll();
                        });
                    }
                    else {
                        this.seasonDices.unselectAll();
                    }
                }
            },

            takeAction: function (action, data) {
                data = data || {};
                data.lock = true;
                this.ajaxcall(`/seasonssk/seasonssk/${action}.html`, data, this, () => { });
            },

            takeNoLockAction: function (action, data) {
                data = data || {};
                this.ajaxcall(`/seasonssk/seasonssk/${action}.html`, data, this, () => { });
            },

            score: function () {
                this.takeAction("score");
            },

            onEndTurn: function () {
                if (this.checkAction('endTurn')) {
                    this.ajaxcall("/seasonssk/seasonssk/endTurn.html", { lock: true }, this, function (result) { });
                }
            },

            // Get energies selected
            // (included: Amulet of Water)
            getAllSelectedEnergies: function (bAmuletOnly) {
                var items = {};
                var id_string = '';

                if (!bAmuletOnly) {
                    items = this.energies[this.player_id].getSelectedItems();

                    var id_string = '';
                    for (var i in items) {
                        id_string += items[i].type + ';';
                    }
                }

                items = {};
                for (var card_id in this.amulet_of_water_ingame) {
                    if (this.amulet_of_water_ingame[card_id] == 1) {
                        items = this.energies_on_card[card_id].getSelectedItems();
                        this.energies_on_card[card_id].unselectAll();

                        for (i in items) {
                            id_string += card_id + '' + items[i].type + ';'; // Note: add the card id to items when they come from an amulet.
                        }
                    }
                }

                return id_string;
            },

            onTransmute: function () {
                if (this.checkAction('transmute')) {
                    // Get all selected energies
                    var id_string = this.getAllSelectedEnergies(false);

                    if (id_string == '') {
                        this.showMessage(_("You must select at least 1 energy to transmute"), 'error');
                        return;
                    }

                    this.ajaxcall("/seasonssk/seasonssk/transmute.html", { energies: id_string, lock: true }, this, function (result) {
                        this.energies[this.player_id].unselectAll();
                    });
                }
            },

            onDrawPowerCard: function () {
                if (this.checkAction('transmute')) {
                    this.ajaxcall("/seasonssk/seasonssk/drawPowerCard.html", { lock: true }, this, function (result) {
                    });
                }
            },

            onDiscardEnergy: function () {
                // Get all selected energies
                var id_string = this.getAllSelectedEnergies(false);

                if (id_string == '') {
                    this.showMessage(_("You must select at least 1 energy to discard"), 'error');
                    return;
                }

                if (this.checkAction('discardEnergy', true)) {
                    this.ajaxcall("/seasonssk/seasonssk/discardEnergy.html", { energies: id_string, lock: true }, this, function (result) {
                        this.energies[this.player_id].unselectAll();
                    });
                }
                else if (this.checkAction('discardEnergyEffect', true)) {
                    this.ajaxcall("/seasonssk/seasonssk/discardEnergyEffect.html", { energies: id_string, lock: true }, this, function (result) {
                        this.energies[this.player_id].unselectAll();
                    });
                }
                else if (this.checkAction('discardEnergyBonus', true)) {
                    this.ajaxcall("/seasonssk/seasonssk/discardEnergyBonus.html", { energies: id_string, lock: true }, this, function (result) {
                        this.energies[this.player_id].unselectAll();
                    });
                }

            },

            onCollectEnergy: function () {
                // Get all selected energies
                var id_string = '';

                for (var player_id in this.energies) {
                    if (player_id != this.player_id) {
                        var items = {};
                        items = this.energies[player_id].getSelectedItems();

                        for (var i in items) {
                            id_string += player_id + ',' + items[i].type + ';';
                        }
                    }
                }

                this.ajaxcall("/seasonssk/seasonssk/collectEnergy.html", { energies: id_string, lock: true }, this, function (result) {
                    for (var player_id in this.energies) {
                        this.energies[player_id].unselectAll();
                    }
                });
            },

            onChooseEnergy: function () {
                // Get all selected energies
                var id_string = this.getAllSelectedEnergies(false);

                if (this.checkAction('chooseXenergy')) {
                    this.ajaxcall("/seasonssk/seasonssk/chooseXenergy.html", { energies: id_string, lock: true }, this, function (result) {
                        this.energies[this.player_id].unselectAll();
                    });
                }
            },

            onDualChoice: function (evt) {
                if (this.checkAction('dualChoice')) {
                    // dualChoice<id>
                    var choice = evt.currentTarget.id.substr(10);
                    this.ajaxcall("/seasonssk/seasonssk/dualChoice.html", { choice: choice, lock: true }, this, function (result) {
                    });
                }
            },
            onCancel: function (evt) {
                if (this.checkAction('cancel')) {
                    this.ajaxcall("/seasonssk/seasonssk/cancel.html", { lock: true }, this, function (result) {
                    });
                }
            },
            onUseZira: function (evt) {
                if (this.checkAction('useZira')) {
                    // dualChoice<id>
                    var choice = evt.currentTarget.id.substr(10);
                    this.ajaxcall("/seasonssk/seasonssk/useZira.html", { choice: choice, lock: true }, this, function (result) {
                    });
                }
            },
            onForceUseZira: function (evt) {
                if (this.checkAction('useZira')) {
                    this.ajaxcall("/seasonssk/seasonssk/useZira.html", { choice: 1, lock: true }, this, function (result) {
                    });
                }
            },

            onKeepOrDiscard: function (evt) {
                if (this.checkAction('keepOrDiscard')) {
                    // dualChoice<id>
                    var choice = evt.currentTarget.id.substr(10);
                    this.ajaxcall("/seasonssk/seasonssk/keepOrDiscard.html", { choice: choice, lock: true }, this, function (result) {
                    });
                }
            },



            onChooseCost: function (evt) {
                if (this.checkAction('chooseCost')) {
                    var cost_id = evt.currentTarget.id.substr(4);
                    var id_string = this.getAllSelectedEnergies(false);
                    this.ajaxcall("/seasonssk/seasonssk/chooseCost.html", { cost: cost_id, energies: id_string, lock: true }, this, function (result) {
                    });
                }
            },
            onChooseCostCancel: function (evt) {
                if (this.checkAction('chooseCost')) {
                    this.ajaxcall("/seasonssk/seasonssk/chooseCostCancel.html", { lock: true }, this, function (result) {
                    });
                }
            },
            onReroll: function (evt) {
                if (this.checkAction('reroll')) {
                    var reroll = 1;
                    if (evt.currentTarget.id == 'dontreroll') { reroll = 0; }

                    this.ajaxcall("/seasonssk/seasonssk/reroll.html", { reroll: reroll, lock: true }, this, function (result) {
                    });
                }
            },

            onSteadFast: function (evt) {
                if (this.checkAction('steadFast')) {
                    // steadfast<id>
                    var action_id = evt.currentTarget.id.substr(9);
                    this.ajaxcall("/seasonssk/seasonssk/steadfast.html", { action_id: action_id, lock: true }, this, function (result) {
                    });
                }
            },

            onOrbChoice: function (evt) {
                if (this.checkAction('orbChoice')) {
                    var bReplace = 0;
                    if (evt.currentTarget.id == 'orbChoice0') { bReplace = 1; }

                    // Get all selected energies
                    var id_string = this.getAllSelectedEnergies(false);

                    this.ajaxcall("/seasonssk/seasonssk/orbChoice.html", { bReplace: bReplace, energy: id_string, lock: true }, this, function (result) {
                    });
                }
            },

            onPlayerHandSelectionChanged: function () {
                console.log('onPlayerHandSelectionChanged');

                var selected = this.playerHand.getSelection();
                if (this.checkAction('chooseLibrary', true)) {
                    // Let the player select the cards

                }
                else if (this.checkAction('amuletOfTime', true)) {
                    // Let the player select the cards

                }
                else if (this.checkAction('chooseLibrarynew', true)) {
                    // Let the player select the cards
                    if (selected.length >= 1) {
                        this.buildLibrary();
                    }
                }
                else if (selected.length == 1) {
                    var card_id = selected[0].id;
                    if (this.checkAction('summon', true)) {
                        // Summon a card
                        var id_string = this.getAllSelectedEnergies(false);
                        this.ajaxcall("/seasonssk/seasonssk/summon.html", { id: card_id, forceuse: id_string, lock: true }, this, function (result) {
                        });
                        this.playerHand.unselectAll();
                    }
                    else if (this.checkAction('chooseCardHand', true)) {
                        // Discard a card
                        this.ajaxcall("/seasonssk/seasonssk/chooseCardHand.html", { id: card_id, lock: true }, this, function (result) {
                        });
                        this.playerHand.unselectAll();
                    }
                    else if (this.checkAction('chooseCardHandcrafty', true)) {
                        // Discard a card
                        this.ajaxcall("/seasonssk/seasonssk/chooseCardHandcrafty.html", { id: card_id, lock: true }, this, function (result) {
                        });
                        this.playerHand.unselectAll();
                    }
                    else if (this.checkAction('discard', true)) {
                        // Discard a card
                        this.ajaxcall("/seasonssk/seasonssk/discard.html", { id: card_id, lock: true }, this, function (result) {
                        });
                        this.playerHand.unselectAll();
                    }
                    else {
                        this.playerHand.unselectAll();
                    }
                }
            },

            onChooseToken: function (evt) {
                if (this.checkAction('chooseToken')) {
                    var tokens = this.tokensStock[this.player_id].getSelectedItems();
                    if (tokens.length == 1) {
                        var token = tokens[0];
                        this.ajaxcall("/seasonssk/seasonssk/chooseToken.html", { tokenId: token.id, lock: true }, this, function (result) {
                        });
                    }
                    else {
                        this.showMessage(_("You must select one of your tokens first"), 'error');
                    }
                }
            },

            onPlayToken: function (evt) {
                //no this.checkAction('playToken') here because many tokens have specific moments to be played
                const selection = this.cardChoice.getSelectedItems();
                let optCardId = undefined;
                if (selection.length == 1) {
                    optCardId = selection[0].id;
                }
                this.ajaxcall("/seasonssk/seasonssk/playToken.html", { lock: true, "optCardId": optCardId }, this, function (result) {
                });

            },

            onLibraryBuildchange: function (library) {
                this.checkAction('chooseLibrarynew');

                // Select a card from a library
                this.buildLibrary();
            },

            buildLibrary: function () {
                // Depending on selection on buildLibrary & player hand, switch cards and build libraries

                var bError = false;
                var allselected = [];

                // Get selected cards. We must have a total of 2 selected cards
                for (var l = 1; l <= 3; l++) {
                    var selected = this.libraryBuild[l].getSelectedItems();
                    if (selected.length > 1) {
                        bError = true;
                    }
                    else if (selected.length == 1) {
                        allselected.push({ loc: l, id: selected[0].id, type: selected[0].type });
                    }
                }

                selected = this.playerHand.getSelection();
                console.log("selection", selected);
                if (selected.length > 1) {
                    bError = true;
                }
                else if (selected.length == 1) {
                    allselected.push({ loc: 0, id: selected[0].id, type: selected[0].type });
                }

                if (bError) {
                    // Unselected everything
                    this.buildLibraryUnselect();
                    return;
                }

                if (allselected.length > 2) {
                    bError = true;
                } else if (allselected.length == 1 && selected.length == 1) {//selection from player hand only
                    //the card goes to the first library with empty slots
                    var selectionFromHand = allselected[0];
                    if (this.libraryBuild[1].getPresentTypeList().hasOwnProperty(0)) {
                        this.libraryBuild[1].addToStockWithId(selectionFromHand.type, selectionFromHand.id, "card-" + selectionFromHand.id);
                        this.libraryBuild[1].removeFromStock(0);//removes blank
                        this.removeCardFromPlayerHand(selectionFromHand)
                    } else if (this.libraryBuild[2].getPresentTypeList().hasOwnProperty(0)) {
                        this.libraryBuild[2].addToStockWithId(selectionFromHand.type, selectionFromHand.id, "card-" + selectionFromHand.id);
                        this.libraryBuild[2].removeFromStock(0);//removes blank
                        this.removeCardFromPlayerHand(selectionFromHand)
                    } else if (this.libraryBuild[3].getPresentTypeList().hasOwnProperty(0)) {
                        this.libraryBuild[3].addToStockWithId(selectionFromHand.type, selectionFromHand.id, "card-" + selectionFromHand.id);
                        this.libraryBuild[3].removeFromStock(0);//removes blank
                        this.removeCardFromPlayerHand(selectionFromHand)
                    }
                }
                else if (allselected.length == 2) {
                    // Can perform a switch between these 2 cards

                    if (allselected[1].type != 0 || allselected[0].loc != 0) {
                        if (allselected[1].loc == 0) { var from = 'card-' + allselected[1].id; }
                        else { var from = 'library_build_' + allselected[1].loc + '_item_' + allselected[1].id; }
                        //todo handle from
                        if (allselected[0].loc == 0) { this.addCardToPlayerHand(allselected[1]); }
                        else { this.libraryBuild[allselected[0].loc].addToStockWithId(allselected[1].type, allselected[1].id, from); }
                    }

                    if (allselected[0].type != 0 || allselected[1].loc != 0) {
                        if (allselected[0].loc == 0) { var from = 'card-' + allselected[0].id; }
                        else { var from = 'library_build_' + allselected[0].loc + '_item_' + allselected[0].id; }

                        if (allselected[1].loc == 0) { this.addCardToPlayerHand(allselected[0]); }
                        else { this.libraryBuild[allselected[1].loc].addToStockWithId(allselected[0].type, allselected[0].id, from); }
                    }

                    if (allselected[1].loc == 0) { this.removeCardFromPlayerHand(allselected[1]) }
                    else { this.libraryBuild[allselected[1].loc].removeFromStockById(allselected[1].id) }

                    if (allselected[0].loc == 0) { this.removeCardFromPlayerHand(allselected[0]) }
                    else { this.libraryBuild[allselected[0].loc].removeFromStockById(allselected[0].id) }


                    this.buildLibraryUnselect();
                }


                if (bError) {
                    // Unselected everything
                    this.buildLibraryUnselect();
                    return;
                }

                if (this.playerHand.isEmpty()) {
                    $(myhand).style.display = "none";
                }
            },

            buildLibraryUnselect: function () {
                for (var l = 1; l <= 3; l++) {
                    this.libraryBuild[l].unselectAll();
                }
                this.playerHand.unselectAll();
            },

            updateScrollButtonsVisibility: function () {
                var hand = this.queryFirst("#player_hand .scrollable-stock-inner");
                if (hand.scrollWidth <= hand.clientWidth) {
                    dojo.query("#player_hand button").style("display", "none");
                } else {
                    dojo.query("#player_hand button").style("display", "inline-block");
                }
            },

            onBuildLibrary: function () {
                this.checkAction('chooseLibrary');

                var selected = this.playerHand.getSelection();
                var id_string = '';
                for (var i in selected) {
                    id_string += selected[i].id + ';';
                }

                this.ajaxcall("/seasonssk/seasonssk/chooseLibrary.html", { cards: id_string, lock: true }, this, function (result) {
                });

                this.playerHand.unselectAll();
            },

            onBuildLibraryNew: function () {
                this.checkAction('chooseLibrarynew');

                var id_string = '';

                for (var l = 1; l <= 3; l++) {
                    var library = this.libraryBuild[l].getAllItems();

                    if (library.length != 3) {
                        this.showMessage(_("You must choose 3 cards for each year"), 'error');
                        return;
                    }

                    for (var i in library) {
                        if (toint(library[i].type) == 0) {
                            this.showMessage(_("You must choose 3 cards for each year"), 'error');
                            return;
                        }

                        id_string += library[i].id + ';';
                    }
                }

                this.ajaxcall("/seasonssk/seasonssk/chooseLibrarynew.html", { cards: id_string, lock: true }, this, function (result) {
                });

                this.playerHand.unselectAll();

            },

            onAmuletOfTime: function () {
                this.checkAction('amuletOfTime');

                var selected = this.playerHand.getSelection();
                var id_string = '';
                for (var i in selected) {
                    id_string += selected[i].id + ';';
                }

                this.ajaxcall("/seasonssk/seasonssk/amuletOfTime.html", { cards: id_string, lock: true }, this, function (result) {
                });

                this.playerHand.unselectAll();

            },

            onChoiceCardsSelectionChanged: function () {
                console.log('onChoiceCardsSelectionChanged');

                var selected = this.cardChoice.getSelectedItems();
                if (selected.length == 1) {
                    var card_id = selected[0].id;
                    if (this.gamedatas.gamestate.name === "token18Effect") {
                        this.onPlayToken();
                    }
                    else if (this.checkAction('chooseCard', true)) {
                        this.ajaxcall("/seasonssk/seasonssk/chooseCard.html", { id: card_id, lock: true }, this, function (result) {
                        });
                        this.cardChoice.unselectAll();
                    }
                    else if (this.checkAction('draftChooseCard', true)) {
                        this.ajaxcall("/seasonssk/seasonssk/draftChooseCard.html", { id: card_id, lock: true }, this, function (result) {
                        });
                        this.cardChoice.unselectAll();
                    }
                    else if (this.checkAction('draftTwist', true)) {
                        this.ajaxcall("/seasonssk/seasonssk/draftTwist.html", { id: card_id, lock: true }, this, function (result) {
                        });
                        this.cardChoice.unselectAll();
                    }
                    else {
                        this.cardChoice.unselectAll();
                    }
                }
            },

            onOtusChoiceCardsSelectionChanged: function () {
                console.log('onOtusChoiceCardsSelectionChanged');

                var selected = this.otusChoice.getSelectedItems();
                if (selected.length == 1) {
                    var card_id = selected[0].id;
                    if (this.checkAction('summon', false)) {
                        // Summon a card
                        var id_string = this.getAllSelectedEnergies(false);
                        this.ajaxcall("/seasonssk/seasonssk/summon.html", { id: card_id, forceuse: id_string, lock: true }, this, function (result) {
                        });
                        this.otusChoice.unselectAll();
                    }
                    else if (this.checkAction('chooseCardHand')) // TRick for POtion of dreams + Otus oracle
                    {
                        // Summon a card
                        this.ajaxcall("/seasonssk/seasonssk/chooseCardHand.html", { id: card_id, lock: true }, this, function (result) {
                        });
                        this.otusChoice.unselectAll();
                    }
                    else {
                        this.otusChoice.unselectAll();
                    }
                }
            },


            onPowerCardActivation: function () {
                console.log('onPowerCardActivation');

                var selected = this.playerTableau[this.player_id].getSelectedItems();
                if (selected.length == 1) {
                    var card_id = selected[0].id;
                    if (card_id >= 0) {
                        if (this.checkAction('active', true)) {
                            // Active a card
                            this.ajaxcall("/seasonssk/seasonssk/active.html", { id: card_id, lock: true }, this, function (result) {
                            });
                            this.playerTableau[this.player_id].unselectAll();
                        }
                        else if (this.checkAction('sacrifice', true)) {
                            // Sacrifice a card
                            this.ajaxcall("/seasonssk/seasonssk/sacrifice.html", { id: card_id, lock: true }, this, function (result) {
                            });
                            this.playerTableau[this.player_id].unselectAll();
                        }
                        else if (this.checkAction('takeBack', true)) {
                            // Sacrifice a card
                            this.ajaxcall("/seasonssk/seasonssk/takeBack.html", { id: card_id, lock: true }, this, function (result) {
                            });
                            this.playerTableau[this.player_id].unselectAll();
                        }
                        else if (this.checkAction('chooseTableauCard', true)) {
                            // Sacrifice a card
                            this.ajaxcall("/seasonssk/seasonssk/chooseTableauCard.html", { id: card_id, lock: true }, this, function (result) {
                            });
                            this.playerTableau[this.player_id].unselectAll();
                        }
                        else {
                            this.playerTableau[this.player_id].unselectAll();
                        }
                    }
                    else {
                        this.playerTableau[this.player_id].unselectAll();
                    }
                }
            },

            onOpponentCardSelection: function (evt) {
                // Get all selected from all tableau
                for (var player_id in this.playerTableau) {
                    if (player_id != this.player_id) {
                        var selected = this.playerTableau[player_id].getSelectedItems();
                        if (selected.length == 1) {
                            var card_id = selected[0].id;
                            if (card_id >= 0) {
                                if (this.checkAction('chooseOpponentCard')) {
                                    var id_string = this.getAllSelectedEnergies(false);
                                    this.ajaxcall("/seasonssk/seasonssk/chooseOpponentCard.html", { id: card_id, forceuse: id_string, lock: true }, this, function (result) {
                                    });
                                    this.playerTableau[player_id].unselectAll();
                                }
                                else {
                                    this.playerTableau[player_id].unselectAll();
                                }
                            }
                            else {
                                this.playerTableau[player_id].unselectAll();
                            }
                        }

                    }
                }
            },

            onGainEnergy: function (evt) {
                if (this.checkAction('gainEnergy', true)) {
                    var energy_id = evt.currentTarget.id.substr(6);
                    this.ajaxcall("/seasonssk/seasonssk/gainEnergy.html", { id: energy_id, lock: true }, this, function (result) { });
                }
                else if (this.checkAction('chooseEnergyType', true)) {
                    var energy_id = evt.currentTarget.id.substr(6);
                    var id_string = this.getAllSelectedEnergies(false);
                    this.ajaxcall("/seasonssk/seasonssk/chooseEnergyType.html", { id: energy_id, energies: id_string, lock: true }, this, function (result) { });
                }
            },

            onCardEffectEnd: function () {
                this.ajaxcall("/seasonssk/seasonssk/cardEffectEnd.html", { lock: true }, this, function (result) { });
            },

            onMoveSeason: function (evt) {
                dojo.stopEvent(evt);
                if (this.checkAction('moveSeason')) {
                    // monthplace_<id>
                    var month = evt.currentTarget.id.substr(11);
                    this.ajaxcall("/seasonssk/seasonssk/moveSeason.html", { month: month, lock: true }, this, function (result) { });
                }
            },
            onUseBonus: function (evt) {
                dojo.stopEvent(evt);
                if (this.checkAction('useBonus')) {
                    // bonus<id>_playerId
                    var bonus_id = evt.currentTarget.id.split("_")[0].substr(5);
                    this.confirmationDialog(_('Are you sure to use this bonus (points penalty at the end of the game) ?'), dojo.hitch(this, function () {
                        this.ajaxcall('/seasonssk/seasonssk/useBonus.html', { id: bonus_id, lock: true }, this, function (result) { });
                    }));
                }
            },

            // Click on "choose this player" link
            onChoosePlayer: function (evt) {
                console.log('onChoosePlayer');
                evt.preventDefault();

                this.checkAction('choosePlayer');

                // choose_player_<id>
                console.log(evt);
                var player_id = evt.currentTarget.id.substr(14);
                this.ajaxcall("/seasonssk/seasonssk/choosePlayer.html", { player: player_id, lock: true }, this, function (result) { });
            },

            onDoNotUse: function (evt) {
                console.log('onDoNotUse');
                evt.preventDefault();

                this.checkAction('doNotUse');

                // choose_player_<id>
                this.ajaxcall("/seasonssk/seasonssk/doNotUse.html", { lock: true }, this, function (result) { });
            },

            onFairyMonolithActive: function (evt) {
                console.log('onFairyMonolithActive');
                evt.preventDefault();

                this.checkAction('fairyMonolithActive');

                var monolith = this.gamedatas.gamestate.args.card_id;
                this.energies_on_card

                var id_string = '';

                items = this.energies_on_card[monolith].getSelectedItems();
                this.energies_on_card[monolith].unselectAll();

                for (i in items) {
                    id_string += items[i].type + ';'; // Note: add the card id to items when they come from an amulet.
                }

                this.ajaxcall("/seasonssk/seasonssk/fairyMonolithActive.html", { energy: id_string, lock: true }, this, function (result) { });

            },


            ///////////////////////////////////////////////////
            //// Game & client states

            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName, args);
                switch (stateName) {
                    case 'nextPlayerTurn':
                        // Remove "activated" tokens
                        dojo.query('.activated').removeClass('activated');
                        break;

                    case 'amuletFireChoice':
                    case 'divineChoice':
                    case 'chaliceEternityChoice':
                    case 'nariaChoice':
                    case 'orbChoice2':
                    case 'draftChoice':
                    case 'bonusDrawChoice':
                    case 'familiarChoice':
                    case 'scrollIshtarCardChoice':
                    case 'telescopeChoice':
                    case 'statueOfEolisLook':
                    case 'resurrectionChoice':
                    case 'potionOfAncientCardChoice':
                    case 'sepulchralAmuletChoice':
                    case 'sepulchralAmuletChoice2':
                    case 'carnivoraChoice':
                    case 'draftTwist':
                    case 'token18Effect':
                        if (stateName === 'token18Effect' && this.isCurrentPlayerActive()) {
                            notif = { "args": [] };
                            notif.args.cards = args.args._private.cards;
                            this.notif_newCardChoice(notif);
                        }
                        if (this.isCurrentPlayerActive()) {
                            dojo.style('choiceCards', 'display', 'block');
                            this.cardChoice.updateDisplay();
                        }
                        break;
                    case 'temporalBoots':
                        dojo.query('.monthplace').style('cursor', 'pointer');
                        break;
                    case 'token10Effect':
                        //moves season +2 or -2
                        dojo.query('#monthplace_' + ((this.currentMonth + (this.currentMonth < 3 ? 12 : 0) - 2))).style('cursor', 'pointer');
                        dojo.query('#monthplace_' + ((this.currentMonth + 2) % 12)).style('cursor', 'pointer');
                        break;

                    case 'lewisChoice':
                        if (this.isCurrentPlayerActive()) {
                            dojo.query('.choose_opponent').style('display', 'block');
                        }
                        break;
                    case 'craftyChooseOpponent':
                        if (this.isCurrentPlayerActive()) {
                            for (var i in args.args.targets) {
                                var target_id = args.args.targets[i];
                                dojo.query('#overall_player_board_' + target_id + ' .choose_opponent').style('display', 'block');
                            }
                        }

                        break;
                    case 'buildLibrary3':
                    case 'buildLibrary2':
                        dojo.style('season_dices_wrap', 'display', 'none')
                        break;
                    case 'buildLibraryNew':
                        dojo.style('season_dices_wrap', 'display', 'none');
                        dojo.style('season_library_choice', 'display', 'block');
                        this.libraryBuild[1].updateDisplay();
                        this.libraryBuild[2].updateDisplay();
                        this.libraryBuild[3].updateDisplay();
                        dojo.style('board', 'display', 'none');
                        break;

                    case 'startYear':
                        var year = args.args.currentYear;
                        if (year < 4) {//year 4 triggers the end of game, we do not want animation there
                            var msg = _("Année ${year}");
                            dojo.place("<div id=\"new-year\"><span>" + msg.replace('${year}', '' + year) + "</span></div>", document.body);
                            var div = document.getElementById("new-year");
                            div.addEventListener('animationend', function () { return dojo.destroy(div); });
                            div.classList.add('new-year-animation');
                            //todo
                            // dojo.style('season_dices_wrap', 'display', 'block')
                        }
                        break;
                    case 'rattyNightshade':
                        for (var player_id in this.energies) {
                            if (player_id != this.player_id) {
                                this.energies[player_id].setSelectionMode(2);
                            }
                            else {
                                this.energies[player_id].setSelectionMode(0);
                            }
                        }
                        break;
                    case 'finalScoring':
                        this.onEnteringShowScore();
                        break;
                    case 'chooseToken':
                        this.tokensStock[this.player_id].setSelectionMode(1);
                        break;
                }
            },
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {
                    case 'amuletFireChoice':
                    case 'divineChoice':
                    case 'chaliceEternityChoice':
                    case 'nariaChoice':
                    case 'orbChoice2':
                    case 'draftChoice':
                    case 'bonusDrawChoice':
                    case 'familiarChoice':
                    case 'scrollIshtarCardChoice':
                    case 'telescopeChoice':
                    case 'statueOfEolisLook':
                    case 'resurrectionChoice':
                    case 'potionOfAncientCardChoice':
                    case 'sepulchralAmuletChoice':
                    case 'sepulchralAmuletChoice2':
                    case 'carnivoraChoice':
                    case 'draftTwist':
                    case 'token18Effect':
                        dojo.style('choiceCards', 'display', 'none');
                        break;
                    case 'temporalBoots':
                    case 'token10Effect':
                        dojo.query('.monthplace').style('cursor', 'auto');
                        break;
                    case 'lewisChoice':
                    case 'craftyChooseOpponent':
                        dojo.query('.choose_opponent').style('display', 'none');
                        break;
                    case 'rattyNightshade':
                        for (var player_id in this.energies) {
                            if (player_id != this.player_id) {
                                this.energies[player_id].setSelectionMode(0);
                            }
                            else {
                                this.energies[player_id].setSelectionMode(2);
                            }
                        }
                        break;

                    case 'buildLibraryNew':
                        dojo.style('season_library_choice', 'display', 'none');
                        dojo.style('board', 'display', 'block');
                        break;
                    case 'maliceDie':
                        dojo.query('.cardtype_15 .cardactivation').removeClass('cardactivation');
                        break;
                    case 'chooseToken':
                        //this.tokensStock[this.player_id].setSelectionMode(0);
                        break;
                }
            },

            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'token18Effect':
                            this.addActionButton('playToken', _('Choose selected card'), 'onPlayToken');
                            break;
                        case 'buildLibrary3':
                        case 'buildLibrary2':
                            this.addActionButton('buildLibrary', _('Choose selected cards'), 'onBuildLibrary');
                            break;
                        case 'buildLibraryNew':
                            this.addActionButton('buildLibraryDone', _('I am done'), 'onBuildLibraryNew');
                            break;
                        case 'amuletOfTime':

                            // If shield of zira in current player area => propose to discard zira
                            if ((dojo.query("#currentPlayerTablea .cardtype_113").length - dojo.query("#currentPlayerTablea .to_be_destroyed .cardtype_113").length) > 0) {
                                this.addActionButton('useZira', _('Sacrifice Shield of Zira'), 'onForceUseZira');
                            }


                            this.addActionButton('amuletOfTime', _('Discard selected cards'), 'onAmuletOfTime');
                            break;
                        case 'playerTurn':
                            // Transmutation possible ?
                            if (toint(args.transmutationPossible) > 0) {
                                var msg = _('Transmute energies');
                                var bonus = toint(args.transmutationPossible) - 1;
                                if (bonus > 0) {
                                    msg += ' (+' + bonus + ')';
                                }
                                this.addActionButton('transmute', msg, 'onTransmute');
                            }
                            //highlight cards that can be played
                            if (args.possibleCards) {
                                args.possibleCards.forEach(c => dojo.query("#card-" + c).addClass("possibleCard"));
                            }
                            if (toint(args.drawCardPossible) === 1) {
                                // Drawing a card is mandatory, so this is the only button
                                this.addActionButton('drawPowerCard', _('Draw a power card'), 'onDrawPowerCard');
                            }
                            else {
                                this.addActionButton('endTurn', _('End my turn'), 'onEndTurn');
                            }
                            break;

                        case 'keepOrDiscard':
                        case 'keepOrDiscardRagfield':
                            this.addActionButton('dualChoice1', _('Keep'), 'onKeepOrDiscard');
                            this.addActionButton('dualChoice0', _('Discard'), 'onKeepOrDiscard');
                            break;
                        case 'checkEnergy':
                        case 'bonusExchangeDiscard':
                            this.addActionButton('discardEnergy', _('Discard selected'), 'onDiscardEnergy');
                            break;

                        case 'dialDualChoice':
                            this.addActionButton('dualChoice1', _('Reroll it'), 'onDualChoice');
                            this.addActionButton('dualChoice0', _('Do nothing'), 'onDualChoice');

                            break;

                        case 'gainEnergy':
                        case 'mirrorChoose':
                        case 'bonusGainEnergy':
                        case 'scrollIshtarChoice':
                            this.addActionButton('energy1', '<div class="sicon energy1"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy2', '<div class="sicon energy2"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy3', '<div class="sicon energy3"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy4', '<div class="sicon energy4"></div>', 'onGainEnergy', false, null, 'gray');
                            break;

                        case 'elementalChoice':
                            for (var i = 1; i <= 4; i++) {
                                if (args.available[i] == 1) {
                                    this.addActionButton('energy' + i, '<div class="sicon energy' + i + '"></div>', 'onGainEnergy');
                                }
                            }
                            this.addActionButton('elementalEnd', _('Done'), 'onCardEffectEnd');
                            break;

                        case 'potionOfAncientChoice':
                            if (args.available[1] == 1) {
                                this.addActionButton('dualChoice1', _("Crystallize each energy in your reserve for 4 crystals"), 'onDualChoice');
                            }
                            if (args.available[2] == 1) {
                                this.addActionButton('dualChoice2', _("Draw two Power cards and discard one"), 'onDualChoice');
                            }
                            if (args.available[3] == 1) {
                                this.addActionButton('dualChoice3', _("Increase your summoning gauge by 2"), 'onDualChoice');
                            }
                            if (args.available[4] == 1) {
                                this.addActionButton('dualChoice4', _("Receive 4 energy tokens"), 'onDualChoice');
                            }

                            break;

                        case 'mirrorDiscard':
                            this.addActionButton('chooseEnergy', _('Choose selected energies'), 'onChooseEnergy');
                            break;
                        case 'treeOfLifeChoice':
                            this.addActionButton('dualChoice0', _('Discard 3 crystals and gain 1 energy token'), 'onDualChoice');
                            this.addActionButton('dualChoice1', _('Discard 1 energy token and you can transmute this round'), 'onDualChoice');
                            break;
                        case 'vampiricChoice':
                            this.addActionButton('dualChoice0', _('Draw a card'), 'onDualChoice');
                            this.addActionButton('dualChoice1', _('Discard a card'), 'onDualChoice');
                            break;

                        case 'wardenChoice':
                            this.addActionButton('dualChoice0', _('Each player discard 4 energies'), 'onDualChoice');
                            this.addActionButton('dualChoice1', _('Each player discard a Power Card'), 'onDualChoice');
                            break;


                        case 'summonVariableCost':
                            for (var cost_id in args.costs) {
                                var cost = args.costs[cost_id];
                                var html = '';
                                for (var ress_id in cost) {
                                    var qt = cost[ress_id];
                                    for (var j = 0; j < qt; j++) {
                                        html += '<div class="sicon energy' + ress_id + '"></div>';
                                    }
                                }
                                this.addActionButton('cost' + cost_id, html, 'onChooseCost');
                            }
                            this.addActionButton('chooseCostCancel', _('Cancel'), 'onChooseCostCancel');
                            break;

                        case 'maliceDie':
                            this.addActionButton('reroll', _('Reroll die'), 'onReroll');
                            this.addActionButton('dontreroll', _('Do not reroll'), 'onReroll');
                            break;

                        case 'crystalTitanChoice':
                            this.addActionButton('dualChoice0', _('Do not use'), 'onDualChoice');
                            break;
                        case 'escapedChoice':
                            this.addActionButton('dualChoice1', _('Activate the Escaped'), 'onDualChoice');
                            this.addActionButton('dualChoice0', _('Do not activate'), 'onDualChoice');
                            break;
                        case 'steadfastDie':
                            this.addActionButton('steadfast0', _('Do not use'), 'onSteadFast');
                            this.addActionButton('steadfast8', _('+1 to your summoning gauge'), 'onSteadFast');
                            this.addActionButton('steadfast9', _('Transmute during this turn'), 'onSteadFast');
                            this.addActionButton('steadfast1', '<div class="sicon energy1"></div>', 'onSteadFast', false, null, 'gray');
                            this.addActionButton('steadfast2', '<div class="sicon energy2"></div>', 'onSteadFast', false, null, 'gray');
                            this.addActionButton('steadfast3', '<div class="sicon energy3"></div>', 'onSteadFast', false, null, 'gray');
                            this.addActionButton('steadfast4', '<div class="sicon energy4"></div>', 'onSteadFast', false, null, 'gray');
                            break;

                        case 'orbChoice':
                            this.addActionButton('dualChoice0', _('See first card on the draw pile'), 'onDualChoice');
                            this.addActionButton('dualChoice1', _('Discard first card on the draw pile'), 'onDualChoice');
                            break;

                        case 'carnivoraChoice':
                            this.addActionButton('dualChoice0', _('Keep this card'), 'onDualChoice');
                            this.addActionButton('dualChoice1', _('Replace it at the top of the draw pile'), 'onDualChoice');
                            break;

                        case 'statueOfEolisLook':
                            this.addActionButton('dualChoice0', _('Okay, I saw it!'), 'onDualChoice');
                            break;

                        case 'orbChoice2':
                            this.addActionButton('orbChoice0', _('Replace'), 'onOrbChoice');
                            this.addActionButton('orbChoice1', _('Summon for 4 energy tokens'), 'onOrbChoice');
                            break;

                        case 'urmianChoice':
                            this.addActionButton('dualChoice0', _('Discard the card you played and apply no effect'), 'onDualChoice');
                            this.addActionButton('dualChoice1', _('Sacrifice a card'), 'onDualChoice');
                            break;

                        case 'familiarChoice':
                            this.addActionButton('dualChoice0', _('Add this card to my hand'), 'onDualChoice');
                            this.addActionButton('dualChoice1', _('Add the next Familiar card to my hand'), 'onDualChoice');
                            break;

                        case 'scrollIshtarCardChoice':
                            this.addActionButton('dualChoice0', _('Add this card to my hand'), 'onDualChoice');
                            this.addActionButton('dualChoice1', _('Add the next card to my hand'), 'onDualChoice');
                            break;

                        case 'statueOfEolisChoice':
                            this.addActionButton('energy1', '<div class="sicon energy1"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy2', '<div class="sicon energy2"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy3', '<div class="sicon energy3"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy4', '<div class="sicon energy4"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy0', _('2 crystals + look at the top card of the draw pile'), 'onGainEnergy');

                            break;

                        case 'chronoRingChoice':
                            this.addActionButton('energy1', '<div class="sicon energy1"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy2', '<div class="sicon energy2"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy3', '<div class="sicon energy3"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy4', '<div class="sicon energy4"></div>', 'onGainEnergy', false, null, 'gray');
                            this.addActionButton('energy0', _('4 crystals'), 'onGainEnergy');

                            break;

                        case 'rattyNightshade':
                            this.addActionButton('collectEnergy', _('Collect selected'), 'onCollectEnergy');
                            break;

                        case 'fairyMonolith':
                        case 'chaliceEternity':
                        case 'ravenChoice':
                            this.addActionButton('steadfast0', _('Do not use'), 'onDoNotUse');
                            break;
                        case 'fairyMonolithActive':
                            this.addActionButton('fairyMonolithActive', _('Return selected energies'), 'onFairyMonolithActive');
                            break;
                        case 'potionSacrificeChoice':
                        case 'igramulDiscard':
                            this.addActionButton('dualChoice1', _('Sacrifice Shield of Zira'), 'onUseZira');
                            this.addActionButton('dualChoice0', _('No, thanks'), 'onUseZira');
                            break;

                        case 'throneDiscard':
                        case 'wardenDiscardCard':
                        case 'vampiricDiscard':
                        case 'staffWinterDiscard':
                            // If shield of zira in current player area => propose to discard zira
                            if (dojo.query("#currentPlayerTablea .cardtype_113").length > 0) {
                                this.addActionButton('useZira', _('Sacrifice Shield of Zira'), 'onForceUseZira');
                            }
                            break;
                        case 'igramulChoice':
                            var card_types_sorted = [];
                            for (var card_id in this.gamedatas.card_types) {
                                var card = this.gamedatas.card_types[card_id];
                                if (card_id != 222)    // Filter Replica
                                {
                                    card_types_sorted.push([card_id, _(card.name)]);
                                }
                            }

                            card_types_sorted.sort(this.cmp);
                            for (var i in card_types_sorted) {
                                var card = card_types_sorted[i];
                                this.addActionButton('dualChoice' + card[0], card[1], 'onDualChoice');
                            }
                            break;
                        case 'chooseToken':
                            this.addActionButton('chooseToken', _('Choose this token'), 'onChooseToken');
                            break;
                    }

                    if (this.checkPossibleActions('placeenergyEffect')) {
                        this.addActionButton('discardEnergy', _('Choose selected energies'), 'onDiscardEnergy');
                    }
                    else if (this.checkPossibleActions('discardEnergyEffect')) {
                        this.addActionButton('discardEnergy', _('Discard selected'), 'onDiscardEnergy');
                    }

                    if (this.checkPossibleActions('cancel')) {
                        this.addActionButton('cancel', _('Cancel'), 'onCancel');
                    }
                }

                if (this.checkPossibleActions('sacrifice', true)) {
                    if ((dojo.query("#currentPlayerTablea .cardtype_113").length - dojo.query("#currentPlayerTablea .to_be_destroyed .cardtype_113").length) > 0) {
                        $('pagemaintitletext').innerHTML += ' (' + _("or use your Shield of Zira") + ')';
                    }
                }
            },

            onEnteringShowScore: function (fromReload = false) {

                document.getElementById('score').style.display = 'flex';

                const headers = document.getElementById('scoretr');
                if (!headers.childElementCount) {
                    dojo.place(`
                    <th></th>
                    <th id="th-cristals-score" class="cristals-score">${_("Score at the end of year III")}</th>
                    <th id="th-raw-cards-score" class="raw-cards-score">${_("Cards : raw score")}</th>
                    <th id="th-eog-cards-score" class="eog-cards-score">${_("Cards : end of game effects")}</th>
                    <th id="th-bonus-actions-score" class="bonus-actions-score">${_("Additional actions")}</th>
                    <th id="th-remaining-cards-score" class="remaining-cards-score">${_("Cards in hand")}</th>
                    <th id="th-token-score" class="token-score">${_("Ability token")}</th>
                    <th id="th-after-end-score" class="after-end-score">${_("Final score")}</th>
                `, headers);
                }

                const players = Object.values(this.gamedatas.players);

                players.forEach(player => {
                    //if we are a reload of end state, we display values, else we wait for notifications
                    const playerScore = fromReload ? (player) : null;

                    dojo.place(`<tr id="score${player.id}">
                    <td class="player-name" style="color: #${player.color}">${player.name}</td>
                    <td id="cristals-score${player.id}" class="score-number cristals-score">${playerScore?.cristalsScore !== undefined ? playerScore.cristalsScore : ''}</td>
                    <td id="raw-cards-score${player.id}" class="score-number raw-cards-score">${playerScore?.rawCardsScore !== undefined ? playerScore.rawCardsScore : ''}</td>
                    <td id="eog-cards-score${player.id}" class="score-number eog-cards-score">${playerScore?.eogCardsScore !== undefined ? playerScore.eogCardsScore : ''}</td>
                    <td id="bonus-actions-score${player.id}" class="score-number bonus-actions-score">${playerScore?.bonusActionsScore !== undefined ? playerScore.bonusActionsScore : ''}</td>
                    <td id="remaining-cards-score${player.id}" class="score-number remaining-cards-score">${playerScore?.remainingCardsScore !== undefined ? playerScore.remainingCardsScore : ''}</td>
                    <td id="token-score${player.id}" class="score-number token-score">${playerScore?.tokenScore !== undefined ? playerScore.tokenScore : ''}</td>
                    <td id="after-end-score${player.id}" class="score-number after-end-score total">${playerScore?.score !== undefined ? playerScore.score : ''}</td>
                </tr>`, 'score-table-body');
                });

                this.addTooltipHtmlToClass('cristals-score', _("Score before the final count."));
                this.addTooltipHtmlToClass('raw-cards-score', _("Total number of cristals visible on the left corner of the cards in play."));
                this.addTooltipHtmlToClass('eog-cards-score', _("Number of cristals awarded by end of game effects on cards in play."));
                this.addTooltipHtmlToClass('bonus-actions-score', _("Total number of malus for additional actions used"));
                this.addTooltipHtmlToClass('remaining-cards-score', _("-5 cristals per card in hand."));
                this.addTooltipHtmlToClass('token-score', _("Effect of the ability token if used."));

            },

            cmp: function (a, b) {
                return a[1].localeCompare(b[1]);
            },

            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            setupNotifications: function () {
                console.log('notifications subscriptions setup');


                dojo.subscribe('chooseDie', this, "notif_chooseDie");
                dojo.subscribe('newDices', this, "notif_newDices");
                dojo.subscribe('score', this, "notif_score");
                dojo.subscribe('resourceStockUpdate', this, "notif_resourceStockUpdate");
                dojo.subscribe('timeProgression', this, "notif_timeProgression");
                dojo.subscribe('incInvocationLevel', this, "notif_incInvocationLevel");
                dojo.subscribe('playerPickPowerCard', this, "notif_playerPickPowerCard");
                dojo.subscribe('pickPowerCard', this, "notif_pickPowerCard");
                dojo.subscribe('pickPowerCards', this, "notif_pickPowerCards");
                this.notifqueue.setSynchronous('pickPowerCards', 300);

                dojo.subscribe('winPoints', this, "notif_winPoints");
                dojo.subscribe('summon', this, "notif_summon");
                dojo.subscribe('active', this, "notif_active");
                dojo.subscribe('discardFromTableau', this, "notif_discardFromTableau");
                dojo.subscribe('reserveSizeChange', this, "notif_reserveSizeChange");
                dojo.subscribe('discard', this, "notif_discard");
                dojo.subscribe('placeEnergyOnCard', this, "notif_placeEnergyOnCard");
                dojo.subscribe('removeEnergiesOnCard', this, "notif_removeEnergiesOnCard");
                dojo.subscribe('removeEnergyOnCard', this, "notif_removeEnergyOnCard");
                dojo.subscribe('newCardChoice', this, "notif_newCardChoice");
                dojo.subscribe('newOtusChoice', this, "notif_newOtusChoice");

                dojo.subscribe('removeFromChoice', this, "notif_removeFromChoice");
                dojo.subscribe('rerollDice', this, "notif_rerollDice");
                dojo.subscribe('placeMyInLibrary', this, "notif_placeMyInLibrary");
                dojo.subscribe('placeMyInLibrarynew', this, "notif_placeMyInLibrarynew");

                dojo.subscribe('firstPlayer', this, "notif_firstPlayer");
                dojo.subscribe('updateCardCount', this, "notif_updateCardCount");
                dojo.subscribe('bonusUsed', this, "notif_bonusUsed");
                dojo.subscribe('bonusBack', this, "notif_bonusBack");
                dojo.subscribe('ravenCopy', this, "notif_ravenCopy");
                dojo.subscribe('removeLock', this, "notif_removeLock");
                dojo.subscribe('inactivateCard', this, "notif_inactivateCard");

                dojo.subscribe('rerollSeasonsDice', this, "notif_rerollSeasonsDice");
                this.notifqueue.setSynchronous('rerollSeasonsDice', 1000);

                dojo.subscribe('updateScores', this, "notif_updateScores");
                dojo.subscribe('potionOfLifeWarning', this, "notif_potionOfLifeWarning");
                dojo.subscribe('tokenUsed', this, "notif_tokenUsed");

                var _this = this;
                var notifs = [
                    ['cristalsScore', this.scoreAnimationDuration],
                    ['rawCardsScore', this.scoreAnimationDuration],
                    ['eogCardsScore', this.scoreAnimationDuration],
                    ['scoreAdditionalActions', this.scoreAnimationDuration],
                    ['scoreRemainingCards', this.scoreAnimationDuration],
                    ['tokenScore', this.scoreAnimationDuration],
                    ['scoreAfterEnd', this.scoreAnimationDuration],
                ];
                notifs.forEach(function (notif) {
                    dojo.subscribe(notif[0], _this, "notif_" + notif[0]);
                    _this.notifqueue.setSynchronous(notif[0], notif[1]);
                });
            },

            setScore: function (playerId, column, score) {
                var cell = document.getElementById("score" + playerId).getElementsByTagName('td')[column];
                cell.innerHTML = "" + score;
            },
            notif_cristalsScore: function (notif) {
                this.log('notif_cristalsScore', notif.args);
                this.setScore(notif.args.playerId, 1, notif.args.points);
            },
            notif_rawCardsScore: function (notif) {
                this.log('notif_rawCardsScore', notif.args);
                this.setScore(notif.args.playerId, 2, notif.args.points);
            },
            notif_eogCardsScore: function (notif) {
                this.log('notif_eogCardsScore', notif.args);
                this.setScore(notif.args.playerId, 3, notif.args.points);
            },
            notif_scoreAdditionalActions: function (notif) {
                this.log('notif_scoreAdditionalActions', notif.args);
                this.setScore(notif.args.playerId, 4, notif.args.points);
            },
            notif_scoreRemainingCards: function (notif) {
                this.log('notif_scoreRemainingCards', notif.args);
                this.setScore(notif.args.playerId, 5, notif.args.points);
            },
            notif_tokenScore: function (notif) {
                this.log('notif_tokenScore', notif.args);
                this.setScore(notif.args.playerId, 6, notif.args.points);
            },
            notif_scoreAfterEnd: function (notif) {
                this.log('notif_scoreAfterEnd', notif.args);
                this.setScore(notif.args.playerId, 7, notif.args.points);
            },

            notif_updateCardCount: function (notif) {
                for (var player_id in notif.args.count) {
                    $('handcount_' + player_id).innerHTML = notif.args.count[player_id];
                }
            },

            notif_placeMyInLibrary: function (notif) {
                if (toint(notif.args.player_id) == this.player_id) {
                    // Remove cards from this player hand
                    for (var i in notif.args.cards) {
                        var card = notif.args.cards[i];
                        this.library[notif.args.year].addToStockWithId(card.type, card.id, $('card-' + card.id));
                        this.removeCardFromPlayerHand(card);
                    }
                }
            },
            notif_placeMyInLibrarynew: function (notif) {
                if (toint(notif.args.player_id) == this.player_id) {
                    var bFirstCard = true;
                    for (var i in notif.args.cards) {
                        var card = notif.args.cards[i];

                        if (notif.args.year == 1) {
                            if (bFirstCard) {
                                this.playerHand.removeAll();
                                bFirstCard = false;
                            }

                            this.addCardToPlayerHand(card);
                        }
                        else {
                            this.library[notif.args.year].addToStockWithId(card.type, card.id);
                        }
                    }
                }
            },

            notif_chooseDie: function (notif) {
                console.log(notif);

                this.giveDiceToPlayer(notif.args.die_type, notif.args.player_id);
            },
            notif_newDices: function (notif) {
                console.log(notif);
                //todo add animation
                this.showSeasonDices(notif.args.dices, true);
            },
            notif_score: function (notif) {
                console.log(notif);
                this.scoreCtrl[notif.args.player_id].incValue(notif.args.points);
                console.log(this.leftPlayerBoardsCristalCounters);
                this.leftPlayerBoardsCristalCounters[notif.args.player_id].toValue(notif.args.points);
            },
            notif_resourceStockUpdate: function (notif) {
                for (var ress_id in notif.args.delta) {
                    var qt = notif.args.delta[ress_id];
                    for (var i = 0; i < qt; i++) {
                        this.addEnergyToPlayerStock(notif.args.player_id, ress_id);
                    }
                    for (i = 0; i > qt; i--) {
                        this.removeEnergyToPlayerStock(notif.args.player_id, ress_id);
                    }
                }
            },
            notif_timeProgression: function (notif) {
                if (toint(notif.args.year) == 4) {
                    notif.args.year = 3;    // Note: happened at the end of the game
                }
                this.setSeasonDate(notif.args.year, notif.args.month);
            },
            notif_incInvocationLevel: function (notif) {
                $('invocation_level_' + notif.args.player_id).innerHTML = Math.max(0, Math.min(15, toint($('invocation_level_' + notif.args.player_id).innerHTML) + toint(notif.args.nbr)));
                this.updateInvocationLevelOnSlots(notif.args.player_id);
            },
            notif_playerPickPowerCard: function (notif) {
            },
            notif_pickPowerCard: function (notif) {
                var from;
                if (notif.args.fromChoice) {
                    if ($('choiceCardsStock_item_' + notif.args.card.id)) {
                        from = $('choiceCardsStock_item_' + notif.args.card.id);
                    }
                }
                //todo handle from
                this.addCardToPlayerHand(notif.args.card);

                if (typeof from != 'undefined') {
                    // Remove from choice
                    this.cardChoice.removeFromStockById(notif.args.card.id);
                }
            },
            notif_pickPowerCards: function (notif) {
                var from;
                if (notif.args.hasOwnProperty('fromLibrary')) {
                    from = "ages";
                }
                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    //todo from
                    this.addCardToPlayerHand(card);
                }
            },
            notif_winPoints: function (notif) {
            },
            notif_updateScores: function (notif) {
                for (var player_id in notif.args.scores) {
                    this.scoreCtrl[player_id].toValue(notif.args.scores[player_id]);
                    this.leftPlayerBoardsCristalCounters[player_id].toValue(notif.args.scores[player_id]);
                }
            },

            notif_tokenChosen: function (notif) {
                console.log("notif_tokenChosen", dojo.query("#tokens_" + playerId).length);
                var playerId = notif.args.player_id;
                var tokenId = notif.args.token_id

                this.tokensStock[playerId].setSelectionMode(0);
                var tokens = this.tokensStock[playerId].getAllItems();
                tokens.forEach(token => {
                    if (token.id != tokenId) {
                        this.tokensStock[playerId].removeFromStockById(token.id);
                    }
                });
                dojo.place("tokens_" + playerId, "left_avatar_" + playerId, "replace");
                if (playerId == this.player_id) {
                    dojo.query("#tokens_" + playerId + " .stockitem").connect('click', this, 'onPlayToken');
                }
            },

            notif_summon: function (notif) {
                // Summon: card goes from player hand (if current player) or from player panel (=opponent) to player's tableau
                if (notif.args.fromTableau) {
                    // Specific: card come from another tableau
                    this.playerTableau[notif.args.player_id].addToStockWithId(this.ot(notif.args.card.type), notif.args.card.id, 'player_tableau_' + notif.args.fromTableau + '_item_' + notif.args.card.id);
                }
                else if (notif.args.fromNoWhere) {
                    this.playerTableau[notif.args.player_id].addToStockWithId(this.ot(notif.args.card.type), notif.args.card.id, 'overall_player_board_' + notif.args.player_id);
                }
                else if (notif.args.fromOtus) {
                    this.playerTableau[notif.args.player_id].addToStockWithId(this.ot(notif.args.card.type), notif.args.card.id, 'otus_item_' + notif.args.card.id);
                    this.otusChoice.removeFromStockById(notif.args.card.id);
                }
                else if (notif.args.player_id == this.player_id) {
                    this.playerTableau[notif.args.player_id].addToStockWithId(this.ot(notif.args.card.type), notif.args.card.id, 'card-' + notif.args.card.id);
                    this.removeCardFromPlayerHand(notif.args.card);
                }
                else {
                    this.playerTableau[notif.args.player_id].addToStockWithId(this.ot(notif.args.card.type), notif.args.card.id, 'overall_player_board_' + notif.args.player_id);
                }
                this.setupNewCardOnTableau(notif.args.card.type, notif.args.card.id, notif.args.player_id);
                this.updateInvocationLevelOnSlots(notif.args.player_id);
                if (this.gamedatas.card_types[notif.args.card.type].category == "f") {
                    this.playSound("familiar", false);
                }

            },
            notif_active: function (notif) {
                this.markCardActivated(notif.args.player_id, notif.args.card.id);
            },
            notif_discardFromTableau: function (notif) {
                // Discard card from tableau
                this.playerTableau[notif.args.player_id].removeFromStockById(notif.args.card_id);
                this.updateInvocationLevelOnSlots(notif.args.player_id);

                // Specific: Amulet of Water
                if (typeof this.amulet_of_water_ingame[notif.args.card_id] != 'undefined') {
                    this.amulet_of_water_ingame[notif.args.card_id] = 0;
                }
            },
            notif_reserveSizeChange: function (notif) {
                this.setReserveSize(notif.args.player_id, notif.args.reserve_size);
            },
            notif_discard: function (notif) {
                if (notif.args.player_id == this.player_id) {
                    this.removeCardFromPlayerHand({ id: notif.args.card_id });
                }
            },
            notif_placeEnergyOnCard: function (notif) {
                this.placeEnergyOnCard(notif.args.card_id, notif.args.energy_type, notif.args.player_id);
            },
            notif_removeEnergiesOnCard: function (notif) {
                this.energies_on_card[notif.args.card_id].removeAll();
            },
            notif_removeEnergyOnCard: function (notif) {
                this.energies_on_card[notif.args.card_id].removeFromStock(notif.args.energy_type);
            },
            notif_newCardChoice: function (notif) {
                this.cardChoice.removeAll();
                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    this.cardChoice.addToStockWithId(card.type, card.id);
                }
            },
            notif_newOtusChoice: function (notif) {
                dojo.style('otus_wrap', 'display', 'block');
                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    this.otusChoice.addToStockWithId(card.type, card.id);
                }
            },
            notif_removeFromChoice: function (notif) {
                this.cardChoice.removeFromStockById(notif.args.card);
            },
            notif_rerollDice: function (notif) {
                const dieType = notif.args.dice.season + notif.args.dice.id + notif.args.dice.face;
                this.playSound("dice", false);
                this.updatePlayerDie(dieType, notif.args.player_id);

            },
            notif_rerollSeasonsDice: function (notif) {
                var season = notif.args.dice.season;
                var dice = notif.args.dice.id;
                var face = notif.args.dice.face;

                var backx = 54 * (toint(dice) - 1) + (5 * 54 * (toint(season) - 1));
                var backy = 54 * (toint(face) - 1);

                dojo.style('seasons_dices_item_' + notif.args.dice.id, 'backgroundPosition', '-' + backx + 'px -' + backy + 'px');
                this.setNewFace(this.seasonDices.getItemDivId(dice), true);//to do check
                //this.addRollToDiv('seasons_dices_item_' + notif.args.dice.id, changed ? 'change' : (Math.random() > 0.5 ? 'odd' : 'even'));

            },

            notif_firstPlayer: function (notif) {
                console.log('notif_firstPlayer');
                console.log(notif);
                this.setFirstPlayer(notif.args.player_id);
            },

            notif_tokenUsed: function (notif) {
                console.log('notif_tokenUsed');
                console.log(notif);
                //todo
            },

            notif_bonusUsed: function (notif) {
                console.log('notif_bonusUsed');
                console.log(notif);
                var oldnbr = toint(notif.args.bonus_used) - 1;
                dojo.removeClass('bonusused_' + notif.args.player_id, 'bonusused' + oldnbr + " invisible");
                dojo.addClass('bonusused_' + notif.args.player_id, 'bonusused' + notif.args.bonus_used);
                dojo.query("#bonusUsedCube_" + notif.args.player_id).removeClass('bonusUsed' + oldnbr).addClass('bonusUsed' + notif.args.bonus_used);
                this.disableBonusActions(notif.args.player_id, toint(notif.args.bonus_used) == 3);
            },

            disableBonusActions: function (player_id, disable) {
                let bonusesQuery = "#leftPlayerBoard_" + player_id + " .bonus";
                if (disable) {
                    dojo.query(bonusesQuery).removeClass("enabled").forEach(element => {
                        this.addTooltip(element.id, '', _("You've already used your 3 possible bonus actions"));
                    });
                }
                else {
                    dojo.query(bonusesQuery).addClass("enabled");
                    this.addTooltip('bonus1_' + player_id, '', _('Bonus: Trade 2 energy tokens for 2 energy tokens of your choice'));
                    this.addTooltip('bonus2_' + player_id, '', _('Bonus: You can transmute energies this turn'));
                    this.addTooltip('bonus3_' + player_id, '', _('Bonus: Increase your summoning gauge by one'));
                    this.addTooltip('bonus_4' + player_id, '', _('Bonus: Instead of drawing 1 card this turn, draw 2 cards and keep one.'));
                }

                /* console.log("+++++", dojo.query("#leftPlayerBoard_" + player_id + " .bonus1").);
                 this.addTooltip(this.queryFirst("#leftPlayerBoard_" + player_id + " .bonus1"), '', _("You've already used your 3 possible bonus actions"));
                 this.addTooltip('bonus2', '', _("You've already used your 3 possible bonus actions"));
                 this.addTooltip('bonus3', '', _("You've already used your 3 possible bonus actions"));
                 this.addTooltip('bonus4', '', _("You've already used your 3 possible bonus actions"));*/
            },
            notif_bonusBack: function (notif) {
                console.log('notif_bonusBack');
                console.log(notif);
                var oldnbr = toint(notif.args.bonus_used) + 1;
                dojo.removeClass('bonusused_' + notif.args.player_id, 'bonusused' + oldnbr);
                dojo.addClass('bonusused_' + notif.args.player_id, 'bonusused' + notif.args.bonus_used);
                dojo.query("#bonusUsedCube_" + notif.args.player_id).removeClass('bonusUsed' + oldnbr).addClass('bonusUsed' + notif.args.bonus_used);
                this.disableBonusActions(notif.args.player_id, toint(notif.args.bonus_used) == 3);
            },
            notif_potionOfLifeWarning: function (notif) {
                console.log('notif_potionOfLifeWarning');
                console.log(notif);
                this.showMessage(_("Note: You cannot continue to transmute after having activated Potion of Life"), 'info');
            },
            notif_ravenCopy: function (notif) {
                this.setupNewCardOnTableau(notif.args.card_type, notif.args.card_id, notif.args.player_id);
            },
            notif_removeLock: function (notif) {
                if ($('deadlock_' + notif.args.card_id)) {
                    dojo.destroy('deadlock_' + notif.args.card_id);
                }
            },
            notif_inactivateCard: function (notif) {
                var card_div_id = 'player_tableau_' + notif.args.player_id + '_item_' + notif.args.card_id;
                if ($(card_div_id)) {
                    dojo.removeClass(card_div_id, 'activated');
                }
            }
        });
    });

