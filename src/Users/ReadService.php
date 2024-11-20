<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Users;

use Rumur\WordPress\CosmoUsers\Collection;
use Rumur\WordPress\CosmoUsers\Exception;

/**
 * Interface of Users' ReadService.
 *
 * @template TUser
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Users
 */
interface ReadService
{
    /**
     * Retrieves the users from the source.
     *
     * @return Collection<int,TUser> The list of users.
     */
    public function users(int $limit, int $offset = 0): Collection;

    /**
     * Retrieves the user by the ID.
     *
     * @param int $id Gets the user by the given id.
     *
     * @throws Exception If the user was not found.
     *
     * @return TUser
     */
    public function userById(int $id);
}
