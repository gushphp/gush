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
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class MergeRequest extends Model\MergeRequest
{
    public static function castFrom(Model\MergeRequest $mr)
    {
        $cast = new static($mr->project, $mr->id, $mr->getClient());

        foreach (static::$properties as $property) {
            $cast->$property = $mr->$property;
        }

        return $cast;
    }

    public function toArray()
    {
        $mr = [
            'url' => null,
            'number' => null,
            'state' => null,
            'title' => null,
            'body' => null,
            'labels' => [],
            'milestone' => null,
            'created_at' => new \DateTime(),
            'updated_at' => null,
            'user' => null,
            'assignee' => null,
            'merge_commit' => null,
            'merged' => false,
            'merged_by' => null,
            'head' => [
                'ref' => null,
                'sha' => null,
                'user' => null,
                'repo' => null,
            ],
            'base' => [
                'ref' => null,
                'label' => null,
                'sha' => null,
                'repo' => null,
                'user' => null,
            ],
        ];

        foreach (static::$properties as $property) {
            switch ($property) {
                case 'id':
                    $mr['number'] = $this->$property;
                    break;

                case 'author':
                    $mr['author'] = $this->{$property}->username;
                    break;

                case 'source_project_id':
                    if ($this->project->id === $this->$property) {
                        $sourceProject = $this->project;
                    } else {
                        $sourceProject = Model\Project::fromArray(
                            $this->getClient(),
                            $this->getClient()->api('projects')->show($this->$property)
                        );
                    }

                    $mr['head']['user'] = $sourceProject->namespace->path;
                    $mr['head']['repo'] = $sourceProject->name;
                    break;

                case 'state':
                    $mr['state'] = 'opened' === $this->$property ? 'open' : $this->$property;
                    $mr['merged'] = $this->$property === 'merged';
                    break;

                case 'source_branch':
                    $mr['head']['ref'] = $this->$property;
                    break;

                case 'target_project_id':
                    if ($this->project->id === $this->$property) {
                        $targetProject = $this->project;
                    } else {
                        $targetProject = Model\Project::fromArray(
                            $this->getClient(),
                            $this->getClient()->api('projects')->show($this->$property)
                        );
                    }

                    $mr['base']['user'] = $targetProject->namespace->path;
                    $mr['base']['repo'] = $targetProject->name;
                    break;

                case 'target_branch':
                    $mr['base']['ref'] = $this->$property;
                    break;

                case 'created_at':
                case 'updated_at':
                    // remove microseconds precision (2014-11-28T08:43:59.354Z -> 2014-11-28T08:43:59Z)
                    $mr[$property] = new \DateTime(preg_replace('{\.\d+}', '', $this->$property));
                    break;

                case 'description':
                    $mr['body'] = $this->$property;
                    break;

                default:
                    $mr[$property] = $this->$property;
                    break;
            }
        }

        return $mr;
    }
}
