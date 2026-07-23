<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The plain-text password given to every factory-made user.
     * Tests should reference this instead of hard-coding the value.
     */
    public const PASSWORD = '12345678';

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    private static array $khmerFirstNames = [
        'ដារា', 'សុភា', 'រតនា', 'សុខា', 'វិរៈ', 'ចំរើន', 'កុសល', 'បុណ្ណា', 'រិទ្ធី', 'សំណាង',
        'វុទ្ធី', 'ចន្ទា', 'មករា', 'បូរី', 'វិច្ឆា', 'សុវណ្ណ', 'សេរី', 'ភារុណ', 'ឧត្តម', 'ពិសិទ្ធ',
        'ស្រីមុំ', 'ចន្ធី', 'គន្ធា', 'សុផា', 'សុជាតា', 'សុធា', 'ចន្នារី', 'ស្រីលក្ខ', 'បុប្ផា',
        'ចេន្តា', 'ស្រីនិច', 'គីមហាក់', 'ស្រីពេជ្រ', 'ស្រីមនី', 'ដាវ៉ាន់', 'លីដា', 'នីម៉ុល', 'ប័ញ្ញា',
        'ស្រីផុវ', 'នីតា', 'រស្មី', 'ស្រីណែត', 'ចាន់ថា', 'ពេជ្រ', 'ហេង', 'ម៉ានិត', 'ភក្ត្រា', 'លក្ខិណា',
    ];

    private static array $khmerLastNames = [
        'ចំ', 'ហេង', 'សុក', 'ចាន់', 'លីម', 'ពេជ្រ', 'មាំ', 'យឹម', 'កែវ', 'រស់',
        'ន្គេត', 'អ៊ូយ', 'អ៊ិត', 'អ៊ូក', 'ផុវ', 'ភន', 'តេព', 'ឈុន', 'កុង', 'មាស',
        'នេម', 'ប្រាក់', 'សេង', 'ទូច', 'វ៉ាន់', 'វង់', 'ខៀវ', 'ស៊ុន', 'ណន', 'ស្រេង',
    ];

    public function definition(): array
    {
        $firstName = fake()->randomElement(self::$khmerFirstNames);
        $lastName  = fake()->randomElement(self::$khmerLastNames);

        return [
            'name' => $lastName . ' ' . $firstName,
            'email' => fake()->unique()->userName() . '@gmail.com',
            'email_verified_at' => now(),
            'password' => static::$password ??= self::PASSWORD,
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
        ];
    }

    /**
     * Every user gets the base `user` role after creation; role states
     * (below) replace it via syncRoles.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn ($user) => $user->assignRole('user'));
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(fn ($user) => $user->syncRoles(['super_admin']));
    }

    public function admin(): static
    {
        return $this->afterCreating(fn ($user) => $user->syncRoles(['admin']));
    }

    public function moderator(): static
    {
        return $this->afterCreating(fn ($user) => $user->syncRoles(['moderator']));
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }
}
