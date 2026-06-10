<?php
namespace Database\Seeders;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['libelle' => 'Voitures', 'icon' => 'bxs-car', 'description' => 'Voitures d\'occasion'],
            ['libelle' => 'Motos', 'icon' => 'bxs-motorcycle', 'description' => 'Motos et scooters'],
            ['libelle' => 'Maisons', 'icon' => 'bxs-home', 'description' => 'Maisons et appartements'],
            ['libelle' => 'Électroniques', 'icon' => 'bxs-devices', 'description' => 'Téléphones, ordinateurs, gadgets'],
            ['libelle' => 'Livres', 'icon' => 'bxs-book', 'description' => 'Livres d\'occasion'],
            ['libelle' => 'Divers', 'icon' => 'bxs-category', 'description' => 'Autres articles'],
        ];
        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => Str::slug($cat['libelle'])],
                [
                    'libelle' => $cat['libelle'],
                    'icon' => $cat['icon'],
                    'description' => $cat['description'],
                ]
            );
        }
    }
}
