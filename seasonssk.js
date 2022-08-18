/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SeasonsSK implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * seasonssk.js
 *
 * SeasonsSK user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
    function (dojo, declare) {
        return declare("bgagame.seasonssk", ebg.core.gamegui, {
            constructor: function () {
                console.log('seasonssk constructor');

                // Here, you can init the global variables of your user interface
                // Example:
                // this.myGlobalValue = 0;

                this.isDebug = window.location.host == 'studio.boardgamearena.com';
                this.log = this.isDebug ? console.log.bind(window.console) : function () { };
                this.ANIMATION_MS = 500;
                this.SCORE_MS = 1500;
            },

            /*
                setup:
                
                This method must set up the game user interface according to current game situation specified
                in parameters.
                
                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refreshes the game page (F5)
                
                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */

            setup: function (gamedatas) {
                console.log("Starting game setup");

                // Setting up player boards
                for (var player_id in gamedatas.players) {
                    var player = gamedatas.players[player_id];

                    // TODO: Setting up players boards if needed
                }

                if (Number(gamedatas.gamestate.id) >= 80) { // score or end
                    this.onEnteringShowScore(true);

                }
                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);

                switch (stateName) {


                    case 'endScore':
                        this.onEnteringShowScore();
                        break;
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
                    <th id="th-after-end-score" class="after-end-score">${_("Final score")}</th>
                `, headers);
                }

                const players = Object.values(this.gamedatas.players);
                if (players.length == 1) {
                    players.push(this.gamedatas.tom);
                }

                players.forEach(player => {
                    //if we are a reload of end state, we display values, else we wait for notifications
                    const playerScore = fromReload ? (player) : null;

                    const bonusActionsScore = fromReload && Number(player.id) > 0 ? (this.fireflyCounters[player.id].getValue() >= this.companionCounters[player.id].getValue() ? 10 : 0) : undefined;
                    const remainingCardsScore = fromReload ? this.footprintCounters[player.id].getValue() : undefined;

                    dojo.place(`<tr id="score${player.id}">
                    <td class="player-name" style="color: #${player.color}">${player.name}</td>
                    <td id="cristals-score${player.id}" class="score-number cristals-score">${playerScore?.cristalsScore !== undefined ? playerScore.cristalsScore : ''}</td>
                    <td id="raw-cards-score${player.id}" class="score-number raw-cards-score">${playerScore?.rawCardsScore !== undefined ? playerScore.rawCardsScore : ''}</td>
                    <td id="eog-cards-score${player.id}" class="score-number eog-cards-score">${playerScore?.eogCardsScore !== undefined ? playerScore.eogCardsScore : ''}</td>
                    <td id="bonus-actions-score${player.id}" class="score-number bonus-actions-score">${bonusActionsScore !== undefined ? bonusActionsScore : ''}</td>
                    <td id="remaining-cards-score${player.id}" class="score-number remaining-cards-score">${remainingCardsScore !== undefined ? remainingCardsScore : ''}</td>
                    <td id="after-end-score${player.id}" class="score-number after-end-score total">${playerScore?.scoreAfterEnd !== undefined ? playerScore.scoreAfterEnd : ''}</td>
                </tr>`, 'score-table-body');
                });

                this.addTooltipHtmlToClass('cristals-score', _("Score before the final count."));
                this.addTooltipHtmlToClass('raw-cards-score', _("Total number of cristals visible on the left corner of the cards in play."));
                this.addTooltipHtmlToClass('eog-cards-score', _("Number of cristals awarded by end of game effects on cards in play."));
                this.addTooltipHtmlToClass('bonus-actions-score', _("Total number of malus for additional actions used"));
                this.addTooltipHtmlToClass('remaining-cards-score', _("-5 cristals per card in hand."));

            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {

                    /* Example:
                    
                    case 'myGameState':
                    
                        // Hide the HTML block we are displaying only during this game state
                        dojo.style( 'my_html_block_id', 'display', 'none' );
                        
                        break;
                   */


                    case 'dummmy':
                        break;
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //        
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        /*               
                                         Example:
                         
                                         case 'myGameState':
                                            
                                            // Add 3 action buttons in the action status bar:
                                            
                                            this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                                            this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                                            this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                                            break;
                        */
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*
            
                Here, you can defines some utility methods that you can use everywhere in your javascript
                script.
            
            */


            ///////////////////////////////////////////////////
            //// Player's action

            /*
            
                Here, you are defining methods to handle player's action (ex: results of mouse click on 
                game objects).
                
                Most of the time, these methods:
                _ check the action is possible at this game state.
                _ make a call to the game server
            
            */
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
            /* Example:
            
            onMyMethodToCall1: function( evt )
            {
                console.log( 'onMyMethodToCall1' );
                
                // Preventing default browser reaction
                dojo.stopEvent( evt );
    
                // Check that this action is possible (see "possibleactions" in states.inc.php)
                if( ! this.checkAction( 'myAction' ) )
                {   return; }
    
                this.ajaxcall( "/seasonssk/seasonssk/myAction.html", { 
                                                                        lock: true, 
                                                                        myArgument1: arg1, 
                                                                        myArgument2: arg2,
                                                                        ...
                                                                     }, 
                             this, function( result ) {
                                
                                // What to do after the server call if it succeeded
                                // (most of the time: nothing)
                                
                             }, function( is_error) {
    
                                // What to do after the server call in anyway (success or failure)
                                // (most of the time: nothing)
    
                             } );        
            },        
            
            */


            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:
                
                In this method, you associate each of your game notifications with your local method to handle it.
                
                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your seasonssk.game.php file.
            
            */
            setupNotifications: function () {
                console.log('notifications subscriptions setup');

                // TODO: here, associate your game notifications with local methods

                // Example 1: standard notification handling
                // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

                // Example 2: standard notification handling + tell the user interface to wait
                //            during 3 seconds after calling the method in order to let the players
                //            see what is happening in the game.
                // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
                // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
                //

                var _this = this;
                var notifs = [
                    ['cristalsScore', this.SCORE_MS],
                    ['rawCardsScore', this.SCORE_MS],
                    ['eogCardsScore', this.SCORE_MS],
                    ['scoreFireflies', this.SCORE_MS],
                    ['scoreFootprints', this.SCORE_MS],
                    ['scoreAfterEnd', this.SCORE_MS],
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
            notif_scoreFireflies: function (notif) {
                this.log('notif_scoreFireflies', notif.args);
                this.setScore(notif.args.playerId, 4, notif.args.points);
            },
            notif_scoreFootprints: function (notif) {
                this.log('notif_scoreFootprints', notif.args);
                this.setScore(notif.args.playerId, 5, notif.args.points);
            },
            notif_scoreAfterEnd: function (notif) {
                this.log('notif_scoreAfterEnd', notif.args);
                this.setScore(notif.args.playerId, 6, notif.args.points);
            },

            // TODO: from this point and below, you can write your game notifications handling methods

            /*
            Example:
            
            notif_cardPlayed: function( notif )
            {
                console.log( 'notif_cardPlayed' );
                console.log( notif );
                
                // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
                
                // TODO: play the card in the user interface.
            },    
            
            */
        });
    });
