<?php

namespace Database\Factories\Inmopro;

use App\Models\Inmopro\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Team '.$this->faker->unique()->word(),
            'code' => strtoupper($this->faker->unique()->lexify('TEAM_????')),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'sort_order' => $this->faker->numberBetween(1, 20),
            'is_active' => true,
            'group_sales_goal' => 0,
        ];
    }
}
