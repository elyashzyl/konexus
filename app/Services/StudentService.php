<?php

namespace App\Services;

use App\Models\Student;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class StudentService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'student_number',
        'lrn',
        'school_student_id',
        'first_name',
        'middle_name',
        'last_name',
        'nickname',
        'email',
    ];

    protected array $sortable = [
        'id',
        'created_at',
        'updated_at',
        'student_number',
        'lrn',
        'last_name',
        'first_name',
        'gender',
        'birth_date',
        'status',
    ];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['parents', 'guardians'];

    protected string $defaultSortBy = 'last_name';

    public function __construct(private readonly StudentRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Create a new student record, generating a student number when absent.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $data['student_number'] = $data['student_number']
            ?? $this->generateStudentNumber();

        $student = $this->repository()->create($data);

        $this->syncRelations($student, $data);

        return $student->load($this->with);
    }

    /**
     * Update a student record and its many-to-many relations.
     *
     * @param  Student  $model
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $student = $this->repository()->update($model, $data);

        $this->syncRelations($student, $data);

        return $student->load($this->with);
    }

    /**
     * Store the profile picture of a student and return its public path.
     */
    public function storePhoto(Student $student, UploadedFile $file): string
    {
        $path = $file->store('students/photos', 'public');

        $student->forceFill(['profile_picture_path' => $path])->save();

        return $path;
    }

    /**
     * Generate a unique, human-friendly student number.
     */
    public function generateStudentNumber(): string
    {
        do {
            $number = 'KXN-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while ($this->repo->findByStudentNumber($number) !== null);

        return $number;
    }

    /**
     * Sync the parent and guardian many-to-many relations of a student.
     *
     * @param  array<string, mixed>  $data
     */
    protected function syncRelations(Student $student, array $data): void
    {
        if (array_key_exists('parent_ids', $data)) {
            $student->parents()->sync($this->normalizeIds($data['parent_ids']));
        }

        if (array_key_exists('guardian_ids', $data)) {
            $student->guardians()->sync($this->normalizeIds($data['guardian_ids']));
        }
    }

    /**
     * Normalize a mixed ID payload into a list of positive integers.
     *
     * @return list<int>
     */
    protected function normalizeIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0));
    }
}
