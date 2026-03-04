<?php

namespace Tests\Feature;

use App\Models\Predaja;
use App\Models\Predmet;
use App\Models\Upis;
use App\Models\User;
use App\Models\Zadatak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PredajaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_only_authenticated_student_submissions(): void
    {
        [$student, $zadatak] = $this->createStudentWithZadatak();
        $otherStudent = $this->createUser('other@student.rs', 'STUDENT');

        $mojaPredaja = Predaja::create([
            'zadatak_id' => $zadatak->id,
            'student_id' => $student->id,
            'status' => 'PREDATO',
            'submitted_at' => now(),
            'file_path' => null,
        ]);

        Predaja::create([
            'zadatak_id' => $zadatak->id,
            'student_id' => $otherStudent->id,
            'status' => 'PREDATO',
            'submitted_at' => now(),
            'file_path' => null,
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/predaje');
        $response->assertOk();

        $items = $this->extractCollectionData($response->json());

        $this->assertCount(1, $items);
        $this->assertEquals($mojaPredaja->id, $items[0]['id'] ?? null);
    }

    public function test_show_returns_forbidden_when_student_requests_other_students_submission(): void
    {
        [$student, $zadatak] = $this->createStudentWithZadatak();
        $otherStudent = $this->createUser('other@student.rs', 'STUDENT');

        $predaja = Predaja::create([
            'zadatak_id' => $zadatak->id,
            'student_id' => $otherStudent->id,
            'status' => 'PREDATO',
            'submitted_at' => now(),
            'file_path' => null,
        ]);

        Sanctum::actingAs($student);

        $this->getJson('/api/predaje/' . $predaja->id)
            ->assertStatus(403)
            ->assertJson(['message' => 'Zabranjeno']);
    }

    public function test_store_returns_forbidden_for_non_student_user(): void
    {
        [$student, $zadatak] = $this->createStudentWithZadatak();
        $profesor = $this->createUser('profesor2@example.rs', 'PROFESOR');

        Sanctum::actingAs($profesor);

        $this->postJson('/api/predaje', [
            'zadatak_id' => $zadatak->id,
        ])->assertStatus(403)
          ->assertJson(['message' => 'Zabranjeno']);
    }

    public function test_store_creates_submission_for_enrolled_student(): void
    {
        [$student, $zadatak] = $this->createStudentWithZadatak();

        Sanctum::actingAs($student);

        $response = $this->postJson('/api/predaje', [
            'zadatak_id' => $zadatak->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Predaja je uspešno kreirana.');

        $this->assertDatabaseHas('predaje', [
            'zadatak_id' => $zadatak->id,
            'student_id' => $student->id,
            'status' => 'PREDATO',
        ]);
    }

    public function test_store_returns_forbidden_when_student_is_not_enrolled(): void
    {
        $student = $this->createUser('student@example.rs', 'STUDENT');
        $profesor = $this->createUser('profesor@example.rs', 'PROFESOR');

        $predmet = Predmet::create([
            'profesor_id' => $profesor->id,
            'naziv' => 'Web programiranje',
            'sifra' => 'WP101',
            'godina_studija' => 2,
        ]);

        $zadatak = Zadatak::create([
            'predmet_id' => $predmet->id,
            'profesor_id' => $profesor->id,
            'naslov' => 'Domaci 1',
            'opis' => 'Opis',
            'rok_predaje' => now()->addDays(7),
        ]);

        Sanctum::actingAs($student);

        $this->postJson('/api/predaje', [
            'zadatak_id' => $zadatak->id,
        ])->assertStatus(403)
          ->assertJson(['message' => 'Zabranjeno']);
    }

    public function test_store_returns_conflict_when_submission_already_exists(): void
    {
        [$student, $zadatak] = $this->createStudentWithZadatak();

        Predaja::create([
            'zadatak_id' => $zadatak->id,
            'student_id' => $student->id,
            'status' => 'PREDATO',
            'submitted_at' => now(),
            'file_path' => null,
        ]);

        Sanctum::actingAs($student);

        $this->postJson('/api/predaje', [
            'zadatak_id' => $zadatak->id,
        ])->assertStatus(409)
          ->assertJson(['message' => 'Već postoji predaja za ovaj zadatak.']);
    }

    public function test_update_returns_forbidden_for_student(): void
    {
        [$student, $zadatak] = $this->createStudentWithZadatak();

        $predaja = Predaja::create([
            'zadatak_id' => $zadatak->id,
            'student_id' => $student->id,
            'status' => 'PREDATO',
            'submitted_at' => now(),
            'file_path' => null,
        ]);

        Sanctum::actingAs($student);

        $this->putJson('/api/predaje/' . $predaja->id, [
            'status' => 'OCENJENO',
        ])->assertStatus(403)
          ->assertJson(['message' => 'Zabranjeno']);
    }

    public function test_update_allows_professor_for_his_subject_submission(): void
    {
        [$student, $zadatak, $profesor] = $this->createStudentWithZadatak();

        $predaja = Predaja::create([
            'zadatak_id' => $zadatak->id,
            'student_id' => $student->id,
            'status' => 'PREDATO',
            'submitted_at' => now(),
            'file_path' => null,
        ]);

        Sanctum::actingAs($profesor);

        $response = $this->putJson('/api/predaje/' . $predaja->id, [
            'status' => 'OCENJENO',
            'ocena' => 9.5,
            'komentar' => 'Odlican rad',
        ]);

        $response->assertOk();

        $payload = $this->extractSingleResourceData($response->json());

        $this->assertEquals('OCENJENO', $payload['status'] ?? null);
        $this->assertEquals('Odlican rad', $payload['komentar'] ?? null);

        $this->assertDatabaseHas('predaje', [
            'id' => $predaja->id,
            'status' => 'OCENJENO',
            'komentar' => 'Odlican rad',
        ]);
    }

    public function test_destroy_returns_conflict_when_student_tries_to_delete_graded_submission(): void
    {
        [$student, $zadatak] = $this->createStudentWithZadatak();

        $predaja = Predaja::create([
            'zadatak_id' => $zadatak->id,
            'student_id' => $student->id,
            'status' => 'OCENJENO',
            'submitted_at' => now(),
            'file_path' => null,
        ]);

        Sanctum::actingAs($student);

        $this->deleteJson('/api/predaje/' . $predaja->id)
            ->assertStatus(409)
            ->assertJson(['message' => 'Predaja je već ocenjena.']);
    }

    public function test_destroy_allows_admin_to_delete_submission(): void
    {
        [$student, $zadatak] = $this->createStudentWithZadatak();
        $admin = $this->createUser('admin@example.rs', 'ADMIN');

        $predaja = Predaja::create([
            'zadatak_id' => $zadatak->id,
            'student_id' => $student->id,
            'status' => 'PREDATO',
            'submitted_at' => now(),
            'file_path' => null,
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson('/api/predaje/' . $predaja->id)
            ->assertOk()
            ->assertJson(['message' => 'Predaja je uspešno obrisana.']);

        $this->assertDatabaseMissing('predaje', ['id' => $predaja->id]);
    }

    public function test_file_returns_404_when_submission_has_no_file_path(): void
    {
        [$student, $zadatak] = $this->createStudentWithZadatak();

        $predaja = Predaja::create([
            'zadatak_id' => $zadatak->id,
            'student_id' => $student->id,
            'status' => 'PREDATO',
            'submitted_at' => now(),
            'file_path' => null,
        ]);

        Sanctum::actingAs($student);

        $this->getJson('/api/predaje/' . $predaja->id . '/file')
            ->assertStatus(404)
            ->assertJson(['message' => 'Predaja nema fajl.']);
    }

    public function test_file_returns_forbidden_when_student_requests_other_student_file(): void
    {
        Storage::fake('public');

        [$student, $zadatak] = $this->createStudentWithZadatak();
        $otherStudent = $this->createUser('other@student.rs', 'STUDENT');

        $file = UploadedFile::fake()->create('predaja.pdf', 100, 'application/pdf');
        $path = $file->store('predaje', 'public');

        $predaja = Predaja::create([
            'zadatak_id' => $zadatak->id,
            'student_id' => $otherStudent->id,
            'status' => 'PREDATO',
            'submitted_at' => now(),
            'file_path' => $path,
        ]);

        Sanctum::actingAs($student);

        $this->getJson('/api/predaje/' . $predaja->id . '/file')
            ->assertStatus(403)
            ->assertJson(['message' => 'Zabranjeno']);
    }

    private function createStudentWithZadatak(): array
    {
        $student = $this->createUser('student@example.rs', 'STUDENT');
        $profesor = $this->createUser('profesor@example.rs', 'PROFESOR');

        $predmet = Predmet::create([
            'profesor_id' => $profesor->id,
            'naziv' => 'Programiranje 1',
            'sifra' => 'P1-' . uniqid(),
            'godina_studija' => 1,
        ]);

        $zadatak = Zadatak::create([
            'predmet_id' => $predmet->id,
            'profesor_id' => $profesor->id,
            'naslov' => 'Domaci 1',
            'opis' => 'Opis zadatka',
            'rok_predaje' => now()->addDays(7),
        ]);

        Upis::create([
            'student_id' => $student->id,
            'predmet_id' => $predmet->id,
        ]);

        return [$student, $zadatak, $profesor, $predmet];
    }

    private function createUser(string $email, string $uloga): User
    {
        return User::create([
            'ime' => 'Test',
            'prezime' => 'Korisnik',
            'email' => $email,
            'password' => Hash::make('password'),
            'uloga' => $uloga,
        ]);
    }

    private function extractCollectionData(array $json): array
    {
        if (isset($json['data']) && is_array($json['data'])) {
            return $json['data'];
        }

        if (array_is_list($json)) {
            return $json;
        }

        return [];
    }

    private function extractSingleResourceData(array $json): array
    {
        if (isset($json['data']) && is_array($json['data'])) {
            return $json['data'];
        }

        return $json;
    }
}