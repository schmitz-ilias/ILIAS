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

use ILIAS\Refinery\Constraint;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDData
{
    protected string $type;
    protected string $value;
    protected Constraint $constraint;

    protected ?ilMDPathRelative $path_to_condition;

    public function __construct(
        string $type,
        string $value,
        Constraint $constraint,
        ?ilMDPathRelative $path_to_condition = null
    ) {
        $this->type = $type;
        $this->value = $value;
        $this->constraint = $constraint;
        $this->path_to_condition = $path_to_condition;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Some vocabularies are only applicable if a different MD element
     * takes a specific value. This returns the path to that different
     * MD element.
     */
    public function getPathToConditionElement(): ?ilMDPathRelative
    {
        return $this->path_to_condition;
    }

    public function getError(
        ?string $condition_value = null
    ): ?string {
        if (
            isset($this->path_to_condition) &&
            !isset($condition_value)
        ) {
            throw new ilMDBuildingBlocksException(
                'A conditional value can only be checked if the ' .
                'value it is conditional on is supplied.'
            );
        }
        if (isset($condition_value)) {
            return $this->constraint->problemWith(
                [$this->value, $condition_value]
            );
        }
        return $this->constraint->problemWith($this->value);
    }
}
