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
use classes\Elements\Data\ilMDLOMDataFactory;

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
    }

    public function testPointerPath(): void
    {
        $structure1 = new ilMDLOMStructure();
        $structure2 = new ilMDLOMStructure();
        $path_factory = new ilMDPathFactory();
        $structure1->movePointerToSubElement('general')
                  ->movePointerToSubElement('language');
        $structure2->movePointerToEndOfPath(
            $path_factory->getStructurePointerAsPath($structure1)
        );
        $this->assertSame(
            'language',
            $structure2->getNameAtPointer()
        );
        $this->assertSame(
            'language',
            $structure1->getNameAtPointer()
        );
        $this->assertSame(
            'general',
            $structure2->movePointerToSuperElement()
                       ->getNameAtPointer()
        );

        $structure2->movePointerToEndOfPath(
            $path_factory->getStructurePointerAsPath(
                $structure1->movePointerToRoot()
            )
        );
        $this->assertTrue($structure2->isPointerAtRootElement());
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
            ilMDLOMDataFactory::TYPE_NULL,
            $structure->getTypeAtPointer()
        );
        $this->assertSame(
            ['string', 'language'],
            $structure->getSubElementsAtPointer()
        );
    }

    public function testTagsAtPointer(): void
    {
        $tag_general = new ilMDTag();
        $tag_language = new ilMDTag();
        $structure = new ilMDLOMStructure();
        $structure->movePointerToSubElement('general')
                  ->setTagAtPointer($tag_general)
                  ->movePointerToSubElement('language')
                  ->setTagAtPointer($tag_language);
        $this->assertSame(
            $tag_language,
            $structure->getTagAtPointer()
        );
        $structure->movePointerToSuperElement();
        $this->assertSame(
            $tag_general,
            $structure->getTagAtPointer()
        );
        $structure->movePointerToSuperElement();
        $this->assertNull($structure->getTagAtPointer());
    }

    public function testReadModeTagException(): void
    {
        $structure = new ilMDLOMStructure();
        $structure->switchToReadMode();
        $this->expectException(ilMDStructureException::class);
        $structure->setTagAtPointer(new ilMDTag());
    }

    public function testLOMDatabaseDictionary(): void
    {
        $tag = $this->createMock(ilMDDatabaseTag::class);
        $tag_factory = $this->createMock(ilMDTagFactory::class);
        $tag_factory
            ->expects($this->any())
            ->method('database')
            ->willReturn($tag);

        $db = $this->createMock(ilDBInterface::class);
        $query_provider = $this->createMock(
            ilMDLOMDatabaseQueryProvider::class
        );

        $dictionary = new ilMDLOMDatabaseDictionary(
            $tag_factory,
            $db,
            $query_provider
        );
        $structure = $dictionary->getStructure();

        $this->assertInstanceOf(
            ilMDDatabaseTag::class,
            $structure->getTagAtPointer()
        );
        $this->assertInstanceOf(
            ilMDDatabaseTag::class,
            $structure->movePointerToSubElement('lifeCycle')->getTagAtPointer()
        );
        $this->assertInstanceOf(
            ilMDDatabaseTag::class,
            $structure->movePointerToSubElement('version')->getTagAtPointer()
        );
        $this->assertInstanceOf(
            ilMDDatabaseTag::class,
            $structure->movePointerToSubElement('language')->getTagAtPointer()
        );
        $this->assertInstanceOf(
            ilMDDatabaseTag::class,
            $structure
                ->movePointerToSuperElement()
                ->movePointerToSubElement('string')
                ->getTagAtPointer()
        );
    }

    public function testLOMVocabulariesDictionary(): void
    {
        $tag = $this->createMock(ilMDVocabulariesTag::class);
        $tag_builder = $this->createMock(ilMDVocabulariesTagBuilder::class);
        $tag_builder
            ->expects($this->any())
            ->method('getTag')
            ->willReturn($tag);
        $tag_factory = $this->createMock(ilMDTagFactory::class);
        $tag_factory
            ->expects($this->any())
            ->method('vocabularies')
            ->willReturn($tag_builder);
        $path = $this->createMock(ilMDPathRelative::class);
        $path
            ->expects($this->any())
            ->method('addStep')
            ->willReturn($path);
        $path
            ->expects($this->any())
            ->method('addStepToSuperElement')
            ->willReturn($path);
        $path_factory = $this->createMock(ilMDPathFactory::class);
        $path_factory
            ->expects($this->any())
            ->method('getRelativePath')
            ->willReturn($path);

        $dictionary = new ilMDLOMVocabulariesDictionary(
            $tag_factory,
            $path_factory
        );
        $structure = $dictionary
            ->getStructure()
            ->movePointerToSubElement('lifeCycle')
            ->movePointerToSubElement('contribute')
            ->movePointerToSubElement('role')
            ->movePointerToSubElement('source');

        $this->assertInstanceOf(
            ilMDVocabulariesTag::class,
            $structure->getTagAtPointer()
        );
        $this->assertInstanceOf(
            ilMDVocabulariesTag::class,
            $structure
                ->movePointerToSuperElement()
                ->movePointerToSubElement('value')
                ->getTagAtPointer()
        );
        $this->assertNull(
            $structure
                ->movePointerToRoot()
                ->movePointerToSubElement('educational')
                ->getTagAtPointer()
        );
        $this->assertInstanceOf(
            ilMDVocabulariesTag::class,
            $structure
                ->movePointerToSubElement('semanticDensity')
                ->movePointerToSubElement('value')
                ->getTagAtPointer()
        );
    }
}
