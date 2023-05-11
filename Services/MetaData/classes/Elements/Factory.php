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

namespace ILIAS\MetaData\Elements;

use ILIAS\MetaData\Elements\Element;
use ILIAS\MetaData\Elements\Data\DataFactory;
use ILIAS\MetaData\Elements\NoID;
use ILIAS\MetaData\Structure\Definitions\DefinitionInterface;
use ILIAS\MetaData\Elements\RessourceID\RessourceID;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Factory
{
    protected DataFactory $data_factory;

    public function __construct(DataFactory $data_factory)
    {
        $this->data_factory = $data_factory;
    }

    public function element(
        int $md_id,
        DefinitionInterface $definition,
        string $data_value,
        Element ...$sub_elements
    ): Element {
        return new Element(
            $md_id,
            $definition,
            $this->data_factory->data($definition->dataType(), $data_value),
            ...$sub_elements
        );
    }

    public function root(
        DefinitionInterface $definition,
        Element ...$sub_elements
    ): ElementInterface {
        return new Element(
            NoID::ROOT,
            $definition,
            $this->data_factory->null(),
            ...$sub_elements
        );
    }

    public function set(
        int $obj_id,
        int $sub_id,
        string $type,
        ElementInterface $root
    ): SetInterface {
        return new Set(
            new RessourceID($obj_id, $sub_id, $type),
            $root
        );
    }
}
