<?php

namespace App\Services;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\TuitionRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class TuitionService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['reference_number'];

    /**
     * Relation columns included in free-text search (relation => columns).
     *
     * @var array<string, list<string>>
     */
    protected array $searchableRelations = [
        'student' => ['first_name', 'middle_name', 'last_name', 'student_number', 'lrn'],
    ];

    /**
     * Columns that are allowed to be sorted on.
     *
     * @var list<string>
     */
    protected array $sortable = [
        'id', 'created_at', 'updated_at',
        'reference_number', 'status',
        'tuition_fee', 'misc_fee', 'other_fees', 'discount',
        'total', 'amount_paid', 'balance',
    ];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['student', 'academicYear'];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortDir = 'desc';

    public function __construct(private readonly TuitionRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Create a new tuition record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $data = $this->reconcile($data);

        if (blank($data['reference_number'] ?? null)) {
            $data['reference_number'] = $this->generateReference();
        }

        return parent::create($data);
    }

    /**
     * Update an existing tuition record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $data = array_merge(
            $model->only(['tuition_fee', 'misc_fee', 'other_fees', 'discount', 'amount_paid']),
            $data
        );

        return parent::update($model, $this->reconcile($data));
    }

    /**
     * Compute the total, balance and status from the fee breakdown.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function reconcile(array $data): array
    {
        $tuitionFee = (float) ($data['tuition_fee'] ?? 0);
        $miscFee = (float) ($data['misc_fee'] ?? 0);
        $otherFees = (float) ($data['other_fees'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $amountPaid = (float) ($data['amount_paid'] ?? 0);

        $total = max(0, round($tuitionFee + $miscFee + $otherFees - $discount, 2));
        $balance = max(0, round($total - $amountPaid, 2));

        $data['total'] = $total;
        $data['balance'] = $balance;
        $data['status'] = $balance <= 0 && $total > 0
            ? 'paid'
            : ($amountPaid > 0 ? 'partial' : 'unpaid');

        return $data;
    }

    /**
     * Generate a unique human-readable reference number.
     */
    protected function generateReference(): string
    {
        $seq = ((int) $this->repo->query()->max('id')) + 1;

        return 'TUIT-'.now()->format('Y').'-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}