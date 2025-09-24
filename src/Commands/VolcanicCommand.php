<?php

declare(strict_types=1);

namespace Volcanic\Commands;

use Illuminate\Console\Command;

class VolcanicCommand extends Command
{
    public $signature = 'volcanic';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
