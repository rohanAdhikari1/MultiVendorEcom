<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NepalLocationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Nepal's 7 provinces mapped to their 77 districts.
     *
     * @var array<string, array<int, string>>
     */
    private const PROVINCES = [
        'Koshi Province' => [
            'Bhojpur', 'Dhankuta', 'Ilam', 'Jhapa', 'Khotang', 'Morang',
            'Okhaldhunga', 'Panchthar', 'Sankhuwasabha', 'Solukhumbu',
            'Sunsari', 'Taplejung', 'Terhathum', 'Udayapur',
        ],
        'Madhesh Province' => [
            'Bara', 'Dhanusha', 'Mahottari', 'Parsa', 'Rautahat', 'Saptari', 'Sarlahi', 'Siraha',
        ],
        'Bagmati Province' => [
            'Bhaktapur', 'Chitwan', 'Dhading', 'Dolakha', 'Kathmandu',
            'Kavrepalanchok', 'Lalitpur', 'Makwanpur', 'Nuwakot',
            'Ramechhap', 'Rasuwa', 'Sindhuli', 'Sindhupalchok',
        ],
        'Gandaki Province' => [
            'Baglung', 'Gorkha', 'Kaski', 'Lamjung', 'Manang', 'Mustang',
            'Myagdi', 'Nawalpur', 'Parbat', 'Syangja', 'Tanahun',
        ],
        'Lumbini Province' => [
            'Arghakhanchi', 'Banke', 'Bardiya', 'Dang', 'Nawalparasi West',
            'Gulmi', 'Kapilvastu', 'Palpa', 'Pyuthan', 'Rolpa', 'Rukum East', 'Rupandehi',
        ],
        'Karnali Province' => [
            'Dailekh', 'Dolpa', 'Humla', 'Jajarkot', 'Jumla', 'Kalikot',
            'Mugu', 'Rukum West', 'Salyan', 'Surkhet',
        ],
        'Sudurpashchim Province' => [
            'Achham', 'Baitadi', 'Bajhang', 'Bajura', 'Dadeldhura',
            'Darchula', 'Kailali', 'Kanchanpur', 'Doti',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PROVINCES as $provinceName => $districts) {
            $province = Province::query()->firstOrCreate(
                ['slug' => Str::slug($provinceName)],
                ['name' => $provinceName]
            );

            foreach ($districts as $districtName) {
                District::query()->firstOrCreate(
                    [
                        'province_id' => $province->id,
                        'slug' => Str::slug($districtName),
                    ],
                    ['name' => $districtName]
                );
            }
        }
    }
}
