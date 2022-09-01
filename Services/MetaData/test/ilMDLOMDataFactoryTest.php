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
use ILIAS\Refinery\Constraint;
use ILIAS\Refinery\Factory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMDataFactoryTest extends TestCase
{
    /**
     * @var callable
     */
    protected $call;
    protected string $error;

    protected function getFactoryMock(): ilMDLOMDataFactory
    {
        $constraint = \Mockery::mock(Constraint::class);
        $constraint->shouldReceive('problemWith')
                   ->andReturnUsing(function (
                       string $value
                   ) use ($constraint) {
                       if (!call_user_func($this->call, $value)) {
                           return $this->error;
                       }
                       return null;
                   });

        $ref_factory = \Mockery::mock(Factory::class);
        $ref_factory->shouldReceive('custom->constraint')
                    ->andReturnUsing(function (
                        callable $call,
                        string $error
                    ) use ($constraint) {
                        $this->call = $call;
                        $this->error = $error;
                        return $constraint;
                    });

        return $factory = new ilMDLOMDataFactory($ref_factory);
    }

    public function testWrongTypeException(): void
    {
        $ref_factory = \Mockery::mock(Factory::class);
        $ref_factory->shouldReceive('custom->constraint')
                    ->never();

        $factory = new ilMDLOMDataFactory($ref_factory);
        $this->expectException(ilMDBuildingBlocksException::class);

        $factory->MDData('wrong type', 'value');
    }

    public function testVocabSourceException(): void
    {
        $ref_factory = \Mockery::mock(Factory::class);
        $ref_factory->shouldReceive('custom->constraint')
                    ->never();

        $factory = new ilMDLOMDataFactory($ref_factory);
        $this->expectException(ilMDBuildingBlocksException::class);

        $factory->MDData(ilMDLOMDataFactory::TYPE_VOCAB_SOURCE, 'value');
    }

    public function testVocabValueException(): void
    {
        $ref_factory = \Mockery::mock(Factory::class);
        $ref_factory->shouldReceive('custom->constraint')
                    ->never();

        $factory = new ilMDLOMDataFactory($ref_factory);
        $this->expectException(ilMDBuildingBlocksException::class);

        $factory->MDData(ilMDLOMDataFactory::TYPE_VOCAB_VALUE, 'value');
    }

    public function testStringConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->MDData(ilMDLOMDataFactory::TYPE_STRING, 'value');
        $this->assertNull($data->getError());
    }

    public function testLangConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_LANG,
            ilMDLOMDataFactory::LANGUAGES[0]
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(ilMDLOMDataFactory::TYPE_LANG, 'not lang');
        $this->assertIsString($data->getError());
    }

    public function testVocabConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $vocab = $this->getMockBuilder(ilMDVocabulary::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $vocab->method('getValues')->willReturn(['value1', 'value2']);
        $vocab->method('getSource')->willReturn('source');

        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_SOURCE,
            'source',
            $vocab
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_SOURCE,
            'not source',
            $vocab
        );
        $this->assertIsString($data->getError());

        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_VALUE,
            'value1',
            $vocab
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_VALUE,
            'not value',
            $vocab
        );
        $this->assertIsString($data->getError());
    }

    public function testDatetimeConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_DATETIME,
            '2001'
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_DATETIME,
            '2001-12-01T23:56:01.1234Z'
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_DATETIME,
            'something else'
        );
        $this->assertIsString($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_DATETIME,
            '2001-13-01T23:56:01.1234Z'
        );
        $this->assertIsString($data->getError());
    }

    public function testNonNegativeIntegerConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_NON_NEG_INT,
            '12345'
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_NON_NEG_INT,
            '0000'
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_NON_NEG_INT,
            '-12345'
        );
        $this->assertIsString($data->getError());
    }

    public function testDurationConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_DURATION,
            'P120M'
        );
        $this->assertNull($data->getError());
        $factory = $this->getFactoryMock();
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_DURATION,
            'PT120M'
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_DURATION,
            'P2Y13M78DT345H1M12.0S'
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_DURATION,
            'something else'
        );
        $this->assertIsString($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_DURATION,
            'P0M'
        );
        $this->assertIsString($data->getError());
    }
}
