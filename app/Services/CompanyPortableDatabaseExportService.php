<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use RuntimeException;

/**
 * Writes one portable SQLite file per company (per day) for manual backup to USB or transfer.
 * When the app uses a file-based SQLite main database, uses ATTACH + CREATE TABLE AS SELECT (fast, preserves types).
 * Otherwise copies rows into SQLite (works with MySQL/MariaDB).
 */
final class CompanyPortableDatabaseExportService
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    /**
     * @return array{path: string, bytes: int}
     */
    public function writeDailySnapshot(Company $company): array
    {
        $dir = $this->directoryForCompany($company->id);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Unable to create portable database directory: '.$dir);
        }

        $date = now()->format('Y-m-d');
        $path = $dir.DIRECTORY_SEPARATOR.$date.'.sqlite';
        if (is_file($path)) {
            unlink($path);
        }

        if ($this->shouldUseSqliteAttach()) {
            $this->exportViaSqliteAttach($company, $path);
        } else {
            $this->exportViaRowCopy($company, $path);
        }

        $this->writeManifest($company, $path, $date);
        $this->purgeOldSnapshots($company->id, (int) config('company_portable_db.retain_days', 90));

        $bytes = is_file($path) ? (int) filesize($path) : 0;

        return ['path' => $path, 'bytes' => $bytes];
    }

    public function purgeOldSnapshots(int $companyId, int $retainDays): void
    {
        if ($retainDays <= 0) {
            return;
        }
        $dir = $this->directoryForCompany($companyId);
        if (! is_dir($dir)) {
            return;
        }
        $cutoff = now()->subDays($retainDays)->startOfDay()->timestamp;
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*.sqlite') ?: [] as $file) {
            if (@filemtime($file) !== false && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    public function directoryForCompany(int $companyId): string
    {
        $root = storage_path('app/'.trim(config('company_portable_db.storage_subdir', 'company-portable-databases'), '/'));

        return $root.DIRECTORY_SEPARATOR.$companyId;
    }

    private function shouldUseSqliteAttach(): bool
    {
        if ($this->connection->getDriverName() !== 'sqlite') {
            return false;
        }
        $db = config('database.connections.sqlite.database');
        if ($db === ':memory:' || ! is_string($db) || $db === '') {
            return false;
        }
        $path = str_starts_with($db, DIRECTORY_SEPARATOR) || preg_match('#^[A-Za-z]:[/\\\\]#', $db) === 1
            ? $db
            : base_path($db);

        return is_file($path);
    }

    private function sqliteMainPath(): string
    {
        $db = config('database.connections.sqlite.database');
        if (! is_string($db) || $db === '' || $db === ':memory:') {
            throw new RuntimeException('Invalid SQLite database path for attach export.');
        }
        if (str_starts_with($db, DIRECTORY_SEPARATOR) || preg_match('#^[A-Za-z]:[/\\\\]#', $db) === 1) {
            return $db;
        }

        return base_path($db);
    }

    private function exportViaSqliteAttach(Company $company, string $exportPath): void
    {
        $mainPath = str_replace("'", "''", $this->sqliteMainPath());
        $cid = (int) $company->id;

        $pdo = new PDO('sqlite:'.$exportPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=OFF');
        $pdo->exec("ATTACH DATABASE '{$mainPath}' AS main");

        $exec = static function (string $sql) use ($pdo): void {
            $pdo->exec($sql);
        };

        $exec("CREATE TABLE companies AS SELECT * FROM main.companies WHERE id = {$cid}");

        $userIds = $this->collectRelatedUserIds($cid);
        if ($userIds === []) {
            $exec('CREATE TABLE users AS SELECT * FROM main.users WHERE 1 = 0');
        } else {
            $in = implode(',', $userIds);
            $exec("CREATE TABLE users AS SELECT * FROM main.users WHERE id IN ({$in})");
        }

        $this->sqliteAttachCompanyScoped($exec, $cid, 'company_holidays');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'company_working_day_overrides');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'member_groups');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'chart_accounts');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'members');
        if (Schema::hasTable('member_group_members')) {
            $exec("CREATE TABLE member_group_members AS SELECT mgm.* FROM main.member_group_members mgm INNER JOIN main.members m ON m.id = mgm.member_id WHERE m.company_id = {$cid}");
        }
        $this->sqliteAttachCompanyScoped($exec, $cid, 'debtors');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'creditors');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'loan_products');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'savings_products');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'financial_positions');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'financial_position_accruals');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'financial_position_movements');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'inventory_items');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'inventory_lots');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'inventory_movements');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'journal_entries');
        $exec("CREATE TABLE journal_lines AS SELECT jl.* FROM main.journal_lines jl INNER JOIN main.journal_entries je ON je.id = jl.journal_entry_id WHERE je.company_id = {$cid}");
        $this->sqliteAttachCompanyScoped($exec, $cid, 'journal_approval_comments');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'accounting_audit_logs');
        $exec("CREATE TABLE reviews AS SELECT * FROM main.reviews WHERE company_id = {$cid}");
        $this->sqliteAttachCompanyScoped($exec, $cid, 'crm_accounts');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'crm_contacts');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'crm_opportunities');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'crm_activities');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'teller_day_closes');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'banking_webhook_subscriptions');
        $this->sqliteAttachCompanyScoped($exec, $cid, 'bank_reconciliation_batches');
        $exec("CREATE TABLE bank_statement_lines AS SELECT bsl.* FROM main.bank_statement_lines bsl INNER JOIN main.bank_reconciliation_batches brb ON brb.id = bsl.bank_reconciliation_batch_id WHERE brb.company_id = {$cid}");
        $exec("CREATE TABLE bank_statement_line_matches AS SELECT m.* FROM main.bank_statement_line_matches m INNER JOIN main.bank_statement_lines bsl ON bsl.id = m.bank_statement_line_id INNER JOIN main.bank_reconciliation_batches brb ON brb.id = bsl.bank_reconciliation_batch_id WHERE brb.company_id = {$cid}");
        $this->sqliteAttachCompanyScoped($exec, $cid, 'portal_messages');

        $pdo->exec('DETACH DATABASE main');
    }

    /**
     * @param  callable(string): void  $exec
     */
    private function sqliteAttachCompanyScoped(callable $exec, int $cid, string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        $exec("CREATE TABLE {$table} AS SELECT * FROM main.{$table} WHERE company_id = {$cid}");
    }

    private function exportViaRowCopy(Company $company, string $exportPath): void
    {
        $pdo = new PDO('sqlite:'.$exportPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=OFF');

        $cid = (int) $company->id;

        $this->copyTableWhere($pdo, 'companies', fn ($q) => $q->where('id', $cid));

        $userIds = $this->collectRelatedUserIds($cid);
        if ($userIds === []) {
            $this->createEmptyTableLike($pdo, 'users');
        } else {
            $this->copyTableWhere($pdo, 'users', fn ($q) => $q->whereIn('id', $userIds));
        }

        $this->copyCompanyScoped($pdo, $cid, 'company_holidays');
        $this->copyCompanyScoped($pdo, $cid, 'company_working_day_overrides');
        $this->copyCompanyScoped($pdo, $cid, 'member_groups');
        $this->copyCompanyScoped($pdo, $cid, 'chart_accounts');
        $this->copyCompanyScoped($pdo, $cid, 'members');
        if (Schema::hasTable('member_group_members')) {
            $memberIds = DB::table('members')->where('company_id', $cid)->pluck('id')->all();
            if ($memberIds === []) {
                $this->createEmptyTableLike($pdo, 'member_group_members');
            } else {
                $this->copyTableWhere($pdo, 'member_group_members', fn ($q) => $q->whereIn('member_id', $memberIds));
            }
        }
        $this->copyCompanyScoped($pdo, $cid, 'debtors');
        $this->copyCompanyScoped($pdo, $cid, 'creditors');
        $this->copyCompanyScoped($pdo, $cid, 'loan_products');
        $this->copyCompanyScoped($pdo, $cid, 'savings_products');
        $this->copyCompanyScoped($pdo, $cid, 'financial_positions');
        $this->copyCompanyScoped($pdo, $cid, 'financial_position_accruals');
        $this->copyCompanyScoped($pdo, $cid, 'financial_position_movements');
        $this->copyCompanyScoped($pdo, $cid, 'inventory_items');
        $this->copyCompanyScoped($pdo, $cid, 'inventory_lots');
        $this->copyCompanyScoped($pdo, $cid, 'inventory_movements');
        $this->copyCompanyScoped($pdo, $cid, 'journal_entries');

        if (Schema::hasTable('journal_lines')) {
            $entryIds = DB::table('journal_entries')->where('company_id', $cid)->pluck('id')->all();
            if ($entryIds === []) {
                $this->createEmptyTableLike($pdo, 'journal_lines');
            } else {
                $this->copyTableWhere($pdo, 'journal_lines', fn ($q) => $q->whereIn('journal_entry_id', $entryIds));
            }
        }

        $this->copyCompanyScoped($pdo, $cid, 'journal_approval_comments');
        $this->copyCompanyScoped($pdo, $cid, 'accounting_audit_logs');
        $this->copyTableWhere($pdo, 'reviews', fn ($q) => $q->where('company_id', $cid));
        $this->copyCompanyScoped($pdo, $cid, 'crm_accounts');
        $this->copyCompanyScoped($pdo, $cid, 'crm_contacts');
        $this->copyCompanyScoped($pdo, $cid, 'crm_opportunities');
        $this->copyCompanyScoped($pdo, $cid, 'crm_activities');
        $this->copyCompanyScoped($pdo, $cid, 'teller_day_closes');
        $this->copyCompanyScoped($pdo, $cid, 'banking_webhook_subscriptions');
        $this->copyCompanyScoped($pdo, $cid, 'bank_reconciliation_batches');

        if (Schema::hasTable('bank_statement_lines')) {
            $batchIds = DB::table('bank_reconciliation_batches')->where('company_id', $cid)->pluck('id')->all();
            if ($batchIds === []) {
                $this->createEmptyTableLike($pdo, 'bank_statement_lines');
            } else {
                $this->copyTableWhere($pdo, 'bank_statement_lines', fn ($q) => $q->whereIn('bank_reconciliation_batch_id', $batchIds));
            }
        }

        if (Schema::hasTable('bank_statement_line_matches') && Schema::hasTable('bank_statement_lines')) {
            $batchIdsForLines = DB::table('bank_reconciliation_batches')->where('company_id', $cid)->pluck('id')->all();
            $lineIds = $batchIdsForLines === []
                ? []
                : DB::table('bank_statement_lines')->whereIn('bank_reconciliation_batch_id', $batchIdsForLines)->pluck('id')->all();
            if ($lineIds === []) {
                $this->createEmptyTableLike($pdo, 'bank_statement_line_matches');
            } else {
                $this->copyTableWhere($pdo, 'bank_statement_line_matches', fn ($q) => $q->whereIn('bank_statement_line_id', $lineIds));
            }
        }

        $this->copyCompanyScoped($pdo, $cid, 'portal_messages');
    }

    /**
     * @return list<int>
     */
    private function collectRelatedUserIds(int $companyId): array
    {
        $ids = collect(DB::table('users')->where('company_id', $companyId)->pluck('id'));

        $mergeCol = function (string $table, string $column) use ($companyId, &$ids): void {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                return;
            }
            if (! Schema::hasColumn($table, 'company_id')) {
                return;
            }
            $found = DB::table($table)
                ->where('company_id', $companyId)
                ->whereNotNull($column)
                ->pluck($column);
            $ids = $ids->merge($found);
        };

        $mergeCol('journal_entries', 'user_id');
        $mergeCol('journal_entries', 'approved_by_user_id');
        $mergeCol('journal_entries', 'first_approved_by_user_id');
        $mergeCol('journal_approval_comments', 'user_id');
        $mergeCol('bank_reconciliation_batches', 'user_id');
        $mergeCol('crm_opportunities', 'owner_user_id');
        $mergeCol('crm_activities', 'created_by_user_id');
        $mergeCol('teller_day_closes', 'user_id');
        $mergeCol('members', 'created_by_user_id');
        $mergeCol('members', 'approved_by_user_id');

        return $ids
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function copyCompanyScoped(PDO $sqlite, int $companyId, string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        $this->copyTableWhere($sqlite, $table, fn ($q) => $q->where('company_id', $companyId));
    }

    /**
     * @param  callable(Builder): void  $constraint
     */
    private function copyTableWhere(PDO $sqlite, string $table, callable $constraint): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $columns = Schema::getColumnListing($table);
        if ($columns === []) {
            return;
        }

        if (! $this->sqliteTableExists($sqlite, $table)) {
            $defs = [];
            foreach ($columns as $col) {
                $quoted = '"'.str_replace('"', '""', $col).'"';
                $defs[] = $col === 'id' && in_array('id', $columns, true)
                    ? $quoted.' INTEGER PRIMARY KEY'
                    : $quoted.' TEXT';
            }
            $sqlite->exec('CREATE TABLE "'.str_replace('"', '""', $table).'" ('.implode(',', $defs).')');
        }

        $query = DB::table($table);
        $constraint($query);
        $rows = $query->get();

        if ($rows->isEmpty()) {
            return;
        }

        $colList = implode(',', array_map(fn ($c) => '"'.str_replace('"', '""', $c).'"', $columns));
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $insertSql = 'INSERT INTO "'.str_replace('"', '""', $table).'" ('.$colList.') VALUES ('.$placeholders.')';
        $stmt = $sqlite->prepare($insertSql);

        foreach ($rows as $row) {
            $arr = (array) $row;
            $values = [];
            foreach ($columns as $col) {
                $values[] = $this->normalizeSqliteValue($arr[$col] ?? null);
            }
            $stmt->execute($values);
        }
    }

    private function createEmptyTableLike(PDO $sqlite, string $table): void
    {
        if (! Schema::hasTable($table) || $this->sqliteTableExists($sqlite, $table)) {
            return;
        }
        $columns = Schema::getColumnListing($table);
        $defs = [];
        foreach ($columns as $col) {
            $quoted = '"'.str_replace('"', '""', $col).'"';
            $defs[] = $col === 'id'
                ? $quoted.' INTEGER PRIMARY KEY'
                : $quoted.' TEXT';
        }
        $sqlite->exec('CREATE TABLE "'.str_replace('"', '""', $table).'" ('.implode(',', $defs).')');
    }

    private function sqliteTableExists(PDO $sqlite, string $table): bool
    {
        $stmt = $sqlite->prepare('SELECT 1 FROM sqlite_master WHERE type = ? AND name = ? LIMIT 1');
        $stmt->execute(['table', $table]);

        return (bool) $stmt->fetchColumn();
    }

    private function normalizeSqliteValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function writeManifest(Company $company, string $sqlitePath, string $date): void
    {
        $manifest = [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'exported_at' => now()->toIso8601String(),
            'sqlite_file' => basename($sqlitePath),
            'date' => $date,
            'app_name' => config('app.name'),
            'note' => 'Portable company snapshot: copy this folder to USB or another machine. Open .sqlite with any SQLite client. This is not a full app restore; use for records / migration assistance.',
        ];
        $dir = dirname($sqlitePath);
        file_put_contents(
            $dir.DIRECTORY_SEPARATOR.'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );
    }
}
