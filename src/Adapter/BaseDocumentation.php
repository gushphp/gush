<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

class BaseDocumentation implements DocumentationInterface
{
    /**
     * An array with the available issue tokens and their description
     *
     * @see https://developer.github.com/v3/issues/#response-1
     *
     * @var array
     */
    public static $issueTokens = [
        "url"                                   => "Issue - url (API)",
        "labels_url"                            => "Issue - labels url (API)",
        "comments_url"                          => "Issue - comments url (API)",
        "events_url"                            => "Issue - events url (API)",
        "html_url"                              => "Issue - url (HTML)",
        "id"                                    => "Issue - internal ID",
        "number"                                => "Issue - visible ID",
        "title"                                 => "Issue - title",
        "state"                                 => "Issue - state (open / closed)",
        "comments"                              => "Issue - number of comments",
        "created_at"                            => "Issue - created at",
        "updated_at"                            => "Issue - updated at",
        "closed_at"                             => "Issue - closed at",
        "body"                                  => "Issue - description",
        "user.login"                            => "Reporter - login name",
        "user.id"                               => "Reporter - ID",
        "user.avatar_url"                       => "Reporter - avatar url",
        "user.gravatar_id"                      => "Reporter - gravatar ID",
        "user.url"                              => "Reporter - profile url (API)",
        "user.html_url"                         => "Reporter - profile url (HTML)",
        "user.followers_url"                    => "Reporter - followers url (API)",
        "user.following_url"                    => "Reporter - following url (API)",
        "user.gists_url"                        => "Reporter - gists url (API)",
        "user.starred_url"                      => "Reporter - starred url (API)",
        "user.subscriptions_url"                => "Reporter - subscriptions url (API)",
        "user.organizations_url"                => "Reporter - organizations url (API)",
        "user.repos_url"                        => "Reporter - repos url (API)",
        "user.events_url"                       => "Reporter - events url (API)",
        "user.received_events_url"              => "Reporter - received events url (API)",
        "user.type"                             => "Reporter - type",
        "user.site_admin"                       => "Reporter - is site admin?",
        "labels.X.url"                          => "Label - url (API), X refers to the labels 0 based position",
        "labels.X.name"                         => "Label - name, X refers to the labels 0 based position",
        "labels.X.color"                        => "Label - color, X refers to the labels 0 based position",
        "assignee.login"                        => "Assignee - login name",
        "assignee.id"                           => "Assignee - ID",
        "assignee.avatar_url"                   => "Assignee - avatar url",
        "assignee.gravatar_id"                  => "Assignee - gravatar ID",
        "assignee.url"                          => "Assignee - profile url (API)",
        "assignee.html_url"                     => "Assignee - profile url (HTML)",
        "assignee.followers_url"                => "Assignee - followers url (API)",
        "assignee.following_url"                => "Assignee - following url (API)",
        "assignee.gists_url"                    => "Assignee - gists url (API)",
        "assignee.starred_url"                  => "Assignee - starred url (API)",
        "assignee.subscriptions_url"            => "Assignee - subscriptions url (API)",
        "assignee.organizations_url"            => "Assignee - organizations url (API)",
        "assignee.repos_url"                    => "Assignee - repos url (API)",
        "assignee.events_url"                   => "Assignee - events url (API)",
        "assignee.received_events_url"          => "Assignee - received events url (API)",
        "assignee.type"                         => "Assignee - type",
        "assignee.site_admin"                   => "Assignee - is site admin?",
        "milestone.url"                         => "Milestone - url (API)",
        "milestone.labels_url"                  => "Milestone - labels url (API)",
        "milestone.id"                          => "Milestone - internal ID",
        "milestone.number"                      => "Milestone - ?",
        "milestone.title"                       => "Milestone - title",
        "milestone.description"                 => "Milestone - description",
        "milestone.open_issues"                 => "Milestone - number of open issues",
        "milestone.closed_issues"               => "Milestone - number of closed issues",
        "milestone.state"                       => "Milestone - state (open / closed)",
        "milestone.created_at"                  => "Milestone - created at",
        "milestone.updated_at"                  => "Milestone - updated at",
        "milestone.due_on"                      => "Milestone - due date",
        "milestone.creator.login"               => "Milestone Creator - login name",
        "milestone.creator.id"                  => "Milestone Creator - ID",
        "milestone.creator.avatar_url"          => "Milestone Creator - avatar url",
        "milestone.creator.gravatar_id"         => "Milestone Creator - gravatar ID",
        "milestone.creator.url"                 => "Milestone Creator - profile url (API)",
        "milestone.creator.html_url"            => "Milestone Creator - profile url (HTML)",
        "milestone.creator.followers_url"       => "Milestone Creator - followers url (API)",
        "milestone.creator.following_url"       => "Milestone Creator - following url (API)",
        "milestone.creator.gists_url"           => "Milestone Creator - gists url (API)",
        "milestone.creator.starred_url"         => "Milestone Creator - starred url (API)",
        "milestone.creator.subscriptions_url"   => "Milestone Creator - subscriptions url (API)",
        "milestone.creator.organizations_url"   => "Milestone Creator - organizations url (API)",
        "milestone.creator.repos_url"           => "Milestone Creator - repos url (API)",
        "milestone.creator.events_url"          => "Milestone Creator - events url (API)",
        "milestone.creator.received_events_url" => "Milestone Creator - received events url (API)",
        "milestone.creator.type"                => "Milestone Creator - type",
        "milestone.creator.site_admin"          => "Milestone Creator - is site admin?",
        "closed_by.login"                       => "Closer - login name",
        "closed_by.id"                          => "Closer - ID",
        "closed_by.avatar_url"                  => "Closer - avatar url",
        "closed_by.gravatar_id"                 => "Closer - gravatar ID",
        "closed_by.url"                         => "Closer - profile url (API)",
        "closed_by.html_url"                    => "Closer - profile url (HTML)",
        "closed_by.followers_url"               => "Closer - followers url (API)",
        "closed_by.following_url"               => "Closer - following url (API)",
        "closed_by.gists_url"                   => "Closer - gists url (API)",
        "closed_by.starred_url"                 => "Closer - starred url (API)",
        "closed_by.subscriptions_url"           => "Closer - subscriptions url (API)",
        "closed_by.organizations_url"           => "Closer - organizations url (API)",
        "closed_by.repos_url"                   => "Closer - repos url (API)",
        "closed_by.events_url"                  => "Closer - events url (API)",
        "closed_by.received_events_url"         => "Closer - received events url (API)",
        "closed_by.type"                        => "Closer - type",
        "closed_by.site_admin"                  => "Closer - is site admin?",
    ];

    /**
     * {@inheritdoc}
     */
    public function getIssueTokens()
    {
        return static::$issueTokens;
    }
}
