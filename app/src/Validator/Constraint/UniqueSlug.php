<?php

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueSlug extends Constraint
{
    public string $message = 'This slug is already in use.';
}
