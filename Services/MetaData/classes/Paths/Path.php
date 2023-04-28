<?php

declare(strict_types=1);

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

namespace ILIAS\MetaData\Paths;

use ILIAS\MetaData\Paths\Steps\Step;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Path implements PathInterface, \Stringable
{
    /**
     * @var Step[]
     */
    protected array $steps;
    protected bool $is_relative;
    protected bool $leads_to_one;

    public function __construct(
        bool $is_relative,
        bool $leads_to_one,
        Step ...$steps
    ) {
        $this->is_relative = $is_relative;
        $this->leads_to_one = $leads_to_one;
        $this->steps = $steps;
    }

    /**
     * @return Step[]
     */
    public function steps(): \Generator
    {
        foreach ($this->steps as $step) {
            yield $step;
        }
    }

    public function isRelative(): bool
    {
        return $this->is_relative;
    }

    public function leadsToExactlyOneElement(): bool
    {
        return $this->leads_to_one;
    }

    public function toString(): string
    {
        $string = '';

        if ($this->leadsToExactlyOneElement()) {
            $string .= Token::LEADS_TO_EXACTLY_ONE->value;
        }
        if ($this->isRelative()) {
            $string .= Token::START_AT_CURRENT->value;
        } else {
            $string .= Token::START_AT_ROOT->value;
        }
        foreach ($this->steps() as $step) {
            $string .= Token::SEPARATOR->value;
            $string .= $this->stepToString($step);
        }

        return $string;
    }

    protected function stepToString(Step $step): string
    {
        $string = $step->name();
        foreach ($step->filters() as $filter) {
            $string .= Token::FILTER_OPEN->value;
            $string .= implode(
                Token::FILTER_VALUE_SEPARATOR->value,
                $filter->values()
            );
            $string .= Token::FILTER_CLOSE->value;
        }
        return $string;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
