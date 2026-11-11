<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class CloseExpiredProducts extends Command
{
    protected $signature = 'products:close-expired';

    protected $description = 'Close active products whose campaign end date has passed';

    public function handle(): int
    {
        $closed = Product::where('status', 'active')
            ->whereNotNull('campaign_end')
            ->where('campaign_end', '<', now())
            ->update(['status' => 'closed']);

        $this->info("Closed {$closed} expired product(s).");

        return self::SUCCESS;
    }
}
