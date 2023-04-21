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
use classes\Elements\Data\ilMDData;
use classes\Elements\Markers\ilMDMarker;
use classes\Elements\ilMDElement;
use classes\Elements\ilMDRootElement;
use classes\Elements\ilMDScaffoldElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDBuildingBlocksTest extends TestCase
{
    public function testSuperElements(): void
    {
        $data = $this->createMock(ilMDData::class);
        $low1 = new ilMDScaffoldElement('low1', true, []);
        $low2 = new ilMDElement('low2', false, [], 13, $data);
        $middle = new ilMDElement('middle', true, [$low1, $low2], 13, $data);
        $root = new ilMDRootElement(1, 2, 'type', 'root', [$middle], $data);

        $this->assertSame($middle, $low1->getSuperElement());
        $this->assertSame($middle, $low2->getSuperElement());
        $this->assertSame($root, $middle->getSuperElement());
        $this->assertNull($root->getSuperElement());
    }

    public function testRootSetSuperElementException(): void
    {
        $data = $this->createMock(ilMDData::class);
        $root1 = new ilMDRootElement(1, 2, 'type', 'root1', [], $data);

        $this->expectException(ilMDBuildingBlocksException::class);
        $root2 = new ilMDRootElement(1, 3, 'type', 'root2', [$root1], $data);
    }

    public function testGetSubElement(): void
    {
        $data = $this->createMock(ilMDData::class);
        $element1 = new ilMDScaffoldElement('low', false, []);
        $element2 = new ilMDElement('low', false, [], 13, $data);
        $element3 = new ilMDElement('low', false, [], 7, $data);
        $element4 = new ilMDElement('low_unique', true, [], 78, $data);
        $element5 = new ilMDElement('name', true, [], 7, $data);
        $root = new ilMDRootElement(
            1,
            2,
            'type',
            'root',
            [$element1, $element2, $element3, $element4, $element5],
            $data
        );

        $this->assertSame(
            [$element1, $element2, $element3, $element4, $element5],
            $root->getSubElements()
        );
        $this->assertSame([$element4], $root->getSubElements('low_unique'));
        $this->assertSame(
            [$element1, $element2, $element3],
            $root->getSubElements('low')
        );
        $this->assertSame([$element3, $element5], $root->getSubElements('', 7));
        $this->assertSame([$element3], $root->getSubElements('low', 7));
        $this->assertSame([$element2], $root->getSubElements('low', 13));
        $this->assertSame([], $root->getSubElements('something'));
        $this->assertSame([], $root->getSubElements('low', 143));
    }

    public function testDeleteSubElement(): void
    {
        $data = $this->createMock(ilMDData::class);
        $element1 = new ilMDScaffoldElement('low', false, []);
        $element2 = new ilMDElement('low', false, [], 13, $data);
        $element3 = new ilMDElement('low', false, [], 7, $data);
        $element4 = new ilMDElement('low_unique', true, [], 78, $data);
        $element5 = new ilMDElement('name', true, [], 7, $data);
        $root = new ilMDRootElement(
            1,
            2,
            'type',
            'root',
            [$element1, $element2, $element3, $element4, $element5],
            $data
        );

        $root->deleteFromSubElements($element2);
        $this->assertSame(
            [$element1, $element3, $element4, $element5],
            $root->getSubElements()
        );
    }

    public function testAddScaffoldToSubElements(): void
    {
        $data = $this->createMock(ilMDData::class);
        $root = new ilMDElement('root', true, [], 7, $data);
        $scaffold = new ilMDScaffoldElement('scaffold', false, []);
        $root->addScaffoldToSubElements($scaffold);

        $this->assertSame(
            [$scaffold],
            $root->getSubElements()
        );
        $this->assertSame(
            $root,
            $scaffold->getSuperElement()
        );
    }

    public function testAddScaffoldToSubElementsException(): void
    {
        $data = $this->createMock(ilMDData::class);
        $root = new ilMDElement('root', true, [], 7, $data);
        $scaffold = new ilMDScaffoldElement('scaffold', false, []);
        $root->addScaffoldToSubElements($scaffold);

        $this->expectException(ilMDBuildingBlocksException::class);
        $root->addScaffoldToSubElements($scaffold);
    }

    public function testLeaveMarkerTrail(): void
    {
        $data = $this->createMock(ilMDData::class);
        $lowest1 = new ilMDScaffoldElement('lowest1', true, []);
        $lowest2 = new ilMDElement('lowest2', true, [], 7, $data);
        $low1 = new ilMDScaffoldElement('low1', true, [$lowest1]);
        $low2 = new ilMDElement('low2', false, [$lowest2], 13, $data);
        $middle = new ilMDElement('middle', true, [$low1, $low2], 78, $data);
        $root = new ilMDRootElement(1, 2, 'type', 'root', [$middle], $data);

        $first_marker1 = $this->createMock(ilMDMarker::class);
        $trail_marker1 = $this->createMock(ilMDMarker::class);
        $low2->leaveMarkerTrail($first_marker1, $trail_marker1);

        $first_marker2 = $this->createMock(ilMDMarker::class);
        $trail_marker2 = $this->createMock(ilMDMarker::class);
        $lowest1->leaveMarkerTrail($first_marker2, $trail_marker2);

        $this->assertSame($trail_marker1, $root->getMarker());
        $this->assertSame($trail_marker1, $middle->getMarker());
        $this->assertSame($first_marker1, $low2->getMarker());
        $this->assertSame($trail_marker2, $low1->getMarker());
        $this->assertSame($first_marker2, $lowest1->getMarker());
        $this->assertNull($lowest2->getMarker());
    }
}
