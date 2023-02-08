{OVERALL_GAME_HEADER}

<audio id="audiosrc_season_1" src="{GAMETHEMEURL}/img/season_1.mp3" autobuffer></audio>
<audio id="audiosrc_season_2" src="{GAMETHEMEURL}/img/season_2.mp3" autobuffer></audio>
<audio id="audiosrc_season_3" src="{GAMETHEMEURL}/img/season_3.mp3" autobuffer></audio>
<audio id="audiosrc_season_3" src="{GAMETHEMEURL}/img/season_4.mp3" autobuffer></audio>
<audio id="audiosrc_familiar" src="{GAMETHEMEURL}/img/familiar.mp3" autobuffer></audio>
<audio id="audiosrc_dice" src="{GAMETHEMEURL}/img/dice.mp3" autobuffer></audio>

<audio id="audiosrc_o_season_1" src="{GAMETHEMEURL}/img/season_1.ogg" autobuffer></audio>
<audio id="audiosrc_o_season_2" src="{GAMETHEMEURL}/img/season_2.ogg" autobuffer></audio>
<audio id="audiosrc_o_season_3" src="{GAMETHEMEURL}/img/season_3.ogg" autobuffer></audio>
<audio id="audiosrc_o_season_3" src="{GAMETHEMEURL}/img/season_4.ogg" autobuffer></audio>
<audio id="audiosrc_o_familiar" src="{GAMETHEMEURL}/img/familiar.ogg" autobuffer></audio>
<audio id="audiosrc_o_dice" src="{GAMETHEMEURL}/img/dice.ogg" autobuffer></audio>

<div id="seasons_container">
    <div id="score">
        <div id="tabble-wrapper">
            <table>
                <thead>
                    <tr id="scoretr"></tr>
                </thead>
                <tbody id="score-table-body">
                </tbody>
            </table>
        </div>
    </div>
    <div id="board">
        <div id="seasonHighlighter"></div>
        <div class="monthplace" id="monthplace_1"></div>
        <div class="monthplace" id="monthplace_2"></div>
        <div class="monthplace" id="monthplace_3"></div>
        <div class="monthplace" id="monthplace_4"></div>
        <div class="monthplace" id="monthplace_5"></div>
        <div class="monthplace" id="monthplace_6"></div>
        <div class="monthplace" id="monthplace_7"></div>
        <div class="monthplace" id="monthplace_8"></div>
        <div class="monthplace" id="monthplace_9"></div>
        <div class="monthplace" id="monthplace_10"></div>
        <div class="monthplace" id="monthplace_11"></div>
        <div class="monthplace" id="monthplace_12"></div>
        <div class="yearplace" id="yearplace_1"></div>
        <div class="yearplace" id="yearplace_2"></div>
        <div class="yearplace" id="yearplace_3"></div>
        <div id="current_month"></div>
        <div id="current_year"></div>
        
    </div>
    <div id="season_library_choice" style="display:none">
        <div class="seasonsblock">
            <h3>{YEAR_I}
                <div id="reset_button_1" class="reset_button" data-year="1">x</div>
            </h3>
            <div class="library_build_wrap">
                <div id="library_build_1"></div>
            </div>
        </div>
        <div class="seasonsblock">
            <h3>{CARDS_FOR_YEAR_2}
                <div id="reset_button_2" class="reset_button" data-year="2">x</div>
            </h3>
            <div class="library_build_wrap">
                <div id="library_build_2"></div>
            </div>
        </div>
        <div class="seasonsblock">
            <h3>{CARDS_FOR_YEAR_3}
                <div id="reset_button_3" class="reset_button" data-year="3">x</div>
            </h3>
            <div class="library_build_wrap">
                <div id="library_build_3"></div>
            </div>
        </div>
    </div>

    <div class="transmutation_bar seasons_rightpanel">
        <div class="head"></div>
        <div id="season_dices_wrap">
            <div>
                <h3>{LB_SEASONS_DICES}</h3>
                <div id="seasons_dices"></div>
            </div>
            <div class="conversion_reminder_wrapper">
                <h3>{LB_TRANSMUTATION_RATE}</h3>
                <div class="conversion_reminder">
                    <div id="convertFor3" class="reminder">
                        <div class="energy"></div>
                        <div class="conversion3"></div>
                    </div>
                    <div class="sicon icon_separator"></div>
                    <div id="convertFor2" class="reminder">
                        <div class="energy"></div>
                        <div class="conversion2"></div>
                    </div>
                    <div class="sicon icon_separator"></div>
                    <div id="convertFor1" class="reminder">
                        <div id="energyType1" class="sicon energy"></div>
                        <div id="energyType2" class="sicon energy"></div>
                        <div class="conversion1"></div>
                    </div>
                </div>
            </div>
            <div>
                <h3>{LB_CARDS_NUMBER}</h3>
                <div id="piles_counters">
                    <div  class="card-pile drawpile"></div>
                    <span id="draw_pile_counter" class="counter"></span>
                    <div class="card-pile discardpile"></div>
                    <span id="discard_pile_counter" class="counter"></span>
                </div>
            </div>
        </div>
        <div class="head"></div>
    </div>

    <div id="choiceCards" class="seasonsblock seasons_rightpanel">
        <h3>{LB_CARDS_DRAWN}</h3>
        <div class="block-inside-wrapper">
            <div id="choiceCardsStock"></div>
        </div>
    </div>

    <div id="myhand" class="seasons_rightpanel seasonsblock">
        <button type="button" class="left mobileOnly backward"></button>
        <h3>{LB_MY_HAND}</h3>
        <button type="button" class="right mobileOnly forward"></button>
        <div id="player_hand">
        </div>
    </div>

    <div id="abilityTokens" class="seasons_rightpanel">
        <div id="tokensStocks">
            <div id="currentPlayerTokenStock" class="token-stock seasonsblock">
                <h3>{LB_ABILITY_TOKENS} {CURRENT_PLAYER_NAME}</h3>
                <div class="block-inside-wrapper">
                    <div id="tokens_{CURRENT_PLAYER_ID}"></div>
                </div>
            </div>
            <!-- BEGIN tokens -->
            <div class="token-stock seasonsblock">
                <h3>{LB_ABILITY_TOKENS} {PLAYER_NAME}</h3>
                <div class="block-inside-wrapper">
                    <div id="tokens_{PLAYER_ID}"></div>
                </div>
            </div>
            <!-- END tokens -->
        </div>
    </div>

    <br class="clear"/>
    
    <a id="anchor_player_{CURRENT_PLAYER_ID}"></a>
    <div class="tableau" id="currentPlayerTablea">
        <div id="leftPlayerBoard_{CURRENT_PLAYER_ID}" class="leftPlayerBoard" style="background-color:#{CURRENT_PLAYER_COLOR}">
            <div class="playerNameWrapper"> 
                <h3 class="mobileOnly">{CURRENT_PLAYER_NAME}</h3>
            </div>
            <div id="board_counters">
                <div id="cristals_counter_{CURRENT_PLAYER_ID}" class="icon_cristal counter"></div>
                <div id="cards_points_counter_{CURRENT_PLAYER_ID}" class="prestige counter"></div>
            </div>
            <img id="left_avatar_{CURRENT_PLAYER_ID}" alt="" class="ssn-avatar" />
            
            <h3 class="desktopOnly">{CURRENT_PLAYER_NAME}</h3>
            
            <div class="playerdie_wrap" id="playerdie_wrap_left_{CURRENT_PLAYER_ID}">
                <div class="playerdie" id="playerdie_left_{CURRENT_PLAYER_ID}"></div>
            </div>
            <div>
                <div class="energywrapper">
                    <div id="energy_reserve_reminder_{CURRENT_PLAYER_ID}" class="energy_reserve"></div>
                    <div id="energies_reminder_{CURRENT_PLAYER_ID}" class="energies"></div>
                </div>
            </div>
            <div class="bonus-progress">
                <div id="bonusUsedCube_{CURRENT_PLAYER_ID}" class="sicon icon_black_cube bonusUsedCube"></div>
                <div class="bonus_actions_used">
                    <div id="bonusUsed1_{CURRENT_PLAYER_ID}" class="sicon bonusused bonusused0 ttbonusused"></div>
                    <div id="bonusUsed2_{CURRENT_PLAYER_ID}" class="sicon bonusused bonusused1 ttbonusused"></div>
                    <div id="bonusUsed3_{CURRENT_PLAYER_ID}" class="sicon bonusused bonusused2 ttbonusused"></div>
                    <div id="bonusUsed4_{CURRENT_PLAYER_ID}" class="sicon bonusused bonusused3 ttbonusused"></div>
                </div>
            </div>
            <div class="bonus_actions">
                <div id="bonus1_{CURRENT_PLAYER_ID}" class="bonus bonus1"></div>
                <div id="bonus2_{CURRENT_PLAYER_ID}" class="bonus bonus2"></div>
                <div id="bonus3_{CURRENT_PLAYER_ID}" class="bonus bonus3"></div>
                <div id="bonus4_{CURRENT_PLAYER_ID}" class="bonus bonus4"></div>
            </div>
        </div>
        <div class="stock-wrapper">
            <div id="underlayer_player_tableau_{CURRENT_PLAYER_ID}" class="underlayer-tableau"></div>
            <div id="player_tableau_{CURRENT_PLAYER_ID}"></div>
        </div>
        <div id="ages" class="ages">
            <div class="age age2" data-year="2"></div>
            <div class="age age3" data-year="3"></div>
        </div>
    </div>
    <div class="anchor-up">
        <a href="#">
            <svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="1280.000000pt" height="1280.000000pt" viewBox="0 0 1280.000000 1280.000000" preserveAspectRatio="xMidYMid meet">
                <g transform="translate(0.000000,1280.000000) scale(0.100000,-0.100000)"
                fill="#000000" stroke="none">
                <path d="M6305 12787 c-74 -19 -152 -65 -197 -117 -30 -34 -786 -1537 -3070
                -6105 -2924 -5849 -3029 -6062 -3035 -6126 -15 -173 76 -326 237 -403 59 -27
                74 -30 160 -30 79 1 104 5 150 26 30 13 1359 894 2953 1956 l2897 1932 2897
                -1932 c1594 -1062 2923 -1943 2953 -1957 47 -21 70 -25 150 -25 86 0 101 3
                160 30 36 17 86 50 111 72 88 79 140 223 124 347 -6 51 -383 811 -3040 6125
                -2901 5801 -3036 6069 -3082 6110 -100 90 -246 128 -368 97z"/>
                </g>
            </svg>
        </a>
    </div>

    <!-- BEGIN player -->
    <a id="anchor_player_{PLAYER_ID}"></a>
    <div class="tableau">
        <div id="leftPlayerBoard_{PLAYER_ID}" class="leftPlayerBoard" style="background-color:#{PLAYER_COLOR}">
            <div class="playerNameWrapper"> 
                <h3 class="mobileOnly">{PLAYER_NAME}</h3>
            </div>
            <div id="board_counters">
                <div id="cristals_counter_{PLAYER_ID}" class="icon_cristal counter"></div>
                <div id="cards_points_counter_{PLAYER_ID}" class="prestige counter"></div>
            </div>
            <img id="left_avatar_{PLAYER_ID}" alt="" class="ssn-avatar" />
            <h3 class="desktopOnly">{PLAYER_NAME}</h3>
            <div class="playerdie_wrap" id="playerdie_wrap_left_{PLAYER_ID}">
                <div class="playerdie" id="playerdie_left_{PLAYER_ID}"></div>
            </div>
            <div>
                <div class="energywrapper">
                    <div id="energy_reserve_reminder_{PLAYER_ID}" class="energy_reserve"></div>
                    <div id="energies_reminder_{PLAYER_ID}" class="energies"></div>
                </div>
            </div>
            <div class="bonus-progress">
                <div id="bonusUsedCube_{PLAYER_ID}" class="sicon icon_black_cube bonusUsedCube"></div>
                <div class="bonus_actions_used">
                    <div id="bonusUsed1_{PLAYER_ID}" class="sicon bonusused bonusused0 ttbonusused"></div>
                    <div id="bonusUsed2_{PLAYER_ID}" class="sicon bonusused bonusused1 ttbonusused"></div>
                    <div id="bonusUsed3_{PLAYER_ID}" class="sicon bonusused bonusused2 ttbonusused"></div>
                    <div id="bonusUsed4_{PLAYER_ID}" class="sicon bonusused bonusused3 ttbonusused"></div>
                </div>
            </div>
            <div class="bonus_actions">
                <div id="bonus1_{PLAYER_ID}" class="bonus bonus1"></div>
                <div id="bonus2_{PLAYER_ID}" class="bonus bonus2"></div>
                <div id="bonus3_{PLAYER_ID}" class="bonus bonus3"></div>
                <div id="bonus4_{PLAYER_ID}" class="bonus bonus4"></div>
            </div>
        </div>
        <div class="stock-wrapper">
            <div id="underlayer_player_tableau_{PLAYER_ID}" class="underlayer-tableau"></div>
            <div id="player_tableau_{PLAYER_ID}"></div>
        </div>
    </div>
    <div class="anchor-up">
        <a href="#">
            <svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="1280.000000pt" height="1280.000000pt" viewBox="0 0 1280.000000 1280.000000" preserveAspectRatio="xMidYMid meet">
                <g transform="translate(0.000000,1280.000000) scale(0.100000,-0.100000)"
                fill="#000000" stroke="none">
                <path d="M6305 12787 c-74 -19 -152 -65 -197 -117 -30 -34 -786 -1537 -3070
                -6105 -2924 -5849 -3029 -6062 -3035 -6126 -15 -173 76 -326 237 -403 59 -27
                74 -30 160 -30 79 1 104 5 150 26 30 13 1359 894 2953 1956 l2897 1932 2897
                -1932 c1594 -1062 2923 -1943 2953 -1957 47 -21 70 -25 150 -25 86 0 101 3
                160 30 36 17 86 50 111 72 88 79 140 223 124 347 -6 51 -383 811 -3040 6125
                -2901 5801 -3036 6069 -3082 6110 -100 90 -246 128 -368 97z"/>
                </g>
            </svg>
        </a>
    </div>
    <!-- END player -->

    <div class="seasonsblock" id="otus_wrap">
        <h3>{OTUS_TITLE}:</h3>
        <div class="block-inside-wrapper">
            <div id="otus"></div>
        </div>
    </div>
    
    
</div>
<script type="text/javascript">

// Templates
var jstpl_player_board = '<div class="clear">\
        <div class="playerdie_wrap" id="playerdie_wrap_${player.id}">\
            <div class="playerdie" id="playerdie_${player.id}"></div>\
        </div>\
        <div class="boardblock_seasons">\
            <div class="energywrapper">\
                <div id="energy_reserve_${player.id}" class="energy_reserve"></div>\
                <div id="energies_${player.id}" class="energies"></div>\
            </div>\
        </div>\
        <div id="boardblock_additional_info_${player.id}" class="boardblock_additional_info">\
            <div><div id="tinvocationlevel_${player.id}" class="sicon invocation_level imgtext tinvocationlevel" ></div><span id="invocation_level_${player.id}" class="tinvocationlevel">0</span><span class="ssn-info">${maxInfo}</span></div>\
            <div><div id="handcounticon_${player.id}" class="hand imgtext"></div><span id="handcount_${player.id}" class="tthand">0</span></div>\
            <div id="bonusused_${player.id}" class="sicon bonusused bonusused${player.nb_bonus} imgtext ttbonusused"></div>\
            <div id="firstplayer_${player.id}" class="firstplayerplace"></div>\
            <div class="show-player-tableau"><a href="#anchor_player_${player.id}">\
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 85.333343 145.79321">\
                    <path fill="currentColor" d="M 1.6,144.19321 C 0.72,143.31321 0,141.90343 0,141.06039 0,140.21734 5.019,125.35234 11.15333,108.02704 L 22.30665,76.526514 14.626511,68.826524 C 8.70498,62.889705 6.45637,59.468243 4.80652,53.884537 0.057,37.810464 3.28288,23.775161 14.266011,12.727735 23.2699,3.6711383 31.24961,0.09115725 42.633001,0.00129225 c 15.633879,-0.123414 29.7242,8.60107205 36.66277,22.70098475 8.00349,16.263927 4.02641,36.419057 -9.54327,48.363567 l -6.09937,5.36888 10.8401,30.526466 c 5.96206,16.78955 10.84011,32.03102 10.84011,33.86992 0,1.8389 -0.94908,3.70766 -2.10905,4.15278 -1.15998,0.44513 -19.63998,0.80932 -41.06667,0.80932 -28.52259,0 -39.386191,-0.42858 -40.557621,-1.6 z M 58.000011,54.483815 c 3.66666,-1.775301 9.06666,-5.706124 11.99999,-8.735161 l 5.33334,-5.507342 -6.66667,-6.09345 C 59.791321,26.035633 53.218971,23.191944 43.2618,23.15582 33.50202,23.12041 24.44122,27.164681 16.83985,34.94919 c -4.926849,5.045548 -5.023849,5.323672 -2.956989,8.478106 3.741259,5.709878 15.032709,12.667218 24.11715,14.860013 4.67992,1.129637 13.130429,-0.477436 20,-3.803494 z m -22.33337,-2.130758 c -2.8907,-1.683676 -6.3333,-8.148479 -6.3333,-11.893186 0,-11.58942 14.57544,-17.629692 22.76923,-9.435897 8.41012,8.410121 2.7035,22.821681 -9,22.728685 -2.80641,-0.0223 -6.15258,-0.652121 -7.43593,-1.399602 z m 14.6667,-6.075289 c 3.72801,-4.100734 3.78941,-7.121364 0.23656,-11.638085 -2.025061,-2.574448 -3.9845,-3.513145 -7.33333,-3.513145 -10.93129,0 -13.70837,13.126529 -3.90323,18.44946 3.50764,1.904196 7.30574,0.765377 11,-3.29823 z m -11.36999,0.106494 c -3.74071,-2.620092 -4.07008,-7.297494 -0.44716,-6.350078 3.2022,0.837394 4.87543,-1.760912 2.76868,-4.29939 -1.34051,-1.615208 -1.02878,-1.94159 1.85447,-1.94159 4.67573,0 8.31873,5.36324 6.2582,9.213366 -1.21644,2.27295 -5.30653,5.453301 -7.0132,5.453301 -0.25171,0 -1.79115,-0.934022 -3.42099,-2.075605 z"></path>\
                </svg>\
                </a>\
            </div>\
        </div>\
    </div>';

var jstpl_token_tooltip = '<div class="tokentooltip">\
                                ${text}\
                                <br/><br/><hr/>\
                                ${points} <img src="{THEMEURL}img/common/point.png" alt="points"/> \
                                 <br/><br/>\
                                <div class="tokenart" style="background-position: ${backPos}"></div>\
                          </div>';

var jstpl_card_content = '<div class="cardcontent cardtype_${type} thickness" id="cardcontent_${id}">\
                            <div class="cardtitle">${name}</div>\
                            <div class="${cardactivation}"></div>\
                            <div id="cardenergies_${id}" class="cardenergies"></div>\
                         </div>';

var jstpl_card_tooltip = '<div class="cardtooltip">\
                            <h3>${named}</h3>\
                            <hr/>\
                            ${costd}<br\>\
                            ${text}\
                            <br/><br/>\
                            ${points} <img src="{THEMEURL}img/common/point.png" alt="points"/> &bull; \
                            ${categoryd}\
                            <div class="cardartwrap">\
                                <div class="cardart" style="background-position: -${artx}px -${arty}px; position:relative;">\
                                    <span class="cardtitle">${named}</span>\
                                </div>\
                            </div>\
                          </div>';

var jstpl_die_tooltip = '<div class="dietooltip"><ul>\
                            ${nrj}\
                            ${summon}\
                            ${card}\
                            ${points}\
                            ${transmute}\
                            ${timeprogress}\
                            <hr/>\
                            <div class="all_die_faces">\
                                <h3>${all_die_faces}:</h3>\
                                <div class="all_die_face" style="background-position: -${dicex}px -0px"></div>\
                                <div class="all_die_face" style="background-position: -${dicex}px -54px"></div>\
                                <div class="all_die_face" style="background-position: -${dicex}px -108px"></div>\
                                <div class="all_die_face" style="background-position: -${dicex}px -162px"></div>\
                                <div class="all_die_face" style="background-position: -${dicex}px -216px"></div>\
                                <div class="all_die_face" style="background-position: -${dicex}px -270px"></div>\
                            </div>\
                          </ul></div>';

var jstpl_firstplayer = '<div id="firstplayer"></div>';
var jstpl_deadlock = '<div id="deadlock_${id}" class="deadlock"></div>';
var jstpl_trap = '<div id="trap_${id}" class="trap"></div>';

var jstpl_year2= '<div class="seasonsblock" id="library_2_wrap">\
        <h3>${title}</h3>\
        <div>\
            <div id="library_2"></div>\
        </div>\
    </div>';
var jstpl_year3= '<div class="seasonsblock" id="library_3_wrap">\
        <h3>${title}</h3>\
        <div>\
            <div id="library_3"></div>\
        </div>\
    </div>';
var jstpl_opponent_hand='\
    <div class="seasons_rightpanel opponent-hand">\
        <h3>${playerName}</h3>\
        <div id="opponent_hand_${playerId}">\
        </div>\
    </div>';
var jstpl_bonus_action_exchange_bar='\
    <div id="bonus_action_exchange_wrapper" class="generalactionscontent">\
    </div>';

var jstpl_choose_player='<a href="#" id="choose_player_${player.id}"  class="choose_player button ${player.choose_opponent}"><span>{LB_CHOOSE_THIS_PLAYER}</span></a>';
var jstpl_down_arrow='\
    <div id="goToCurrentPlayer" class="show-player-tableau">\
        <a href="#anchor_player_${player_id}">\
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 85.333343 145.79321">\
                <path fill="currentColor" d="M 1.6,144.19321 C 0.72,143.31321 0,141.90343 0,141.06039 0,140.21734 5.019,125.35234 11.15333,108.02704 L 22.30665,76.526514 14.626511,68.826524 C 8.70498,62.889705 6.45637,59.468243 4.80652,53.884537 0.057,37.810464 3.28288,23.775161 14.266011,12.727735 23.2699,3.6711383 31.24961,0.09115725 42.633001,0.00129225 c 15.633879,-0.123414 29.7242,8.60107205 36.66277,22.70098475 8.00349,16.263927 4.02641,36.419057 -9.54327,48.363567 l -6.09937,5.36888 10.8401,30.526466 c 5.96206,16.78955 10.84011,32.03102 10.84011,33.86992 0,1.8389 -0.94908,3.70766 -2.10905,4.15278 -1.15998,0.44513 -19.63998,0.80932 -41.06667,0.80932 -28.52259,0 -39.386191,-0.42858 -40.557621,-1.6 z M 58.000011,54.483815 c 3.66666,-1.775301 9.06666,-5.706124 11.99999,-8.735161 l 5.33334,-5.507342 -6.66667,-6.09345 C 59.791321,26.035633 53.218971,23.191944 43.2618,23.15582 33.50202,23.12041 24.44122,27.164681 16.83985,34.94919 c -4.926849,5.045548 -5.023849,5.323672 -2.956989,8.478106 3.741259,5.709878 15.032709,12.667218 24.11715,14.860013 4.67992,1.129637 13.130429,-0.477436 20,-3.803494 z m -22.33337,-2.130758 c -2.8907,-1.683676 -6.3333,-8.148479 -6.3333,-11.893186 0,-11.58942 14.57544,-17.629692 22.76923,-9.435897 8.41012,8.410121 2.7035,22.821681 -9,22.728685 -2.80641,-0.0223 -6.15258,-0.652121 -7.43593,-1.399602 z m 14.6667,-6.075289 c 3.72801,-4.100734 3.78941,-7.121364 0.23656,-11.638085 -2.025061,-2.574448 -3.9845,-3.513145 -7.33333,-3.513145 -10.93129,0 -13.70837,13.126529 -3.90323,18.44946 3.50764,1.904196 7.30574,0.765377 11,-3.29823 z m -11.36999,0.106494 c -3.74071,-2.620092 -4.07008,-7.297494 -0.44716,-6.350078 3.2022,0.837394 4.87543,-1.760912 2.76868,-4.29939 -1.34051,-1.615208 -1.02878,-1.94159 1.85447,-1.94159 4.67573,0 8.31873,5.36324 6.2582,9.213366 -1.21644,2.27295 -5.30653,5.453301 -7.0132,5.453301 -0.25171,0 -1.79115,-0.934022 -3.42099,-2.075605 z"></path>\
            </svg>\
        </a>\
    </div>';

</script>  

{OVERALL_GAME_FOOTER}
