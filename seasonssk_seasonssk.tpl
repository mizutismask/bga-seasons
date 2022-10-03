{OVERALL_GAME_HEADER}
<div id="seasons_container">
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
        <div class="whiteblock">
            <h3>{YEAR_I}</h3>
            <div class="library_build_wrap">
                <div id="library_build_1"></div>
            </div>
        </div>
        <div class="whiteblock">
            <h3>{CARDS_FOR_YEAR_2}</h3>
            <div class="library_build_wrap">
                <div id="library_build_2"></div>
            </div>
        </div>
        <div class="whiteblock">
            <h3>{CARDS_FOR_YEAR_3}</h3>
            <div class="library_build_wrap">
                <div id="library_build_3"></div>
            </div>
        </div>
    </div>


    <div id="season_dices_wrap" class="whiteblock seasons_rightpanel">
        <h3>{LB_SEASONS_DICES}</h3>
        <h3>{LB_TRANSMUTATION_RATE}</h3>
        <div id="seasons_dices"></div>
        <div class="conversion_reminder">
            <div id="convertFor3" class="reminder">
                <div class="sicon"></div>
                <div class="sicon icon_cristal counter">3</div>
            </div>
            <div class="sicon icon_separator"></div>
             <div id="convertFor2" class="reminder">
                <div class="sicon"></div>
                <div class="sicon icon_cristal counter">2</div>
            </div>
             <div class="sicon icon_separator"></div>
             <div id="convertFor1" class="reminder">
                <div id="energyType1" class="sicon energy"></div>
                <div id="energyType2" class="sicon energy"></div>
                <div class="sicon icon_cristal counter">1</div>
            </div>
        </div>
    </div>

    <div id="abilityTokens" class="whiteblock seasons_rightpanel">
        <h3>{LB_ABILITY_TOKENS}</h3>
        <div id="tokensStocks">
            <!-- BEGIN tokens -->
            <div class="token-stock">
                <h3>{PLAYER_NAME}</h3>
                <div id="tokens_{PLAYER_ID}"></div>
            </div>
            <!-- END tokens -->
        </div>
    </div>

    <div id="choiceCards" class="whiteblock seasons_rightpanel">
        <h3>{LB_CARDS_DRAWN}</h3>
        <div id="choiceCardsStock">
        </div>
    </div>

    <div id="myhand" class="seasons_rightpanel">
        <h3>{LB_MY_HAND}</h3>
        <div id="player_hand">
        </div>
    </div>

    <br class="clear"/>


    <div class="whiteblock tableau" id="currentPlayerTablea">
        <div id="leftPlayerBoard_{CURRENT_PLAYER_ID}" class="leftPlayerBoard" style="background-color:#{CURRENT_PLAYER_COLOR}">
            <div id="cristals_counter_{CURRENT_PLAYER_ID}" class="sicon icon_cristal counter"></div>
            <div id="cards_points_counter_{CURRENT_PLAYER_ID}" class="sicon icon_play counter"></div>
            <img id="left_avatar_{CURRENT_PLAYER_ID}" alt="" class="ssn-avatar" />
            <div class="playerdie_wrap" id="playerdie_wrap_left_{CURRENT_PLAYER_ID}">
                <div class="playerdie" id="playerdie_left_{CURRENT_PLAYER_ID}"></div>
            </div>
            <h3>{CURRENT_PLAYER_NAME}</h3>
            <div id="bonusUsedCube_{CURRENT_PLAYER_ID}" class="sicon icon_black_cube bonusUsedCube"></div>
            <div class="bonus_actions_used">
                <div id="bonusUsed1_{CURRENT_PLAYER_ID}" class="sicon bonusused bonusused0 ttbonusused"></div>
                <div id="bonusUsed2_{CURRENT_PLAYER_ID}" class="sicon bonusused bonusused1 ttbonusused"></div>
                <div id="bonusUsed3_{CURRENT_PLAYER_ID}" class="sicon bonusused bonusused2 ttbonusused"></div>
                <div id="bonusUsed4_{CURRENT_PLAYER_ID}" class="sicon bonusused bonusused3 ttbonusused"></div>
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

    <!-- BEGIN player -->
    <div class="whiteblock tableau">
        <div id="leftPlayerBoard_{PLAYER_ID}" class="leftPlayerBoard" style="background-color:#{PLAYER_COLOR}">
            <div id="cristals_counter_{PLAYER_ID}" class="sicon icon_cristal counter"></div>
            <div id="cards_points_counter_{PLAYER_ID}" class="sicon icon_play counter"></div>
            <img id="left_avatar_{PLAYER_ID}" alt="" class="ssn-avatar" />
            <div class="playerdie_wrap" id="playerdie_wrap_left_{PLAYER_ID}">
                <div class="playerdie" id="playerdie_left_{PLAYER_ID}"></div>
            </div>
            <h3>{PLAYER_NAME}</h3>
            <div id="bonusUsedCube_{PLAYER_ID}" class="sicon icon_black_cube bonusUsedCube"></div>
            <div class="bonus_actions_used">
                <div id="bonusUsed1_{PLAYER_ID}" class="sicon bonusused bonusused0 ttbonusused"></div>
                <div id="bonusUsed2_{PLAYER_ID}" class="sicon bonusused bonusused1 ttbonusused"></div>
                <div id="bonusUsed3_{PLAYER_ID}" class="sicon bonusused bonusused2 ttbonusused"></div>
                <div id="bonusUsed4_{PLAYER_ID}" class="sicon bonusused bonusused3 ttbonusused"></div>
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
    <!-- END player -->

    <div class="whiteblock" id="otus_wrap">
        <h3>{OTUS_TITLE}:</h3>
        <div id="otus"></div>
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
        <div class="boardblock_seasons">\
            <div id="tinvocationlevel_${player.id}" class="sicon invocation_level imgtext tinvocationlevel" ></div><span id="invocation_level_${player.id}" class="tinvocationlevel">0</span><span class="ssn-info">${maxInfo}</span>\
            <div id="handcounticon_${player.id}" class="icon16 icon16_hand tthand"></div><span id="handcount_${player.id}" class="tthand">0</span>\
            <div id="bonusused_${player.id}" class="sicon bonusused bonusused${player.nb_bonus} imgtext ttbonusused"></div>\
            <div class="firstplayerplace" id="firstplayer_${player.id}"></div>\
        </div>\
        <div><a href="#" id="choose_player_${player.id}"  class="choose_player button ${player.choose_opponent}"><span>{LB_CHOOSE_THIS_PLAYER}</span></a></div>\
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
                            <div class="cardartwrap"><div class="cardart" style="background-position: -${artx}px -${arty}px;"></div></div>\
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

var jstpl_firstplayer = '<div id="firstplayer" class="sicon"></div>';
var jstpl_deadlock = '<div id="deadlock_${id}" class="deadlock"></div>';
var jstpl_trap = '<div id="trap_${id}" class="trap"></div>';

var jstpl_year2= '<div class="whiteblock" id="library_2_wrap">\
        <div id="library_2"></div>\
    </div>'
var jstpl_year3= '<div class="whiteblock" id="library_3_wrap">\
        <div id="library_3"></div>\
    </div>'
</script>  

{OVERALL_GAME_FOOTER}
