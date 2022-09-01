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

use PHPUnit\Framework\TestCase;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDBuildingBlocksTest extends TestCase
{
    public function testNonUniqueElementException(): void
    {
        $this->expectException(ilMDBuildingBlocksException::class);
        $element = new ilMDElement('name', false, []);
    }

    public function testSuperAndSubElements(): void
    {
        $low1 = new ilMDScaffoldElement('low1', true, []);
        $low2 = new ilMDElement('low2', false, [], 13);
        $middle = new ilMDElement('middle', true, [$low1, $low2]);
        $root = new ilMDRootElement(1, 2, 'type', 'root', [$middle]);

        $this->assertSame($middle, $low1->getSuperElement());
        $this->assertSame($middle, $low2->getSuperElement());
        $this->assertSame($root, $middle->getSuperElement());
        $this->assertNull($root->getSuperElement());
    }

    public function testSetSuperElementException(): void
    {
        $element = new ilMDScaffoldElement('low', false, []);
        $root1 = new ilMDRootElement(1, 2, 'type', 'root1', [$element]);

        $this->expectException(ilMDBuildingBlocksException::class);
        $root2 = new ilMDRootElement(1, 3, 'type', 'root2', [$element]);
    }
    public function testRootSetSuperElementException(): void
    {
        $root1 = new ilMDRootElement(1, 2, 'type', 'root1', []);

        $this->expectException(ilMDBuildingBlocksException::class);
        $root2 = new ilMDRootElement(1, 3, 'type', 'root2', [$root1]);
    }

    public function testSubElementsViaMagic(): void
    {
        $element1 = new ilMDScaffoldElement('low', false, []);
        $element2 = new ilMDElement('low', false, [], 13);
        $element3 = new ilMDElement('low', false, [], 7);
        $element4 = new ilMDElement('low_unique', true, []);
        $root = new ilMDRootElement(
            1,
            2,
            'type',
            'root',
            [$element1, $element2, $element3, $element4]
        );

        $this->assertSame($element4, $root->low_unique());
        $this->assertSame($element3, $root->low(7));
        $this->assertSame($element2, $root->low(13));
    }

    public function testSubElementsViaMagicException(): void
    {
        $element = new ilMDElement('low', false, [], 7);
        $root = new ilMDElement('root', true, [$element]);

        $this->expectException(ilMDBuildingBlocksException::class);
        $root->low();
    }

    public function testLeaveMarkerTrail(): void
    {
        $lowest = new ilMDElement('lowest', true, []);
        $low1 = new ilMDScaffoldElement('low1', true, []);
        $low2 = new ilMDElement('low2', false, [$lowest], 13);
        $middle = new ilMDElement('middle', true, [$low1, $low2]);
        $root = new ilMDRootElement(1, 2, 'type', 'root', [$middle]);

        $fixed_marker = new ilMDMarker();
        $low2->leaveMarkerTrail($fixed_marker);

        $this->assertInstanceOf(ilMDMarker::class, $root->getMarker());
        $this->assertNotSame($fixed_marker, $root->getMarker());
        $this->assertInstanceOf(ilMDMarker::class, $middle->getMarker());
        $this->assertNotSame($fixed_marker, $middle->getMarker());
        $this->assertSame($fixed_marker, $low2->getMarker());
        $this->assertNull($low1->getMarker());
        $this->assertNull($lowest->getMarker());
    }

    public function testLeaveMarkerTrailException(): void
    {
        $low1 = new ilMDScaffoldElement('low1', true, []);
        $low2 = new ilMDElement('low2', false, [], 13);
        $root = new ilMDRootElement(1, 2, 'type', 'root', [$low1, $low2]);

        $marker1 = new ilMDMarker();
        $low2->leaveMarkerTrail($marker1);
        $marker2 = new ilMDMarker();

        $this->expectException(ilMDBuildingBlocksException::class);
        $low1->leaveMarkerTrail($marker2);
    }
}
