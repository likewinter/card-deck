<?php

use Likewinter\CardDeck\Dealer;
use Likewinter\CardDeck\Deck;
use Likewinter\CardDeck\Games\Poker;
use Likewinter\CardDeck\Games\Poker\PokerHand;

require_once __DIR__ . '/../vendor/autoload.php';

$poker = new Poker(new Dealer(new Deck(), []), 5, 4);
$poker->deal();

echo $poker->state();
echo $poker->handsState();
