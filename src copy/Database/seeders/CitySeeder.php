<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            'Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar',
            'Jamnagar', 'Junagadh', 'Gandhinagar', 'Gandhidham',
            'Anand', 'Navsari', 'Surendranagar', 'Mehsana', 'Bharuch',
            'Morbi', 'Vapi', 'Bhuj', 'Valsad', 'Patan', 'Porbandar',
            'Godhra', 'Palanpur', 'Dahod', 'Botad'
        ];

        foreach ($cities as $city) {
            DB::table('city')->insert([
                'city_name'=>$city,
            ]);
        }
    }
}
