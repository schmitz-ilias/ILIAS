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
            ->addIndexFilter(2)
            ->addIndexFilter(4)
            ->addStep('step3');
        $this->assertSame(
            ilMDPath::ROOT . ilMDPath::SEPARATOR . 'step1' .
            ilMDPath::SEPARATOR . 'step2' .
            ilMDPath::FILTER_OPEN . '2' . ilMDPath::FILTER_CLOSE .
            ilMDPath::FILTER_OPEN . '4' . ilMDPath::FILTER_CLOSE .
            ilMDPath::SEPARATOR . 'step3',
            $path->getPathAsString()
        );
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
            [2, 4],
            $path->getIndexFilter(2)
        );
        $this->assertSame(
            ilMDPath::ROOT,
            $path->getStep(0)
        );
        $this->assertSame(
            ilMDPath::ROOT . ilMDPath::SEPARATOR . 'step1' .
            ilMDPath::SEPARATOR . 'step2' .
            ilMDPath::FILTER_OPEN . '2' . ilMDPath::FILTER_CLOSE .
            ilMDPath::FILTER_OPEN . '4' . ilMDPath::FILTER_CLOSE,
            $path->removeLastStep()->getPathAsString()
        );
        $this->assertSame(3, $path->getPathLength());
        $this->assertSame(
            'step2',
            $path->getStep()
        );
        $this->assertSame(
            [2, 4],
            $path->getIndexFilter()
        );
        $this->assertSame(
            ilMDPath::ROOT . ilMDPath::SEPARATOR . 'step1',
            $path->removeLastStep()->getPathAsString()
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
}
