<?php

use Likewinter\CardDeck\Games\Poker\HandRank;

dataset('poker hands and ranks', function () {
    return [
        // Straights — all 10 possible sequences
        'wheel straight A-2-3-4-5' => ['A♣,2♦,3♥,4♠,5♣', HandRank::STRAIGHT],
        'straight 2-3-4-5-6' => ['2♣,3♦,4♥,5♠,6♣', HandRank::STRAIGHT],
        'straight 3-4-5-6-7' => ['3♣,4♦,5♥,6♠,7♣', HandRank::STRAIGHT],
        'straight 4-5-6-7-8' => ['4♣,5♦,6♥,7♠,8♣', HandRank::STRAIGHT],
        'straight 5-6-7-8-9' => ['5♣,6♦,7♥,8♠,9♣', HandRank::STRAIGHT],
        'straight 6-7-8-9-10' => ['6♣,7♦,8♥,9♠,10♣', HandRank::STRAIGHT],
        'straight 7-8-9-10-J' => ['7♣,8♦,9♥,10♠,J♣', HandRank::STRAIGHT],
        'straight 8-9-10-J-Q' => ['8♣,9♦,10♥,J♠,Q♣', HandRank::STRAIGHT],
        'straight 9-10-J-Q-K' => ['9♣,10♦,J♥,Q♠,K♣', HandRank::STRAIGHT],
        'broadway straight 10-J-Q-K-A' => ['10♣,J♦,Q♥,K♠,A♣', HandRank::STRAIGHT],

        // Straight flushes (wheel and mid)
        'wheel straight flush' => ['A♣,2♣,3♣,4♣,5♣', HandRank::STRAIGHT_FLUSH],
        'mid straight flush' => ['5♠,6♠,7♠,8♠,9♠', HandRank::STRAIGHT_FLUSH],

        // Royal flush (A-K-Q-J-10 suited)
        'royal flush spades' => ['10♠,J♠,Q♠,K♠,A♠', HandRank::ROYAL_FLUSH],
        'royal flush hearts' => ['A♥,K♥,Q♥,J♥,10♥', HandRank::ROYAL_FLUSH],

        // Four of a kind
        'four of a kind aces' => ['A♣,A♦,A♥,A♠,K♣', HandRank::FOUR_OF_A_KIND],

        // Full house (both orientations)
        'full house aces over kings' => ['A♣,A♦,A♥,K♠,K♣', HandRank::FULL_HOUSE],
        'full house kings over aces' => ['K♣,K♦,A♥,A♠,A♣', HandRank::FULL_HOUSE],

        // Flush (not straight)
        'flush' => ['2♣,5♣,7♣,9♣,K♣', HandRank::FLUSH],

        // Three of a kind
        'three of a kind aces' => ['A♣,A♦,A♥,K♠,Q♣', HandRank::THREE_OF_A_KIND],

        // Two pair
        'two pair aces and kings' => ['A♣,A♦,K♥,K♠,Q♣', HandRank::TWO_PAIR],

        // One pair
        'one pair aces' => ['A♣,A♦,K♥,Q♠,J♣', HandRank::ONE_PAIR],

        // High card
        'high card ace king' => ['A♣,K♦,Q♥,J♠,9♣', HandRank::HIGH_CARD],
    ];
});
