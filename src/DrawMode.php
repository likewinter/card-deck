<?php

declare(strict_types=1);

namespace Likewinter\CardDeck;

/**
 * How a Table moves cards from the deck into hands.
 */
enum DrawMode
{
    /**
     * Each hand receives its full draw before the next hand does.
     */
    case Sequential;

    /**
     * Cards are dealt one at a time, rotating through the hands.
     */
    case OneByOne;

    /**
     * Cards are taken from random positions in the deck.
     */
    case Random;
}
