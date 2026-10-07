<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'administrator']);
    }

    public function test_import_utf8_bom_csv_creates_users(): void
    {
        Company::create(['name' => 'บริษัท ทดสอบ จำกัด', 'short_name' => 'TEST', 'email_domains' => 'test.com', 'is_active' => true]);

        $csv = "\xEF\xBB\xBFชื่อ,อีเมล,บริษัท,แผนก,เบอร์โทรศัพท์\r\n"
            ."สมชาย ใจดี,somchai@test.com,บริษัท ทดสอบ จำกัด,บัญชี,0812345678\r\n"
            ."สมหญิง รักงาน,somying@test.com,TEST,-,-\r\n"
            ."ไม่มีบริษัท,nobody@test.com,ไม่มีจริง,IT,\r\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $response = $this->actingAs($this->admin())
            ->post(route('normal_users.import'), ['csv_file' => $file]);

        $response->assertRedirect(route('normal_users.index'));
        $response->assertSessionHas('import_errors');

        $this->assertDatabaseHas('users', ['email' => 'somchai@test.com', 'company' => 'บริษัท ทดสอบ จำกัด', 'role' => 'user']);
        $this->assertDatabaseHas('users', ['email' => 'somying@test.com', 'company' => 'บริษัท ทดสอบ จำกัด', 'department' => null]);
        $this->assertDatabaseMissing('users', ['email' => 'nobody@test.com']);
    }

    public function test_import_windows874_semicolon_csv(): void
    {
        Company::create(['name' => 'บริษัท ทดสอบ จำกัด', 'email_domains' => 'test.com', 'is_active' => true]);

        $utf8 = "ชื่อ;อีเมล;บริษัท;แผนก\r\nสมชาย ใจดี;somchai@test.com;บริษัท ทดสอบ จำกัด;บัญชี\r\n";
        $file = UploadedFile::fake()->createWithContent('users.csv', iconv('UTF-8', 'CP874', $utf8));

        $this->actingAs($this->admin())
            ->post(route('normal_users.import'), ['csv_file' => $file])
            ->assertRedirect(route('normal_users.index'));

        $this->assertDatabaseHas('users', ['email' => 'somchai@test.com', 'name' => 'สมชาย ใจดี', 'department' => 'บัญชี']);
    }

    public function test_import_rejects_non_csv_file(): void
    {
        $file = UploadedFile::fake()->create('users.pdf', 10, 'application/pdf');

        $this->actingAs($this->admin())
            ->from(route('normal_users.index'))
            ->post(route('normal_users.import'), ['csv_file' => $file])
            ->assertSessionHasErrors('csv_file');
    }

    public function test_import_companies_csv(): void
    {
        $csv = "\xEF\xBB\xBFชื่อบริษัท,ตัวย่อ,โดเมนอีเมล\nบริษัท ใหม่ จำกัด,NEW,new.com\n";
        $file = UploadedFile::fake()->createWithContent('companies.csv', $csv);

        $this->actingAs($this->admin())
            ->post(route('companies.import'), ['csv_file' => $file])
            ->assertRedirect(route('companies.index'));

        $this->assertDatabaseHas('companies', ['name' => 'บริษัท ใหม่ จำกัด', 'short_name' => 'NEW', 'email_domains' => 'new.com']);
    }
}
