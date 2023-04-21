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

namespace ILIAS\MetaData\Vocabularies;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Vocabulary implements VocabularyInterface
{
    protected string $source;

    /**
     * @var string[]
     */
    protected array $values;
    protected ?string $condition_value;

    public function __construct(
        string $source,
        array $values,
        ?string $condition_value = null
    ) {
        $this->source = $source;
        $this->values = $values;
        $this->condition_value = $condition_value;
    }

    public function source(): string
    {
        return $this->source;
    }

    /**
     * @return \Generator|string[]
     */
    public function values(): \Generator
    {
        foreach ($this->values as $value) {
            yield $value;
        }
    }

    public function isConditional(): bool
    {
        return isset($this->condition_value);
    }

    public function conditionValue(): ?string
    {
        return $this->condition_value;
    }
}
