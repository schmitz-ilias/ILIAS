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

namespace ILIAS\MetaData\Elements\Base;

use ILIAS\MetaData\Structure\Definitions\Definition;
use ILIAS\MetaData\Elements\NoID;
use ILIAS\MetaData\Structure\Definitions\DefinitionInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
abstract class BaseElement implements BaseElementInterface
{
    /**
     * @var BaseElement[]
     */
    private array $sub_elements;
    private ?BaseElement $super_element = null;
    private DefinitionInterface $definition;
    private int|NoID $md_id;

    public function __construct(
        int|NoID $md_id,
        DefinitionInterface $definition,
        BaseElement ...$sub_elements
    ) {
        foreach ($sub_elements as $sub_element) {
            $this->addSubElement($sub_element);
        }
        $this->definition = $definition;
        $this->md_id = $md_id;
    }

    public function __clone()
    {
        $this->setSuperElement(null);
        $map = function (BaseElement $arg) {
            $arg = clone $arg;
            $arg->setSuperElement($this);
            return $arg;
        };
        $this->sub_elements = array_map(
            $map,
            $this->sub_elements
        );
    }

    public function getMDID(): int|NoID
    {
        return $this->md_id;
    }

    /**
     * @return BaseElement[]
     */
    public function getSubElements(): \Generator
    {
        foreach ($this->sub_elements as $sub_element) {
            yield $sub_element;
        }
    }

    protected function addSubElement(BaseElement $sub_element): void
    {
        $sub_element->setSuperElement($this);
        $this->sub_elements[] = $sub_element;
    }

    public function getSuperElement(): ?BaseElement
    {
        return $this->super_element;
    }

    protected function setSuperElement(?BaseElement $super_element): void
    {
        if ($this->isRoot()) {
            throw new \ilMDElementsException(
                'Metadata root can not have a super element.'
            );
        }
        $this->super_element = $super_element;
    }

    public function isRoot(): bool
    {
        return $this->getMDID() === NoID::ROOT;
    }

    public function getDefinition(): DefinitionInterface
    {
        return $this->definition;
    }
}
