<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\CompanyPortableDatabaseExportService;
use Illuminate\Console\Command;

class CompanyWriteDailyPortableDatabaseCommand extends Command
{
    protected $signature = 'company:write-daily-portable-database
                            {company? : Numeric company ID}
                            {--all : Write a snapshot for every company}';

    protected $description = 'Export one portable SQLite database per company (for USB backup or transfer)';

    public function handle(CompanyPortableDatabaseExportService $exporter): int
    {
        if ($this->option('all')) {
            $n = 0;
            foreach (Company::query()->orderBy('id')->cursor() as $company) {
                $r = $exporter->writeDailySnapshot($company);
                $this->line("{$company->id}\t{$company->name}\t{$r['path']}\t{$r['bytes']} bytes");
                $n++;
            }
            $this->info("Wrote {$n} company snapshot(s).");

            return self::SUCCESS;
        }

        $raw = $this->argument('company');
        if ($raw === null || $raw === '') {
            $this->error('Pass a company ID or use --all.');

            return self::FAILURE;
        }

        $id = (int) $raw;
        if ($id <= 0) {
            $this->error('Company ID must be a positive integer.');

            return self::FAILURE;
        }

        $company = Company::query()->find($id);
        if ($company === null) {
            $this->error("Company {$id} not found.");

            return self::FAILURE;
        }

        $r = $exporter->writeDailySnapshot($company);
        $this->info("Wrote {$r['path']} ({$r['bytes']} bytes).");

        return self::SUCCESS;
    }
}
