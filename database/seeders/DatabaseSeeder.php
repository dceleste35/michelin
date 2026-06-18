<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Alimente la base de données de l'application.
     */
    public function run(): void
    {
        // Base de démo : catalogue produits + Marc « premier arrivant » (sorties, aucun pneu).
        // Les commandes demo:tires / demo:wear font ensuite avancer le scénario.
        $this->call([
            ProductCatalogSeeder::class,
            DemoSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
