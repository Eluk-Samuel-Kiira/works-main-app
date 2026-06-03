<?php
// database/seeders/UpdateJobLocationsCoordinatesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateJobLocationsCoordinatesSeeder extends Seeder
{
    public function run(): void
    {
        // Country to country_code mapping
        $countryToCode = [
            'Uganda' => 'UG',
            'Kenya' => 'KE',
            'Tanzania' => 'TZ',
            'Rwanda' => 'RW',
            'Burundi' => 'BI',
            'South Sudan' => 'SS',
            'DR Congo' => 'CD',
            'Nigeria' => 'NG',
            'South Africa' => 'ZA',
            'Ghana' => 'GH',
            'Ethiopia' => 'ET',
            'Egypt' => 'EG',
        ];

        // Coordinates for cities
        $cityCoordinates = [
            // Uganda
            'Kampala' => ['lat' => 0.3136, 'lng' => 32.5811, 'is_capital' => true],
            'Entebbe' => ['lat' => 0.0512, 'lng' => 32.4637, 'is_capital' => false],
            'Jinja' => ['lat' => 0.4246, 'lng' => 33.2042, 'is_capital' => false],
            'Gulu' => ['lat' => 2.7724, 'lng' => 32.2907, 'is_capital' => false],
            'Mbarara' => ['lat' => -0.6072, 'lng' => 30.6545, 'is_capital' => false],
            'Fort Portal' => ['lat' => 0.6712, 'lng' => 30.2750, 'is_capital' => false],
            'Mbale' => ['lat' => 1.0784, 'lng' => 34.1810, 'is_capital' => false],
            'Lira' => ['lat' => 2.2499, 'lng' => 32.8999, 'is_capital' => false],
            'Soroti' => ['lat' => 1.7144, 'lng' => 33.6111, 'is_capital' => false],
            
            // Kenya
            'Nairobi' => ['lat' => -1.2921, 'lng' => 36.8219, 'is_capital' => true],
            'Mombasa' => ['lat' => -4.0435, 'lng' => 39.6682, 'is_capital' => false],
            'Kisumu' => ['lat' => -0.1022, 'lng' => 34.7617, 'is_capital' => false],
            'Nakuru' => ['lat' => -0.3031, 'lng' => 36.0800, 'is_capital' => false],
            'Eldoret' => ['lat' => 0.5143, 'lng' => 35.2698, 'is_capital' => false],
            'Thika' => ['lat' => -1.0388, 'lng' => 37.0833, 'is_capital' => false],
            'Malindi' => ['lat' => -3.2187, 'lng' => 40.1169, 'is_capital' => false],
            
            // Tanzania
            'Dar es Salaam' => ['lat' => -6.7924, 'lng' => 39.2083, 'is_capital' => false],
            'Dodoma' => ['lat' => -6.1629, 'lng' => 35.7516, 'is_capital' => true],
            'Arusha' => ['lat' => -3.3869, 'lng' => 36.6820, 'is_capital' => false],
            'Mwanza' => ['lat' => -2.5164, 'lng' => 32.8987, 'is_capital' => false],
            'Zanzibar' => ['lat' => -6.1659, 'lng' => 39.2026, 'is_capital' => false],
            'Mbeya' => ['lat' => -8.9000, 'lng' => 33.4500, 'is_capital' => false],
            'Tanga' => ['lat' => -5.0724, 'lng' => 39.0995, 'is_capital' => false],
            
            // Rwanda
            'Kigali' => ['lat' => -1.9441, 'lng' => 30.0619, 'is_capital' => true],
            'Musanze' => ['lat' => -1.5000, 'lng' => 29.6346, 'is_capital' => false],
            'Rubavu' => ['lat' => -1.6833, 'lng' => 29.2500, 'is_capital' => false],
            'Huye' => ['lat' => -2.6000, 'lng' => 29.7333, 'is_capital' => false],
            
            // Burundi
            'Bujumbura' => ['lat' => -3.3614, 'lng' => 29.3599, 'is_capital' => true],
            'Gitega' => ['lat' => -3.4264, 'lng' => 29.9306, 'is_capital' => false],
            
            // South Sudan
            'Juba' => ['lat' => 4.8594, 'lng' => 31.5713, 'is_capital' => true],
            
            // Nigeria
            'Lagos' => ['lat' => 6.5244, 'lng' => 3.3792, 'is_capital' => false],
            'Abuja' => ['lat' => 9.0765, 'lng' => 7.3986, 'is_capital' => true],
            
            // South Africa
            'Johannesburg' => ['lat' => -26.2041, 'lng' => 28.0473, 'is_capital' => false],
            'Cape Town' => ['lat' => -33.9249, 'lng' => 18.4241, 'is_capital' => false],
            'Pretoria' => ['lat' => -25.7479, 'lng' => 28.2293, 'is_capital' => true],
            
            // Ghana
            'Accra' => ['lat' => 5.6037, 'lng' => -0.1870, 'is_capital' => true],
        ];

        // Update each location
        $locations = DB::table('job_locations')->get();
        
        foreach ($locations as $location) {
            $updateData = [];
            
            // Set country_code based on country name
            if (isset($countryToCode[$location->country])) {
                $updateData['country_code'] = $countryToCode[$location->country];
            } else {
                $updateData['country_code'] = 'UG';
            }
            
            // Set coordinates based on district
            if ($location->district && isset($cityCoordinates[$location->district])) {
                $coords = $cityCoordinates[$location->district];
                $updateData['latitude'] = $coords['lat'];
                $updateData['longitude'] = $coords['lng'];
                $updateData['is_capital'] = $coords['is_capital'];
            } elseif ($location->district) {
                // Try to match partial names
                foreach ($cityCoordinates as $city => $coords) {
                    if (stripos($location->district, $city) !== false || stripos($city, $location->district) !== false) {
                        $updateData['latitude'] = $coords['lat'];
                        $updateData['longitude'] = $coords['lng'];
                        $updateData['is_capital'] = $coords['is_capital'];
                        break;
                    }
                }
            }
            
            // Set region based on country
            $regions = [
                'UG' => 'East Africa', 'KE' => 'East Africa', 'TZ' => 'East Africa',
                'RW' => 'East Africa', 'BI' => 'East Africa', 'SS' => 'East Africa',
                'CD' => 'Central Africa', 'NG' => 'West Africa', 'ZA' => 'Southern Africa',
                'GH' => 'West Africa', 'ET' => 'East Africa', 'EG' => 'North Africa',
            ];
            $updateData['region'] = $regions[$updateData['country_code']] ?? 'Africa';
            
            // Update the record
            if (!empty($updateData)) {
                DB::table('job_locations')
                    ->where('id', $location->id)
                    ->update($updateData);
            }
        }
    }
}