<?php

namespace App\Validator\Constraint;

use App\Repository\ArtistRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueSlugValidator extends ConstraintValidator
{
    public function __construct(
        private ArtistRepository $artistRepository
    ) {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueSlug) {
            throw new UnexpectedTypeException($constraint, UniqueSlug::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $existingArtist = $this->artistRepository->findOneBy(['slug' => $value]);

        if ($existingArtist) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
