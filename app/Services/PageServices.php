<?php

namespace App\Services;
use App\Models\Page;

class PageServices
{
    public function __construct()
    {
        //
    }

    public function store(array $data)
    {
        return Page::create($data);

    }

    public function update(array $data, $id)
    {
        return Page::whereId($id)->update($data);
    }
}
