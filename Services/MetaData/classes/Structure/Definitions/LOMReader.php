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

namespace ILIAS\MetaData\Structure\Definitions;

use ILIAS\MetaData\Elements\Data\Type;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class LOMReader implements ReaderInterface
{
    protected array $definition_array;

    public function __construct(
        ?array $definition_array = null
    ) {
        if (!is_null($definition_array)) {
            $this->definition_array = $definition_array;
            return;
        }
        $this->definition_array = $this->getDefinitionArray();
    }

    protected function getDefinitionArray(): array
    {
        return require("..\..\StructureDefinition\LOMStructure.php");
    }

    public function definition(): DefinitionInterface
    {
        return new Definition(
            $this->name(),
            $this->unique(),
            $this->dataType()
        );
    }

    /**
     * @return LOMReader[]
     */
    public function subDefinitions(): \Generator
    {
        $sub_definitions = $this->definition_array['sub'] ?? [];
        foreach ($this->definition_array['sub'] as $sub_definition) {
            $clone = clone $this;
            $clone->definition_array = $sub_definition;
            yield $clone;
        }
    }

    protected function name(): string
    {
        if (!isset($this->definition_array['name'])) {
            $this->throwStructureException('missing name');
        }
        return $this->definition_array['name'];
    }

    protected function unique(): bool
    {
        if (!isset($this->definition_array['unique'])) {
            $this->throwStructureException('missing unique');
        }
        return (bool) $this->definition_array['unique'];
    }

    protected function dataType(): Type
    {
        if (
            !isset($this->definition_array['type']) ||
            is_null($type = Type::tryFrom($this->definition_array['type']))
        ) {
            $this->throwStructureException('invalid data type');
        }
        return $type;
    }

    protected function throwStructureException(string $error): void
    {
        throw new \ilMDStructureException(
            'LOM definition is invalid:' . $error
        );
    }
}
