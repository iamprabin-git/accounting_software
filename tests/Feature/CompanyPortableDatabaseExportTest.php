<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;
use Tests\TestCase;

class CompanyPortableDatabaseExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_writes_portable_sqlite_for_company(): void
    {
        $company = Company::factory()->create(['name' => 'Portable Test Co']);

        $this->artisan('company:write-daily-portable-database', [
            'company' => (string) $company->id,
        ])->assertSuccessful();

        $dir = storage_path('app/company-portable-databases/'.$company->id);
        $this->assertDirectoryExists($dir);
        $files = glob($dir.'/*.sqlite');
        $this->assertNotEmpty($files, 'Expected a .sqlite snapshot file');

        $pdo = new PDO('sqlite:'.$files[0]);
        $count = (int) $pdo->query(
            'SELECT COUNT(*) FROM companies WHERE id = '.(int) $company->id,
        )->fetchColumn();
        $this->assertSame(1, $count);

        $manifestPath = $dir.'/manifest.json';
        $this->assertFileExists($manifestPath);
        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $this->assertSame($company->id, $manifest['company_id']);
        $this->assertSame('Portable Test Co', $manifest['company_name']);
    }
}
