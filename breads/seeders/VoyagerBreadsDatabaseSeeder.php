<?php

namespace VoyagerBreadDb\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Traits\Seedable;

class VoyagerBreadsDatabaseSeeder extends Seeder
{
    use Seedable;

    protected $seedersPath = __DIR__.'/';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seed(DataTypesTableSeeder::class);
        $this->seed(DataRowsTableSeeder::class);
        $this->seed(SettingsTableSeeder::class);
    }
}
