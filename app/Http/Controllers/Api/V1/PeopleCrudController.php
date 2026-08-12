<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\IndexRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Base controller for the People Management modules.
 *
 * Extends the standard CRUD controller with bulk CSV export/import and a
 * shared activity-log timeline for the "profile" views.
 */
abstract class PeopleCrudController extends CrudController
{
    /**
     * The CSV column headers (label => resource key).
     *
     * @return array<string, string>
     */
    abstract protected function exportColumns(): array;

    /**
     * Export the current filtered query as a CSV stream.
     */
    public function export(IndexRequest $request): StreamedResponse
    {
        $this->authorize('viewAny', $this->modelClass);

        $columns = $this->exportColumns();
        $records = $this->service->export($request);

        $filename = Str::slug($this->resourceLabel()).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($columns, $records): void {
            $stream = fopen('php://output', 'w');

            // UTF-8 BOM so Excel detects the encoding correctly.
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, array_keys($columns));

            foreach ($records as $record) {
                $row = [];
                foreach ($columns as $key) {
                    $row[] = $this->flattenValue($record->{$key} ?? null);
                }
                fputcsv($stream, $row);
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Bulk-import records from a CSV file or an array of rows.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', $this->modelClass);

        $rows = $request->input('rows');

        if ($request->hasFile('file')) {
            $rows = $this->parseCsv($request->file('file')->getRealPath());
        }

        if (! is_array($rows) || $rows === []) {
            return $this->error('No importable rows were provided.', null, 422);
        }

        /** @var class-string<FormRequest> $requestClass */
        $requestClass = $this->requestClass();
        $rules = $this->importRules(new $requestClass);

        $created = 0;
        $failed = [];

        foreach ($rows as $row) {
            $normalized = $this->normalizeRow($row);

            $validator = Validator::make($normalized, $rules);

            if ($validator->fails()) {
                $failed[] = [
                    'row' => $normalized,
                    'errors' => $validator->errors()->toArray(),
                ];

                continue;
            }

            try {
                $this->service->create($validator->validated());
                $created++;
            } catch (\Throwable $e) {
                $failed[] = ['row' => $normalized, 'errors' => ['_exception' => [$e->getMessage()]]];
            }
        }

        return $this->success([
            'created' => $created,
            'failed' => $failed,
            'total' => count($rows),
        ], 'Import finished.');
    }

    /**
     * The validation rules used for imported rows.
     *
     * @return array<string, mixed>
     */
    protected function importRules(FormRequest $request): array
    {
        return $request->rules();
    }

    /**
     * Parse a CSV file into an array of associative rows.
     *
     * @return list<array<string, mixed>>
     */
    protected function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null]) {
                continue;
            }

            $row = [];
            foreach ($header as $index => $column) {
                $row[$column] = $line[$index] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Normalize a single import row (trim + empty string to null).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $value = trim($value) === '' ? null : trim($value);
            }

            $normalized[Str::snake($key)] = $value;
        }

        return $normalized;
    }

    /**
     * Flatten a value into a scalar suitable for a CSV cell.
     */
    protected function flattenValue(mixed $value): string|int|float|null
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'getAttribute') && $value->getAttribute('name') !== null) {
            return $value->getAttribute('name');
        }

        return json_encode($value);
    }
}
