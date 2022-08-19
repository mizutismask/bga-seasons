Hello all Seasons players,

There has been a new release of Seasons with multiple bug fixes.
I mostly focused on easy bugs, on highly-voted bugs and on bugs in the
"official tournament authorized cards" (the old set, not the 2022 set)
since I'm mostly an Arena player myself.

I am well aware of the fact that a lot of bugs remain.
However, I think the bugs which are fixed already improve the game sufficiently much
that a release is in order.

Below is a list of changes, separated into changes affecting gameplay
and changes affecting the interface.

Some of these changes involve an interpretation of the game rules which might
not be 100% clear. I tried to implement things in the way which seemed most logical
to me, but I am open for discussions...

There are a few changes to messages in the interface which will require new
translations.


GAMEPLAY CHANGES
- Fix Dial of Colof
  - https://boardgamearena.com/bug?id=36378
  - https://boardgamearena.com/bug?id=37633
  - https://boardgamearena.com/bug?id=36427
  - https://boardgamearena.com/bug?id=39941
  - https://boardgamearena.com/bug?id=36807
  - https://boardgamearena.com/bug?id=39474
  - https://boardgamearena.com/bug?id=39024
  - https://boardgamearena.com/bug?id=38001
  - https://boardgamearena.com/bug?id=37122
  - https://boardgamearena.com/bug?id=36943
  - https://boardgamearena.com/bug?id=36585
  - https://boardgamearena.com/bug?id=36445
  - https://boardgamearena.com/bug?id=36369
  - https://boardgamearena.com/bug?id=57895
  - https://boardgamearena.com/bug?id=45978
  - https://boardgamearena.com/bug?id=44055
  - https://boardgamearena.com/bug?id=41691
  - https://boardgamearena.com/bug?id=39393
  - https://boardgamearena.com/bug?id=38716
  - https://boardgamearena.com/bug?id=38530
  - https://boardgamearena.com/bug?id=36865
  - https://boardgamearena.com/bug?id=36836
  - https://boardgamearena.com/bug?id=36832
  - https://boardgamearena.com/bug?id=17614
  - https://boardgamearena.com/bug?id=24762
  - https://boardgamearena.com/bug?id=59456
- End-of-turn effects now happen in the correct player order and with improved card order
  - https://boardgamearena.com/bug?id=446
  - https://boardgamearena.com/bug?id=1360
  - https://boardgamearena.com/bug?id=41765
- Raven the Usurper copying Cursed Treatise: lose all energy if sacrificed
  - https://boardgamearena.com/bug?id=581
  - https://boardgamearena.com/bug?id=1307
- Raven the Usurper copying Scepter of Greatness: correctly count number of items
  - https://boardgamearena.com/bug?id=501
  - https://boardgamearena.com/bug?id=7145
  - https://boardgamearena.com/bug?id=12972
- Amulet of Time: move discarded cards to top of discard pile
  - https://boardgamearena.com/bug?id=14499
  - https://boardgamearena.com/bug?id=38889
  - https://boardgamearena.com/bug?id=11744
- Io's Minion, Eolis Replicator: check cost before activating; remove "Cancel" button
  - https://boardgamearena.com/bug?id=3817
  - https://boardgamearena.com/bug?id=9848
  - https://boardgamearena.com/bug?id=22855
  - https://boardgamearena.com/bug?id=60029
- Selenia's Codex: check if we can take back at least 1 card; remove "Do not use" button
  - https://boardgamearena.com/bug?id=1067
  - https://boardgamearena.com/bug?id=17098
- Mirror of the Seasons: check also energy on Amulet of Water
  - https://boardgamearena.com/bug?id=2558
  - https://boardgamearena.com/bug?id=48710
- Mirror of the Seasons: discard excess energy
  - https://boardgamearena.com/bug?id=549
- Naria the Prophetess: pick own card first
  - https://boardgamearena.com/bug?id=1843
- Naria the Prophetess: handle the case that there are not enough cards
  - https://boardgamearena.com/bug?id=8115
  - https://boardgamearena.com/bug?id=12893
  - https://boardgamearena.com/bug?id=26334
  - https://boardgamearena.com/bug?id=41876
- Throne of Renewal: allow transmute bonus of +4 or more
  - https://boardgamearena.com/bug?id=22618
- Potion of the Ancients: discard excess energy
  - https://boardgamearena.com/bug?id=4680
- Bonus exchange energy tokens: discard excess energy

INTERFACE CHANGES
- Add tooltip for dice chosen by players
  - https://boardgamearena.com/bug?id=3556
  - https://boardgamearena.com/bug?id=7463
- Show all dice during game setup (no reason to show only winter dice)
- Don't show "End my turn" button when player must draw a card
- Show current transmute bonus
- Automatically pick last card in draft to save a turn
- Log increases in summoning gauge more explicitly; log when hitting the limit of 15
  - https://boardgamearena.com/bug?id=42317
- Crafty Nightshade: update hand size
  - https://boardgamearena.com/bug?id=18371
- Various minor improvements in log messages
  - https://boardgamearena.com/bug?id=34029
