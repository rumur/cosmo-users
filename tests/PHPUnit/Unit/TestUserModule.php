<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Tests\Unit;

use Rumur\WordPress\CosmoUsers\Support\Http\Concurrent;
use Rumur\WordPress\CosmoUsers\Support\Transformer\FractalAdapter;
use Rumur\WordPress\CosmoUsers\Users\Services\JsonPlaceholder;

class TestUserModule extends TestCase
{
    public function usersDataProvider(): array
    {
        return [
            [
                array_map(
                    static fn(int $userNumber): array => [
                        'id' => $userNumber,
                        'name' => "User {$userNumber}",
                        'username' => "user_{$userNumber}",
                        'email' => "example-" . $userNumber . "@example.com",
                        'phone' => "+1-555-555-555{$userNumber}",
                        'website' => "https://example.com/user/{$userNumber}",
                        'company' => [
                            'name' => "Company {$userNumber}",
                            'catchPhrase' => "Slogan {$userNumber}",
                        ],
                        'address' => [
                            'street' => "Street {$userNumber}",
                            'suite' => "Suite {$userNumber}",
                            'city' => "City {$userNumber}",
                            'zipcode' => "Zipcode {$userNumber}",
                        ],
                    ],
                    range(1, 3)
                )
            ],
        ];
    }

    /**
     * @dataProvider usersDataProvider
     */
    public function testUsers(array $users): void
    {
        $client = new Concurrent();
        $transformer = new FractalAdapter(new \League\Fractal\Manager());
        $service = new JsonPlaceholder(
            $client,
            $transformer,
        );

        $testDataInjector = static function ($preempt, $request, $url) use ($users): array|false {
            return match ($url) {
                'https://jsonplaceholder.typicode.com/users?_limit=10&_start=0',
                'https://jsonplaceholder.typicode.com/users/?_limit=10&_start=0' => [
                    'body' => json_encode($users),
                    'response' => [
                        'code' => 200,
                    ],
                ],
                'https://jsonplaceholder.typicode.com/users/1' => [
                    'body' => json_encode($users[0]),
                    'response' => [
                        'code' => 200,
                    ],
                ],
                default => $preempt,
            };
        };

        add_filter('pre_http_request', $testDataInjector, 10, 3);

        $singleUser = $service->userById(1);
        $userCollection = $service->users(3);

        remove_filter('pre_http_request', $testDataInjector);

        $this->assertIsArray($singleUser);
        $this->assertArrayHasKey('id', $singleUser);
        $this->assertArrayHasKey('name', $singleUser);
        $this->assertArrayHasKey('company', $singleUser);
        $this->assertArrayHasKey('slogan', $singleUser['company']);

        $this->assertFalse($userCollection->isEmpty());
        $this->assertCount(3, $userCollection);
        $this->assertArrayHasKey('id', $userCollection->first());
        $this->assertArrayHasKey('name', $userCollection->first());
        $this->assertArrayHasKey('company', $userCollection->first());
        $this->assertArrayHasKey('slogan', $userCollection->first()['company']);
    }
}
