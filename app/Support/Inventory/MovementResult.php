<?php

namespace App\Support\Inventory;

use App\Models\StockLevel;
use App\Models\StockMovement;

final class MovementResult
{
    public function __construct(
        public StockMovement $movement,
        public StockLevel $level,
        public bool $replayed = false,
    ) {}
}
