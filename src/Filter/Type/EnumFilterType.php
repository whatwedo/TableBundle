<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Filter\Type;

use Symfony\Contracts\Translation\TranslatorInterface;

class EnumFilterType extends ChoiceFilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public function __construct(string $column, array $enumCases, ?TranslatorInterface $translator = null, array $joins = [])
    {
        $choices = [];

        foreach ($enumCases as $item) {
            $choices[$item->value] = $translator->trans($item->value);
        }

        parent::__construct($column, $choices, $joins);
    }
}
