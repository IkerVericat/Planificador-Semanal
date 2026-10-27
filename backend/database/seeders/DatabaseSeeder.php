<?php

namespace Database\Seeders;

use App\Actions\CreateDefaultCategories;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Usuario de prueba demo@example.com / password con tareas en la semana actual.
     */
    public function run(CreateDefaultCategories $createDefaultCategories): void
    {
        $user = User::factory()->create([
            'name' => 'Demo',
            'email' => 'demo@example.com',
        ]);

        $createDefaultCategories($user);
        $categories = $user->categories()->pluck('id', 'name');
        $monday = CarbonImmutable::now('Europe/Madrid')->startOfWeek();

        // [día (0 = lunes), hora, título, categoría, recurrencia, días]
        $tasks = [
            [0, '09:00', 'Curso', 'Fijo', 'weekly', [1, 2, 4]],
            [0, '12:00', 'Pagar agua y gas', 'Urgente', 'none', null],
            [0, '19:00', 'Sesión psicológica', 'Fijo', 'none', null],
            [0, '20:00', 'Comprar arena para gatos', 'Importante', 'none', null],
            [1, '11:00', 'Mandar correcciones', 'Importante', 'none', null],
            [1, '18:00', 'Clase de inglés', 'Nueva', 'weekly', [2, 4]],
            [1, '19:30', 'Limpiar alacena', 'Importante', 'none', null],
            [3, '11:00', 'Escribir al plomero', 'Importante', 'none', null],
            [3, '18:00', 'Mandar reportes', 'Urgente', 'none', null],
            [4, null, 'Revisar presupuesto del mes', 'Urgente', 'monthly', null],
        ];

        foreach ($tasks as [$offset, $time, $title, $category, $recurrence, $days]) {
            $user->tasks()->create([
                'title' => $title,
                'task_date' => $monday->addDays($offset)->toDateString(),
                'start_time' => $time,
                'category_id' => $categories[$category],
                'recurrence' => $recurrence,
                'recurrence_days' => $days,
            ]);
        }
    }
}
