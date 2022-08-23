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
        <div id="bonus1" class="bonus"></div>
        <div id="bonus2" class="bonus"></div>
        <div id="bonus3" class="bonus"></div>
        <div id="bonus4" class="bonus"></div>
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
        <div id="seasons_dices"></div>
        <div class="conversion_reminder">
            <div id="convertFor3" class="reminder">
                <div class="sicon"></div>
                <div class="sicon icon_cristal">3</div>
            </div>
            <div class="sicon icon_separator"></div>
             <div id="convertFor2" class="reminder">
                <div class="sicon"></div>
                <div class="sicon icon_cristal">2</div>
            </div>
             <div class="sicon icon_separator"></div>
             <div id="convertFor1" class="reminder">
                <div id="energyType1" class="sicon energy"></div>
                <div id="energyType2" class="sicon energy"></div>
                <div class="sicon icon_cristal">1</div>
            </div>
        </div>
    </div>

    <div id="choiceCards" class="whiteblock seasons_rightpanel">
        <h3>{LB_CARDS_DRAWN}</h3>
        <div id="choiceCardsStock">
        </div>
    </div>

    <div id="myhand" class="whiteblock seasons_rightpanel">
        <h3>{LB_MY_HAND}</h3>
        <div id="player_hand">
        </div>
    </div>

    <br class="clear"/>


        <div class="whiteblock tableau" id="currentPlayerTablea">
            <h3>{CURRENT_PLAYER_NAME}</h3>
            <div id="player_tableau_{CURRENT_PLAYER_ID}"></div>
        </div> 

    <!-- BEGIN player -->
        <div class="whiteblock tableau">
            <h3>{PLAYER_NAME}</h3>
            <div id="player_tableau_{PLAYER_ID}"></div>
        </div>    
    <!-- END player -->

    <div class="whiteblock" id="otus_wrap">
        <h3>{OTUS_TITLE}:</h3>
        <div id="otus"></div>
    </div>
    
    <div class="whiteblock" id="library_2_wrap">
        <h3>{CARDS_FOR_YEAR_2}:</h3>
        <div id="library_2"></div>
    </div>
    <div class="whiteblock" id="library_3_wrap">
        <h3>{CARDS_FOR_YEAR_3}:</h3>
        <div id="library_3"></div>
    </div>
</div>
<script type="text/javascript">

// Templates
var jstpl_player_board = '<div class="clear">\
        <div class="playerdie_wrap" id="playerdie_wrap_${id}">\
            <div class="playerdie" id="playerdie_${id}"></div>\
        </div>\
        <div class="boardblock_seasons">\
            <div class="energywrapper">\
                <div id="energy_reserve_${id}" class="energy_reserve"></div>\
                <div id="energies_${id}" class="energies"></div>\
            </div>\
        </div>\
        <div class="boardblock_seasons">\
            <div id="tinvocationlevel_${id}" class="sicon invocation_level imgtext tinvocationlevel" ></div><span id="invocation_level_${id}" class="tinvocationlevel">0</span>\
            <div id="handcounticon_${id}" class="icon16 icon16_hand tthand"></div><span id="handcount_${id}" class="tthand">0</span>\
            <div id="bonusused_${id}" class="sicon bonusused bonusused${nb_bonus} imgtext ttbonusused"></div>\
            <div class="firstplayerplace" id="firstplayer_${id}"></div>\
        </div>\
        <div><a href="#" id="choose_player_${id}"  class="choose_player button ${choose_opponent}"><span>{LB_CHOOSE_THIS_PLAYER}</span></a></div>\
    </div>';

var jstpl_card_content = '<div class="cardcontent cardtype_${type} thickness" id="cardcontent_${id}">\
                            <div class="cardtitle">${name}</div>\
                            <div class="cardactivated"></div>\
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

</script>  

{OVERALL_GAME_FOOTER}
