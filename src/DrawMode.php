<?php

declare(strict_types=1);

namespace Likewinter\CardDeck;

enum DrawMode
{
    case Sequential;
    case OneByOne;
    case Random;
}
