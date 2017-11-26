<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\City;
use App\Hotel;

class HotelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('hotels')->truncate();
        DB::table('cities')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $citiesData = [
            [
                'name' => 'Sandton',
                'image' => 'sandton.jpg',
                'hotels' => [
                    [
                        'name' => 'Empire',
                        'image' => 'Empire.jpg',
                        'address' => '177 Empire Place, Sandhurst, Johannesburg, 2196',
                        'price' => 1500,
                        'latitude' => -26.112523,
                        'longitude' => 28.051407,
                    ],
                    [
                        'name' => '20 West',
                        'image' => '20_West.jpg',
                        'address' => '20 West Road South, Morningside, Johannesburg, 2057',
                        'price' => 1500,
                        'latitude' => -26.092035,
                        'longitude' => 28.055099,
                    ],
                    [
                        'name' => 'Hydro',
                        'image' => 'Hydro.jpg',
                        'address' => '86 Grayston Drive, Sandton, Johannesburg, 2057',
                        'price' => 1500,
                        'latitude' => -26.099757,
                        'longitude' => 28.054053,
                    ],
                    [
                        'name' => 'Moloko',
                        'image' => 'Moloko.jpg',
                        'address' => '160 Helen Road , Strathavon, Sandton, Johannesburg',
                        'price' => 1500,
                        'latitude' => -26.097862,
                        'longitude' => 28.069267,
                    ],
                    [
                        'name' => 'Villa',
                        'image' => 'Villa.jpg',
                        'address' => '130 Rivonia Road, Sandton, Johannesburg, 2196',
                        'price' => 1500,
                        'latitude' => -26.102864,
                        'longitude' => 28.060488,
                    ],
                ]
            ],
            [
                'name' => 'Rosebank',
                'image' => 'rosebank.jpg',
                'hotels' => [
                    [
                        'name' => 'On Bath',
                        'image' => 'On_Bath.jpg',
                        'address' => '72 Bath Ave, Rosebank, Johannesburg, 219',
                        'price' => 1500,
                        'latitude' => -26.141578,
                        'longitude' => 28.039791,
                    ],
                ]
            ],
            [
                'name' => 'Cape Town',
                'image' => 'capetown.jpg',
                'hotels' => [
                    [
                        'name' => 'Mirage',
                        'image' => 'Mirage.jpg',
                        'address' => 'Cnr, Waterkant St, Cape Town, 8001',
                        'price' => 1500,
                        'latitude' => -33.917565,
                        'longitude' => 18.417805,
                    ],
                ]
            ],
        ];

        foreach ($citiesData as $cityData) {

            $city = new City;
            $city->name = $cityData['name'];
            $city->image = $cityData['image'];
            $city->save();

            $hotelsData = $cityData['hotels'];
            foreach ($hotelsData as $hotelData) {
                $hotel = new Hotel($hotelData);
                $city->hotels()->save($hotel);
            }
        }


    }
}
