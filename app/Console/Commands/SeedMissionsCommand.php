<?php

namespace App\Console\Commands;

use Database\Seeders\CleanMissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SeedMissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'potts:seed-missions {--date=today : Date for the missions (YYYY-MM-DD or "today")} {--tech=tech@potts.app : Email of the tech user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed missions for development or demo purposes (Idempotent)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateInput = $this->option('date');
        $techEmail = $this->option('tech');

        if ($dateInput === 'today') {
            $date = Carbon::today()->format('Y-m-d');
        } else {
            try {
                $date = Carbon::parse($dateInput)->format('Y-m-d');
            } catch (\Exception $e) {
                $this->error("Invalid date format: {$dateInput}. Please use YYYY-MM-DD.");

                return 1;
            }
        }

        $this->info("Starting clean mission seeder for date: {$date}...");

        $seeder = new CleanMissionSeeder;

        // Pass info method via anonymous class or just echo if needed,
        // but CleanMissionSeeder extends Seeder which has command property access
        // IF called via $this->call(). But here we are instantiating it directly.
        // Better to use $this->call(CleanMissionSeeder::class, ...) if possible,
        // but CleanMissionSeeder::run() signature has arguments.
        // Laravel's $this->call() supports array of parameters but keys are not argument names usually for seeders?
        // Actually, Seeders in Laravel don't support arguments in run() via $this->call() easily unless properties are set.
        // Let's modify the Seeder to accept parameters in constructor or use a different approach?
        // Or just Instantiate and call run manually.

        // The CleanMissionSeeder::run() expects ($date, $techEmail).
        // If I instantiate it: $seeder->run($date, $techEmail);
        // But $this->command inside Seeder might be null.
        // Let's inject the command instance into the seeder if needed or just ignore output.
        // Since CleanMissionSeeder extends Seeder, it uses $this->command->info() which might fail if not set.

        // Standard way:
        // $this->call(CleanMissionSeeder::class, ['--class' => 'CleanMissionSeeder', ...? No.]

        // The CleanMissionSeeder I wrote has `run(string $date, string $techEmail)`.
        // I should stick to manual invocation but set the command property.

        $seeder->setCommand($this);
        $seeder->run($date, $techEmail);

        $this->info("✅ Missions seeded successfully for {$date}.");

        return 0;
    }
}
