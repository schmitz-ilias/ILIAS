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

namespace ILIAS\MetaData\Elements\Structure;

use PHPUnit\Framework\TestCase;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class StructureFactoryTest extends TestCase
{
    public function testCreateElement(): void
    {
        $factory = new StructureFactory();
        $struct = $factory->structure(new MockDefinition('name'));

        $this->assertInstanceOf(StructureElement::class, $struct);
        $this->assertFalse($struct->isRoot());
    }

    public function testCreateRoot(): void
    {
        $factory = new StructureFactory();
        $struct = $factory->root(new MockDefinition('name'));

        $this->assertInstanceOf(StructureElementInterface::class, $struct);
        $this->assertTrue($struct->isRoot());
    }

    public function testCreateSet(): void
    {
        $factory = new StructureFactory();
        $root = $factory->root(new MockDefinition('name'));
        $set = $factory->set($root);

        $this->assertInstanceOf(StructureSetInterface::class, $set);
    }
}
