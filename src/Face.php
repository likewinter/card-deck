<?php

namespace Likewinter\CardDeck;

/**
 * Face-up / face-down state for a card in play.
 *
 * Most games keep cards face-up once played. Games with partial
 * information (Solitaire tableau, War's face-down cards, hand cards
 * hidden from opponents) use Face::Down until a card is revealed.
 */
enum Face: string
{
    case Up = 'up';
    case Down = 'down';

    public function isUp(): bool
    {
        return $this === self::Up;
    }

    public function isDown(): bool
    {
        return $this === self::Down;
    }
}
