<?php

use Likewinter\CardDeck\{Dealer,Deck};
use Likewinter\CardDeck\Games\Poker;

require_once __DIR__ . '/../vendor/autoload.php';

$poker = new Poker(handSize: 5, numHands: 3);

echo $poker->gameState();

$poker->deal();

echo $poker->gameState();
echo $poker->handsState();
