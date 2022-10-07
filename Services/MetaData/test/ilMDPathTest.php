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
class ilMDPathTest extends TestCase
{
    public function testConstructPath(): void
    {
        $path = new ilMDPathFromRoot();
        $path
            ->addStep('step1')
            ->addStep('step2')
            ->addStep('step3');
        $this->assertSame(4, $path->getPathLength());
        $this->assertSame(
            'step3',
            $path->getStep()
        );
        $this->assertSame(
            'step2',
            $path->getStep(2)
        );
        $this->assertSame(
            ilMDPath::ROOT,
            $path->getStep(0)
        );
        $path->removeLastStep();
        $this->assertSame(3, $path->getPathLength());
        $this->assertSame(
            'step2',
            $path->getStep()
        );
    }

    public function testIndexFilter(): void
    {
        $path = new ilMDPathFromRoot();
        $path
            ->addStep('step1')
            ->addStep('step2')
            ->addIndexFilter(2)
            ->addIndexFilter(4)
            ->addStep('step3');
        $this->assertSame(
            [2, 4],
            $path->getIndexFilter(2)
        );
        $path->removeLastStep();
        $this->assertSame(
            [2, 4],
            $path->getIndexFilter()
        );
    }

    public function testMDIDFilter(): void
    {
        $path = new ilMDPathFromRoot();
        $path
            ->addStep('step1')
            ->addStep('step2')
            ->addMDIDFilter(2)
            ->addStep('step3')
            ->addMDIDFilter(14)
            ->addMDIDFilter(57);
        $this->assertSame(
            [2],
            $path->getMDIDFilter(2)
        );
        $this->assertSame(
            [14, 57],
            $path->getMDIDFilter()
        );
    }

    public function testEmptyStepException(): void
    {
        $path = new ilMDPathFromRoot();
        $this->expectException(ilMDPathException::class);
        $path->addStep('');
    }

    public function testReservedCharacterException(): void
    {
        $path = new ilMDPathFromRoot();
        $this->expectException(ilMDPathException::class);
        $path->addStep(ilMDPath::SEPARATOR);
    }

    public function testNoStepToRemoveException(): void
    {
        $path = new ilMDPathFromRoot();
        $this->expectException(ilMDPathException::class);
        $path->removeLastStep();
    }

    public function testRelativePath(): void
    {
        $path = new ilMDPathRelative('start');
        $path->addStepToSuperElement();
        $this->assertSame(
            ilMDPath::SUPER_ELEMENT,
            $path->getStep(1)
        );
        $this->assertSame(
            'start',
            $path->getStep(0)
        );
    }

    public function testStringConversion(): void
    {
        $path = new ilMDPathFromRoot();
        $path
            ->addStep('step1')
            ->addStep('step2')
            ->addIndexFilter(2)
            ->addIndexFilter(4)
            ->addStep('step3');
        $new_path = new ilMDPathFromRoot();
        $new_path->setPathFromString(
            $path->getPathAsString()
        );
        $this->assertEquals($path, $new_path);
    }
}
