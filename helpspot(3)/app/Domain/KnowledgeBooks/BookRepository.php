<?php

namespace HS\Domain\KnowledgeBooks;

class BookRepository
{
    public function pagesFromSearch(array $pages)
    {
        return Page::with(['chapter' => function ($query) {
            $query->with('book');
        }])->find($pages);
    }
}
