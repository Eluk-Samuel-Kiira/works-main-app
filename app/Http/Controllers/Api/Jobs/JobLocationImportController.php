<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Job\JobLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Handles bulk import of Job Locations from an uploaded Excel file.
 *
 * Expected columns (header row, in any order — matched by name):
 *   country | district | city | description | meta_title | meta_description | sort_order | is_active
 *
 * Requires: composer require phpoffice/phpspreadsheet
 * (already a dependency of maatwebsite/excel if that package is installed)
 */
class JobLocationImportController extends Controller
{
    use ApiResponse;

    /** Country codes this importer currently accepts. Extend as new country templates are added. */
    protected const ALLOWED_COUNTRIES = [
        'UG', 'KE', 'TZ', 'NG', 'ZA', 'GH', 'RW', 'SS', 'CD', 'ET',
        'EG', 'MA', 'DZ', 'SN', 'CI', 'CM', 'ZM', 'ZW', 'MW', 'BW',
        'NA', 'MU', 'SC', 'BI', 'AO', 'MZ', 'SL', 'LR', 'ML', 'BF',
        'NE', 'TD', 'CF', 'CG', 'GA', 'GQ', 'TG', 'BJ', 'GM', 'GN',
        'GW', 'CV', 'ST', 'KM', 'MG', 'SZ', 'LS', 'ER', 'DJ', 'SO',
        'SD', 'LY', 'TN', 'MR',
    ];

    /**
     * POST /api/v1/job-locations/import
     *
     * multipart/form-data:
     *   file              (required) .xlsx / .xls / .csv
     *   country           (optional) if set, every row is forced to this code,
     *                                regardless of what's in the file's country column
     *   skip_duplicates   (optional, default true) skip rows whose district+country
     *                                already exists instead of failing the whole import
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'country' => ['nullable', 'string', 'in:' . implode(',', self::ALLOWED_COUNTRIES)],
            'skip_duplicates' => ['nullable', 'boolean'],
        ]);

        $skipDuplicates = $request->boolean('skip_duplicates', true);
        $forceCountry = $request->string('country')->upper()->value() ?: null;

        try {
            $rows = $this->readRows($request->file('file'));
        } catch (\Throwable $e) {
            return $this->error('Could not read the uploaded file: ' . $e->getMessage(), 422);
        }

        if (empty($rows)) {
            return $this->error('The uploaded file has no data rows.', 422);
        }

        $created = [];
        $skipped = [];
        $failed = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $lineNumber = $i + 2; // +1 for header, +1 for 1-index

                $country = strtoupper(trim($forceCountry ?? ($row['country'] ?? '')));
                $district = trim((string) ($row['district'] ?? ''));

                if ($country === '' || $district === '') {
                    $failed[] = ['row' => $lineNumber, 'reason' => 'Missing country or district.'];
                    continue;
                }

                if (!in_array($country, self::ALLOWED_COUNTRIES, true)) {
                    $failed[] = ['row' => $lineNumber, 'reason' => "'{$country}' is not a recognized country code."];
                    continue;
                }

                $exists = JobLocation::where('country', $country)
                    ->where('district', $district)
                    ->exists();

                if ($exists) {
                    if ($skipDuplicates) {
                        $skipped[] = ['row' => $lineNumber, 'district' => $district, 'country' => $country];
                        continue;
                    }
                    $failed[] = ['row' => $lineNumber, 'reason' => "District '{$district}' already exists for {$country}."];
                    continue;
                }

                $payload = [
                    'country' => $country,
                    'district' => $district,
                    'city' => $this->blankToNull($row['city'] ?? null),
                    'description' => $this->blankToNull($row['description'] ?? null),
                    'meta_title' => $this->blankToNull($row['meta_title'] ?? null),
                    'meta_description' => $this->blankToNull($row['meta_description'] ?? null),
                    'sort_order' => is_numeric($row['sort_order'] ?? null) ? (int) $row['sort_order'] : 0,
                    'is_active' => $this->parseBool($row['is_active'] ?? true),
                ];

                $validator = Validator::make($payload, [
                    'country' => ['required', 'string', 'max:10'],
                    'district' => ['required', 'string', 'max:255'],
                    'city' => ['nullable', 'string', 'max:255'],
                    'description' => ['nullable', 'string'],
                    'meta_title' => ['nullable', 'string', 'max:255'],
                    'meta_description' => ['nullable', 'string', 'max:500'],
                    'sort_order' => ['nullable', 'integer', 'min:0'],
                    'is_active' => ['nullable', 'boolean'],
                ]);

                if ($validator->fails()) {
                    $failed[] = ['row' => $lineNumber, 'reason' => implode(' ', $validator->errors()->all())];
                    continue;
                }

                $location = JobLocation::create($payload);
                $created[] = ['id' => $location->id, 'district' => $location->district, 'country' => $location->country];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error('Import failed: ' . $e->getMessage(), 500);
        }

        return $this->success([
            'created_count' => count($created),
            'skipped_count' => count($skipped),
            'failed_count' => count($failed),
            'created' => $created,
            'skipped' => $skipped,
            'failed' => $failed,
        ], sprintf(
            'Import complete: %d created, %d skipped, %d failed.',
            count($created),
            count($skipped),
            count($failed)
        ));
    }

    /**
     * Reads the uploaded spreadsheet into an array of associative rows,
     * keyed by lower-cased, snake-ish header names.
     */
    protected function readRows($file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $sheetData = $sheet->toArray(null, true, true, false);

        // Find the header row: the first row containing both "country" and "district".
        $headerRowIndex = null;
        $headers = [];
        foreach ($sheetData as $idx => $row) {
            $normalized = array_map(fn ($c) => Str::snake(trim((string) $c)), $row);
            if (in_array('country', $normalized, true) && in_array('district', $normalized, true)) {
                $headerRowIndex = $idx;
                $headers = $normalized;
                break;
            }
        }

        if ($headerRowIndex === null) {
            throw new \RuntimeException("Could not find a header row containing 'country' and 'district' columns.");
        }

        $rows = [];
        for ($i = $headerRowIndex + 1; $i < count($sheetData); $i++) {
            $raw = $sheetData[$i];
            $row = [];
            foreach ($headers as $col => $key) {
                if ($key === '') {
                    continue;
                }
                $row[$key] = $raw[$col] ?? null;
            }

            // Skip fully blank rows
            if (trim((string) ($row['country'] ?? '')) === '' && trim((string) ($row['district'] ?? '')) === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    protected function blankToNull($value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        return ($value === null || $value === '') ? null : (string) $value;
    }

    protected function parseBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $value = strtoupper(trim((string) $value));
        return !in_array($value, ['FALSE', '0', 'NO', 'N', ''], true);
    }
}