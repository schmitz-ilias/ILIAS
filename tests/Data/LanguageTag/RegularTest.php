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

namespace ILIAS\Tests\Data\LanguageTag;

use PHPUnit\Framework\TestCase;
use ILIAS\Data\LanguageTag\Regular;

class RegularTest extends TestCase
{
    public function testConstruct(): void
    {
        $regular = new Regular('foo');
        $this->assertInstanceOf(Regular::class, $regular);
    }

    public function testValue(): void
    {
        $regular = new Regular('foo');
        $this->assertEquals('foo', $regular->value());
    }
}
