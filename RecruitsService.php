<?php

namespace App\Services\Api;

use App\Models\LandingPage;
use App\Models\Prospect;
use App\Models\ShortLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;

class RecruitsService
{
    public static function getImportCsvHeaders()
    {
        return [
            'First Name',
            'Last Name',
            'Email',
            'Street',
            'Unit',
            'City',
            'State',
            'Zip Code',
            'Phone Number',
        ];
    }

    public static function getImportCsvColumns()
    {
        return [
            'first_name',
            'last_name',
            'email',
            'street',
            'unit',
            'city',
            'state',
            'zip_code',
            'phone_number',
        ];
    }

    public function validateCsvColumns(array $data)
    {
        $requiredColumns = self::getImportCsvHeaders();

        foreach ($data as $key => $column) {
            if (str_contains($data[0], '﻿')) {
                $data[$key] = str_replace('﻿', '', $column);
            }
        }

        if ($requiredColumns !==  $data) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'file' => ['File don\'t have required columns: ' . implode($requiredColumns, ', ')],
            ]);
        }
    }

    protected function getCreateData(int $initiativeId, int $organizationId, array $data) : array
    {
        $fields = self::getImportCsvColumns();

        $prospectData = [
            'initiative_id' => $initiativeId,
            'organization_id' => $organizationId,
            'imported' => true,
            'is_recruit' => true,
            'voter_registration' => false,
        ];

        foreach ($fields as $key => $field) {
            $value = \Arr::get($data, $key);
            if (($field === 'phone_number') && $value) {
                $this->validatePhoneNumber($value, $initiativeId, $organizationId);
                $value = PhoneNumber::make($value, 'US')->formatForCountry('US');
            }
            $prospectData[$field] = $value;
        }

        return $prospectData;
    }

    protected function validatePhoneNumber(string $value, int $initiativeId, int $organizationId): void
    {
        $validator = Validator::make([
            'phone_number' => $value
        ], [
            'phone_number' => 'nullable|phone:LENIENT,US'
        ]);

        if ($validator->fails()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone_number' => ["Phone number $value is invalid"],
            ]);
        }

        if (
            Prospect::where('initiative_id', $initiativeId)
                ->where('organization_id', $organizationId)
                ->where(function ($query) use ($value) {
                    $query->where('phone_number', $value)
                        ->orWhere('phone_number', phone($value, 'US')->formatForCountry('US'))
                        ->orWhere('phone_number', phone($value, 'US')->formatE164());
                })->first()
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone_number' => ["You have prospect which has $value phone number"],
            ]);
        }
    }

    public function createProspect(int $initiativeId, int $organizationId, array $csvData) : Prospect
    {
        $prospectData = $this->getCreateData($initiativeId, $organizationId, $csvData);
        $prospect = Prospect::firstOrCreate(\Arr::only($prospectData, [
            'initiative_id',
            'organization_id',
            'first_name',
            'last_name',
            'email',
            'phone_number',
        ]), $prospectData);

        if (!$prospect->voter_registration) {
            $prospect->checkVoter();
        }

        return $prospect;
    }

    public function generateCsv(Collection $prospects, LandingPage $landingPage) : array
    {
        $data = [];

        foreach ($prospects as $prospect) {
            array_push($data, [
                'id' => $prospect->id,
                'first_name' => $prospect->first_name,
                'last_name' => $prospect->last_name,
                'email' => $prospect->email,
                'phone_number' => $prospect->phone_number,
                'address' => $prospect->address,
                'landing_page_url' => $this->generateLandingPageUrl($prospect, $landingPage),
            ]);
        }

        return $data;
    }


    protected function generateLandingPageUrl(Prospect $prospect, LandingPage $landingPage): string
    {
        $landingPageUrl = route('landing-page', [
            'slug' => $landingPage->slug,
            'r' => encrypt(['prospect_id' => $prospect->id])
        ]);

        return ShortLink::shortLink($landingPageUrl);
    }

    public function checkEmptyRow(array $csvData) : bool
    {
        foreach ($csvData as $key => $value) {
            if ($value) {
                return true;
            }
        }
        return false;
    }
}
