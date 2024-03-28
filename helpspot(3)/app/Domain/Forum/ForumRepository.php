<?php

namespace HS\Domain\Forum;

class ForumRepository
{
    public function topicsFromSearch(array $topics)
    {
        return Topic::with(['posts' => function ($query) {
            // "Trick" to get only the
            // initial Post from a Topic
            $query->groupBy('xTopicId')
                ->orderBy('dtGMTPosted', 'DESC');
        }, 'forum' => function ($query) {
        }])->find($topics);
    }
}
