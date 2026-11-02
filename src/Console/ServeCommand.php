<?php

namespace Knackline\Olm\Console;

use Illuminate\Console\Command;
use Knackline\Olm\Exceptions\OlmException;
use Knackline\Olm\OlmManager;
use Knackline\Olm\Support\ServeCommandBuilder;

class ServeCommand extends Command
{
    protected $signature = 'olm:serve';

    protected $description = 'Start the configured local OLM inference backend for development';

    public function handle(OlmManager $olm, ServeCommandBuilder $builder): int
    {
        $driver = (string) config('olm.default', 'ollama');
        $driverConfig = (array) config("olm.drivers.{$driver}", []);

        try {
            $command = $builder->build($driver, $driverConfig);
        } catch (OlmException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($olm->health()) {
            $this->info("OLM backend [{$driver}] is already reachable; keeping the process alive.");

            return $this->keepAlive($olm);
        }

        $this->info("Starting OLM backend [{$driver}]: {$command}");

        passthru($command, $exitCode);

        return is_int($exitCode) ? $exitCode : self::FAILURE;
    }

    protected function keepAlive(OlmManager $olm): int
    {
        while (true) {
            sleep(30);

            if (! $olm->health()) {
                $this->warn('OLM backend became unreachable.');

                return self::FAILURE;
            }
        }
    }
}
