<?php

namespace App\Console\Commands;

use App\Support\TestingToken;
use Illuminate\Console\Command;

class GenerateTestingToken extends Command
{
    protected $signature = 'testing-token:generate {--ttl=3600 : How many seconds the token stays valid}';

    protected $description = 'Mint a testing token for the pricing endpoint';

    /**
     * Print a token, in any environment.
     *
     * Deliberately unguarded: production is the environment worth minting one for, since a token
     * is how production's own testing plans are reached.
     */
    public function handle(): void
    {
        $this->line(TestingToken::generate((int) $this->option('ttl')));
    }
}
