<?php
// app/Console/Commands/ImportJobLocations.php

namespace App\Console\Commands;

use App\Models\Job\JobLocation;
use Illuminate\Console\Command;

class ImportJobLocations extends Command
{
    protected $signature = 'import:job-locations 
                            {--country= : Specific country to import (KE, NG)}
                            {--force : Force import even if records exist}';
    
    protected $description = 'Import job locations for Kenya and Nigeria';

    public function handle()
    {
        $locations = $this->getLocations();
        $country = $this->option('country');
        $force = $this->option('force');

        // Filter by country if specified
        if ($country) {
            $locations = array_filter($locations, fn($loc) => $loc['country'] === strtoupper($country));
            $this->info("Filtering to country: " . strtoupper($country));
        }

        $total = count($locations);
        $this->info("Found {$total} locations to import.");

        $created = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($locations as $location) {
            // Check if location already exists
            $exists = JobLocation::where('country', $location['country'])
                ->where('district', $location['district'])
                ->exists();

            if ($exists) {
                if ($force) {
                    // Update existing
                    JobLocation::where('country', $location['country'])
                        ->where('district', $location['district'])
                        ->update($location);
                    $created++;
                    $this->line(" Updated: {$location['district']}, {$location['country']}");
                } else {
                    $skipped++;
                }
                $bar->advance();
                continue;
            }

            // Create new
            JobLocation::create($location);
            $created++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Import complete: {$created} created/updated, {$skipped} skipped.");
        
        // Show summary by country
        $this->newLine();
        $this->table(
            ['Country', 'Total'],
            [
                ['Kenya (KE)', JobLocation::where('country', 'KE')->count()],
                ['Nigeria (NG)', JobLocation::where('country', 'NG')->count()],
            ]
        );
    }

    private function getLocations(): array
    {
        return array_merge(
            $this->getKenyaLocations(),
            $this->getNigeriaLocations(),
        );
    }

    private function getKenyaLocations(): array
    {
        return [
            // Major cities first (with sort_order)
            ['country' => 'KE', 'district' => 'Nairobi', 'city' => 'Nairobi', 'is_capital' => true, 'sort_order' => 1],
            ['country' => 'KE', 'district' => 'Mombasa', 'city' => 'Mombasa', 'is_capital' => false, 'sort_order' => 2],
            ['country' => 'KE', 'district' => 'Kisumu', 'city' => 'Kisumu', 'is_capital' => false, 'sort_order' => 3],
            ['country' => 'KE', 'district' => 'Nakuru', 'city' => 'Nakuru', 'is_capital' => false, 'sort_order' => 4],
            ['country' => 'KE', 'district' => 'Eldoret', 'city' => 'Eldoret', 'is_capital' => false, 'sort_order' => 5],
            
            // All 47 Counties
            ['country' => 'KE', 'district' => 'Baringo', 'city' => 'Kabarnet'],
            ['country' => 'KE', 'district' => 'Bomet', 'city' => 'Bomet'],
            ['country' => 'KE', 'district' => 'Bungoma', 'city' => 'Bungoma'],
            ['country' => 'KE', 'district' => 'Busia', 'city' => 'Busia'],
            ['country' => 'KE', 'district' => 'Elgeyo-Marakwet', 'city' => 'Iten'],
            ['country' => 'KE', 'district' => 'Embu', 'city' => 'Embu'],
            ['country' => 'KE', 'district' => 'Garissa', 'city' => 'Garissa'],
            ['country' => 'KE', 'district' => 'Homa Bay', 'city' => 'Homa Bay'],
            ['country' => 'KE', 'district' => 'Isiolo', 'city' => 'Isiolo'],
            ['country' => 'KE', 'district' => 'Kajiado', 'city' => 'Kajiado'],
            ['country' => 'KE', 'district' => 'Kakamega', 'city' => 'Kakamega'],
            ['country' => 'KE', 'district' => 'Kericho', 'city' => 'Kericho'],
            ['country' => 'KE', 'district' => 'Kiambu', 'city' => 'Kiambu'],
            ['country' => 'KE', 'district' => 'Kilifi', 'city' => 'Kilifi'],
            ['country' => 'KE', 'district' => 'Kirinyaga', 'city' => 'Kerugoya'],
            ['country' => 'KE', 'district' => 'Kisii', 'city' => 'Kisii'],
            ['country' => 'KE', 'district' => 'Kitui', 'city' => 'Kitui'],
            ['country' => 'KE', 'district' => 'Kwale', 'city' => 'Kwale'],
            ['country' => 'KE', 'district' => 'Laikipia', 'city' => 'Nanyuki'],
            ['country' => 'KE', 'district' => 'Lamu', 'city' => 'Lamu'],
            ['country' => 'KE', 'district' => 'Machakos', 'city' => 'Machakos'],
            ['country' => 'KE', 'district' => 'Makueni', 'city' => 'Wote'],
            ['country' => 'KE', 'district' => 'Mandera', 'city' => 'Mandera'],
            ['country' => 'KE', 'district' => 'Marsabit', 'city' => 'Marsabit'],
            ['country' => 'KE', 'district' => 'Meru', 'city' => 'Meru'],
            ['country' => 'KE', 'district' => 'Migori', 'city' => 'Migori'],
            ['country' => 'KE', 'district' => 'Murang\'a', 'city' => 'Murang\'a'],
            ['country' => 'KE', 'district' => 'Nandi', 'city' => 'Kapsabet'],
            ['country' => 'KE', 'district' => 'Narok', 'city' => 'Narok'],
            ['country' => 'KE', 'district' => 'Nyamira', 'city' => 'Nyamira'],
            ['country' => 'KE', 'district' => 'Nyandarua', 'city' => 'Ol Kalou'],
            ['country' => 'KE', 'district' => 'Nyeri', 'city' => 'Nyeri'],
            ['country' => 'KE', 'district' => 'Samburu', 'city' => 'Maralal'],
            ['country' => 'KE', 'district' => 'Siaya', 'city' => 'Siaya'],
            ['country' => 'KE', 'district' => 'Taita-Taveta', 'city' => 'Voi'],
            ['country' => 'KE', 'district' => 'Tana River', 'city' => 'Hola'],
            ['country' => 'KE', 'district' => 'Tharaka-Nithi', 'city' => 'Chuka'],
            ['country' => 'KE', 'district' => 'Trans-Nzoia', 'city' => 'Kitale'],
            ['country' => 'KE', 'district' => 'Turkana', 'city' => 'Lodwar'],
            ['country' => 'KE', 'district' => 'Uasin Gishu', 'city' => 'Eldoret'],
            ['country' => 'KE', 'district' => 'Vihiga', 'city' => 'Vihiga'],
            ['country' => 'KE', 'district' => 'Wajir', 'city' => 'Wajir'],
            ['country' => 'KE', 'district' => 'West Pokot', 'city' => 'Kapenguria'],
        ];
    }

    private function getNigeriaLocations(): array
    {
        return [
            // Federal Capital Territory (with sort_order)
            ['country' => 'NG', 'district' => 'Federal Capital Territory', 'city' => 'Abuja', 'is_capital' => true, 'sort_order' => 1],
            
            // All 36 States
            ['country' => 'NG', 'district' => 'Abia', 'city' => 'Umuahia', 'sort_order' => 2],
            ['country' => 'NG', 'district' => 'Adamawa', 'city' => 'Yola', 'sort_order' => 3],
            ['country' => 'NG', 'district' => 'Akwa Ibom', 'city' => 'Uyo', 'sort_order' => 4],
            ['country' => 'NG', 'district' => 'Anambra', 'city' => 'Awka', 'sort_order' => 5],
            ['country' => 'NG', 'district' => 'Bauchi', 'city' => 'Bauchi', 'sort_order' => 6],
            ['country' => 'NG', 'district' => 'Bayelsa', 'city' => 'Yenagoa', 'sort_order' => 7],
            ['country' => 'NG', 'district' => 'Benue', 'city' => 'Makurdi', 'sort_order' => 8],
            ['country' => 'NG', 'district' => 'Borno', 'city' => 'Maiduguri', 'sort_order' => 9],
            ['country' => 'NG', 'district' => 'Cross River', 'city' => 'Calabar', 'sort_order' => 10],
            ['country' => 'NG', 'district' => 'Delta', 'city' => 'Asaba', 'sort_order' => 11],
            ['country' => 'NG', 'district' => 'Ebonyi', 'city' => 'Abakaliki', 'sort_order' => 12],
            ['country' => 'NG', 'district' => 'Edo', 'city' => 'Benin City', 'sort_order' => 13],
            ['country' => 'NG', 'district' => 'Ekiti', 'city' => 'Ado Ekiti', 'sort_order' => 14],
            ['country' => 'NG', 'district' => 'Enugu', 'city' => 'Enugu', 'sort_order' => 15],
            ['country' => 'NG', 'district' => 'Gombe', 'city' => 'Gombe', 'sort_order' => 16],
            ['country' => 'NG', 'district' => 'Imo', 'city' => 'Owerri', 'sort_order' => 17],
            ['country' => 'NG', 'district' => 'Jigawa', 'city' => 'Dutse', 'sort_order' => 18],
            ['country' => 'NG', 'district' => 'Kaduna', 'city' => 'Kaduna', 'sort_order' => 19],
            ['country' => 'NG', 'district' => 'Kano', 'city' => 'Kano', 'sort_order' => 20],
            ['country' => 'NG', 'district' => 'Katsina', 'city' => 'Katsina', 'sort_order' => 21],
            ['country' => 'NG', 'district' => 'Kebbi', 'city' => 'Birnin Kebbi', 'sort_order' => 22],
            ['country' => 'NG', 'district' => 'Kogi', 'city' => 'Lokoja', 'sort_order' => 23],
            ['country' => 'NG', 'district' => 'Kwara', 'city' => 'Ilorin', 'sort_order' => 24],
            ['country' => 'NG', 'district' => 'Lagos', 'city' => 'Ikeja', 'sort_order' => 25],
            ['country' => 'NG', 'district' => 'Nasarawa', 'city' => 'Lafia', 'sort_order' => 26],
            ['country' => 'NG', 'district' => 'Niger', 'city' => 'Minna', 'sort_order' => 27],
            ['country' => 'NG', 'district' => 'Ogun', 'city' => 'Abeokuta', 'sort_order' => 28],
            ['country' => 'NG', 'district' => 'Ondo', 'city' => 'Akure', 'sort_order' => 29],
            ['country' => 'NG', 'district' => 'Osun', 'city' => 'Osogbo', 'sort_order' => 30],
            ['country' => 'NG', 'district' => 'Oyo', 'city' => 'Ibadan', 'sort_order' => 31],
            ['country' => 'NG', 'district' => 'Plateau', 'city' => 'Jos', 'sort_order' => 32],
            ['country' => 'NG', 'district' => 'Rivers', 'city' => 'Port Harcourt', 'sort_order' => 33],
            ['country' => 'NG', 'district' => 'Sokoto', 'city' => 'Sokoto', 'sort_order' => 34],
            ['country' => 'NG', 'district' => 'Taraba', 'city' => 'Jalingo', 'sort_order' => 35],
            ['country' => 'NG', 'district' => 'Yobe', 'city' => 'Damaturu', 'sort_order' => 36],
            ['country' => 'NG', 'district' => 'Zamfara', 'city' => 'Gusau', 'sort_order' => 37],
        ];
    }

    // # Import both Kenya and Nigeria
    // php artisan import:job-locations

    // # Import only Kenya
    // php artisan import:job-locations --country=KE

    // # Import only Nigeria
    // php artisan import:job-locations --country=NG

    // # Force update existing records
    // php artisan import:job-locations --force

    // # Force update only Kenya
    // php artisan import:job-locations --country=KE --force
}