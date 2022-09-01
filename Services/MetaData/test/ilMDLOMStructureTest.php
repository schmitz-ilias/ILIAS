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
class ilMDLOMStructureTest extends TestCase
{
    public function testMovePointerToSubElement(): void
    {
        $structure = new ilMDLOMStructure();
        $this->assertSame(
            'lom',
            $structure->getNameAtPointer()
        );
        $this->assertContains(
            'general',
            $structure->getSubElementsAtPointer()
        );
        $structure->movePointerToSubElement('general');
        $this->assertSame(
            'general',
            $structure->getNameAtPointer()
        );
        $this->assertContains(
            'language',
            $structure->getSubElementsAtPointer()
        );
        $structure->movePointerToSubElement('language');
        $this->assertSame(
            'language',
            $structure->getNameAtPointer()
        );
        $this->assertSame(
            ['lom', 'general', 'language'],
            $structure->getPointerPath()
        );
    }

    public function testMovePointerToSubElementException(): void
    {
        $structure = new ilMDLOMStructure();
        $this->assertNotContains(
            'nonsense',
            $structure->getSubElementsAtPointer()
        );
        $this->expectException(ilMDStructureException::class);
        $structure->movePointerToSubElement('nonsense');
    }

    public function testMovePointerToSuperElement(): void
    {
        $structure = new ilMDLOMStructure();
        $structure->movePointerToSubElement('general')
                  ->movePointerToSubElement('language')
                  ->movePointerToSuperElement();
        $this->assertSame(
            'general',
            $structure->getNameAtPointer()
        );
        $this->assertSame(
            false,
            $structure->isPointerAtRootElement()
        );
        $structure->movePointerToSuperElement();
        $this->assertSame(
            'lom',
            $structure->getNameAtPointer()
        );
        $this->assertSame(
            true,
            $structure->isPointerAtRootElement()
        );
    }

    public function testMovePointerToSuperElementException(): void
    {
        $structure = new ilMDLOMStructure();
        $this->expectException(ilMDStructureException::class);
        $structure->movePointerToSuperElement();
    }

    public function testMovePointerToRoot(): void
    {
        $structure = new ilMDLOMStructure();
        $structure->movePointerToSubElement('general')
                  ->movePointerToSubElement('language')
                  ->movePointerToSuperElement();
        $structure->movePointerToRoot();
        $this->assertSame(
            true,
            $structure->isPointerAtRootElement()
        );
    }

    public function testGetValuesAtPointer(): void
    {
        $structure = new ilMDLOMStructure();
        $structure->movePointerToSubElement('general')
                  ->movePointerToSubElement('language');
        $this->assertSame(
            false,
            $structure->isUniqueAtPointer()
        );
        $this->assertSame(
            ilMDLOMDataFactory::TYPE_LANG,
            $structure->getTypeAtPointer()
        );
        $this->assertSame(
            [],
            $structure->getSubElementsAtPointer()
        );
        $structure->movePointerToSuperElement()
                  ->movePointerToSubElement('title');
        $this->assertSame(
            true,
            $structure->isUniqueAtPointer()
        );
        $this->assertSame(
            ilMDLOMDataFactory::TYPE_NONE,
            $structure->getTypeAtPointer()
        );
        $this->assertSame(
            ['string', 'language'],
            $structure->getSubElementsAtPointer()
        );
    }

    public function testMarkersAtPointer(): void
    {
        $marker_general = new ilMDMarker();
        $marker_language = new ilMDMarker();
        $structure = new ilMDLOMStructure();
        $structure->movePointerToSubElement('general')
                  ->setMarkerAtPointer($marker_general)
                  ->movePointerToSubElement('language')
                  ->setMarkerAtPointer($marker_language);
        $this->assertSame(
            $marker_language,
            $structure->getMarkerAtPointer()
        );
        $structure->movePointerToSuperElement();
        $this->assertSame(
            $marker_general,
            $structure->getMarkerAtPointer()
        );
        $structure->movePointerToSuperElement();
        $this->assertNull($structure->getMarkerAtPointer());
    }

    public function testReadModeMarkerException(): void
    {
        $structure = new ilMDLOMStructure();
        $structure->switchToReadMode();
        $this->expectException(ilMDStructureException::class);
        $structure->setMarkerAtPointer(new ilMDMarker());
    }
}
