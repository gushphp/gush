<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Model;

use Gitlab\Model;

/**
 * @author Julien Bianchi <contact@jubianchi.fr>
 */
class User extends Model\User
{
    public static function castFrom(Model\User $user)
    {
        $cast = new static($user->id, $user->getClient());

        foreach (static::$properties as $property) {
            $cast->$property = $user->$property;
        }

        return $cast;
    }

    public function toArray()
    {
        $user = [];

        foreach (static::$properties as $property) {
            switch ($property) {
                case 'username':
                    $user['login'] = $this->$property;
                    break;

                default:
                    $user[$property] = $this->$property;
            }
        }

        return $user;
    }
}
