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

namespace ILIAS\MetaData\Elements\Scaffolds;

use ILIAS\MetaData\Structure\Definitions\DefinitionInterface;
use ILIAS\MetaData\Elements\Element;
use ILIAS\MetaData\Elements\Data\DataFactory;
use ILIAS\MetaData\Elements\NoID;
use ILIAS\MetaData\Elements\ElementInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ScaffoldFactory
{
    protected DataFactory $data_factory;

    public function __construct(DataFactory $data_factory)
    {
        $this->data_factory = $data_factory;
    }

    public function scaffold(DefinitionInterface $definition): ElementInterface
    {
        return new Element(
            NoID::SCAFFOLD,
            $definition,
            $this->data_factory->null()
        );
    }
}
