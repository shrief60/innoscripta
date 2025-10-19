<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NewsAggregatorService;

class FetchArticlesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest articles from news sources';

    /**
     * Execute the console command.
     */
    public function handle(NewsAggregatorService $aggregator): int
    {
        $this->info('starting Fetching news articles...');
        $this->newLine();

        $summary = $aggregator->fetchAndStoreAll();

        // Display results in a table
        $tableData = [];
        $totalFetched = 0;
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalFailed = 0;
        $totalSkipped = 0;

        foreach ($summary as $source => $result) {
            $tableData[] = [
                'Source' => $source,
                'Status' => $result['success'] ? '✅ Success' : '❌ Failed',
                'Fetched' => $result['fetched'],
                'Inserted' => $result['inserted'] ?? 0,
                'Updated' => $result['updated'] ?? 0,
                'Skipped' => $result['skipped'] ?? 0,
                'Failed' => $result['failed'] ?? 0,
            ];

            $totalFetched += $result['fetched'];
            $totalInserted += $result['inserted'] ?? 0;
            $totalUpdated += $result['updated'] ?? 0;
            $totalSkipped += $result['skipped'] ?? 0;
            $totalFailed += $result['failed'] ?? 0;
        }

        $this->table(
            ['Source', 'Status', 'Fetched', 'Inserted', 'Updated', 'Skipped', 'Failed'],
            $tableData
        );

        $this->newLine();
        $this->info("📊 Summary:");
        $this->line("  Fetched: {$totalFetched}");
        $this->line("  Inserted: {$totalInserted}");
        $this->line("  Updated: {$totalUpdated}");
        if ($totalSkipped > 0) {
            $this->warn("  Skipped: {$totalSkipped}");
        }
        if ($totalFailed > 0) {
            $this->error("  Failed: {$totalFailed}");
        }
        
        // Return appropriate exit code
        $hasFailures = collect($summary)->contains('success', false);
        
        if ($hasFailures) {
            $this->warn('⚠️  Some sources failed. Check logs for details.');
            return self::FAILURE;
        }

        $this->info('✅ All sources processed successfully!');
        return self::SUCCESS;
    }
}
