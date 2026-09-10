<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vendor;
use App\Models\JobRequest;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class MigrateLocationData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:vendor-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates string location/city to location_id for Vendors and JobRequests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration for Vendors...');
        
        $vendors = Vendor::whereNotNull('location')->whereNull('location_id')->get();
        $vendorCount = 0;
        
        foreach ($vendors as $vendor) {
            $loc = Location::where(DB::raw('LOWER(name)'), strtolower(trim($vendor->location)))->first();
            if ($loc) {
                // Use DB facade to avoid triggering the 'saving' event that might have side-effects
                DB::table('vendors')->where('id', $vendor->id)->update(['location_id' => $loc->id]);
                $vendorCount++;
            } else {
                $this->warn("Location not found for Vendor ID {$vendor->id} with city: {$vendor->location}");
            }
        }
        $this->info("Successfully migrated {$vendorCount} vendors.");

        $this->info('Starting migration for Freelancers (JobRequests)...');
        
        $freelancers = JobRequest::whereNotNull('city')->whereNull('location_id')->get();
        $freeCount = 0;
        
        foreach ($freelancers as $freelancer) {
            $loc = Location::where(DB::raw('LOWER(name)'), strtolower(trim($freelancer->city)))->first();
            if ($loc) {
                DB::table('job_requests')->where('id', $freelancer->id)->update(['location_id' => $loc->id]);
                $freeCount++;
            } else {
                $this->warn("Location not found for JobRequest ID {$freelancer->id} with city: {$freelancer->city}");
            }
        }
        $this->info("Successfully migrated {$freeCount} freelancers.");
    }
}
