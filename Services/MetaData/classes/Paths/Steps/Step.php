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

namespace ILIAS\MetaData\Paths\Steps;

use ILIAS\MetaData\Paths\Filters\FilterInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Step implements StepInterface
{
    protected string|StepToken $name;
    /**
     * @var FilterInterface[]
     */
    protected array $filters;

    public function __construct(
        string|StepToken $name,
        FilterInterface ...$filters
    ) {
        $this->name = $name;
        $this->filters = $filters;
    }

    public function name(): string|StepToken
    {
        return $this->name;
    }

    /**
     * @return FilterInterface[]
     */
    public function filters(): \Generator
    {
        yield from $this->filters;
    }
}
