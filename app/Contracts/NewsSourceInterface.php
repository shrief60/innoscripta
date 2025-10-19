<?php

namespace App\Contracts;


interface NewsSourceInterface
{

    public function fetch(): array;

}
