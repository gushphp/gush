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
class Issue extends Model\Issue
{
    public static function castFrom(Model\Issue $issue)
    {
        $cast = new static($issue->project, $issue->id, $issue->getClient());

        foreach (static::$properties as $property) {
            $cast->$property = $issue->$property;
        }

        return $cast;
    }

    public function toArray()
    {
        $issue = array();

        foreach (static::$properties as $property) {
            switch ($property) {
                case 'id':
                    $issue['number'] = $this->$property;
                    break;

                case 'author':
                    $issue['user'] = $this->$property->username;
                    break;

                case 'assignee':
                    if (null !== $this->$property) {
                        $issue['assignee'] = $this->$property->username;
                    } else {
                        $issue['assignee'] = null;
                    }
                    break;

                case 'description':
                    $issue['body'] = $this->$property;
                    break;

                case 'created_at':
                case 'updated_at':
                    $issue[$property] = new \DateTime($this->$property);
                    break;

                default:
                    $issue[$property] = $this->$property;
            }

            $issue['url'] = '';
            $issue['pull_request'] = false;
        }

        return $issue;
    }
}
