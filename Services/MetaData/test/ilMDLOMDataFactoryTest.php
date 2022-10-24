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
                       string|array $value
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

    public function testVocabValueConditionException(): void
    {
        $ref_factory = \Mockery::mock(Factory::class);
        $ref_factory->shouldReceive('custom->constraint')
                    ->never();

        $factory = new ilMDLOMDataFactory($ref_factory);
        $path = $this->createMock(ilMDPathRelative::class);

        $this->expectException(ilMDBuildingBlocksException::class);
        $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_VALUE,
            'value',
            [],
            $path
        );
    }

    public function testStringConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->MDData(ilMDLOMDataFactory::TYPE_STRING, 'value');
        $this->assertNull($data->getError());
        $data = $factory->MDData(ilMDLOMDataFactory::TYPE_STRING, '');
        $this->assertIsString($data->getError());
    }

    public function testNoneConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->MDNullData();
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_NONE,
            'something'
        );
        $this->assertIsString($data->getError());
    }

    public function testLangConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_LANG,
            'de'
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(ilMDLOMDataFactory::TYPE_LANG, 'not lang');
        $this->assertIsString($data->getError());
    }

    public function testVocabConstraint(): void
    {
        $path = $this->createMock(ilMDPathRelative::class);
        $factory = $this->getFactoryMock();
        $vocab1 = $this->getMockBuilder(ilMDVocabulary::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $vocab1->method('getValues')->willReturn(['value1', 'value2']);
        $vocab1->method('getSource')->willReturn('source');
        $vocab2 = $this->getMockBuilder(ilMDVocabulary::class)
                       ->disableOriginalConstructor()
                       ->getMock();
        $vocab2->method('getValues')->willReturn(['sheep', 'cow']);
        $vocab2->method('getSource')->willReturn('different source');
        $vocab2->method('getConditionValue')->willReturn('condition');

        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_SOURCE,
            'source',
            [$vocab1, $vocab2]
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_SOURCE,
            'not source',
            [$vocab1, $vocab2]
        );
        $this->assertIsString($data->getError());

        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_VALUE,
            'value1',
            [$vocab1, $vocab2]
        );
        $this->assertNull($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_VALUE,
            'not value',
            [$vocab1, $vocab2]
        );
        $this->assertIsString($data->getError());
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_VALUE,
            'sheep',
            [$vocab1, $vocab2],
            $path
        );
        $this->assertNull($data->getError('condition'));
        $this->assertIsString($data->getError('something else'));
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_VALUE,
            'value1',
            [$vocab1, $vocab2],
            $path
        );
        $this->assertIsString($data->getError('condition'));
        $this->assertNull($data->getError('something else'));
    }

    public function testGetErrorExceptionForVocabValue(): void
    {
        $path = $this->createMock(ilMDPathRelative::class);
        $factory = $this->getFactoryMock();
        $vocab = $this->getMockBuilder(ilMDVocabulary::class)
                       ->disableOriginalConstructor()
                       ->getMock();
        $vocab->method('getValues')->willReturn(['sheep', 'cow']);
        $vocab->method('getSource')->willReturn('different source');
        $vocab->method('getConditionValue')->willReturn('condition');
        $data = $factory->MDData(
            ilMDLOMDataFactory::TYPE_VOCAB_VALUE,
            'sheep',
            [$vocab],
            $path
        );
        $this->expectException(ilMDBuildingBlocksException::class);
        $this->assertNull($data->getError());
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
            'P12S'
        );
        $this->assertIsString($data->getError());
    }
}
