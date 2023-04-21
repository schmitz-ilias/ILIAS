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
use classes\Elements\Data\ilMDLOMDataFactory;
use Validation\ilMDLOMDataConstraintProvider;
use classes\Vocabularies\ilMDVocabulary;

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

        return $factory = new ilMDLOMDataFactory(
            new ilMDLOMDataConstraintProvider($ref_factory)
        );
    }

    public function testVocabSourceException(): void
    {
        $ref_factory = \Mockery::mock(Factory::class);
        $ref_factory->shouldReceive('custom->constraint')
                    ->never();

        $factory = new ilMDLOMDataFactory(
            new ilMDLOMDataConstraintProvider($ref_factory)
        );
        $this->expectException(ilMDBuildingBlocksException::class);

        $factory->vocabSource('value', []);
    }

    public function testVocabValueException(): void
    {
        $ref_factory = \Mockery::mock(Factory::class);
        $ref_factory->shouldReceive('custom->constraint')
                    ->never();

        $factory = new ilMDLOMDataFactory(
            new ilMDLOMDataConstraintProvider($ref_factory)
        );
        $this->expectException(ilMDBuildingBlocksException::class);

        $factory->vocabValue('value', []);
    }

    public function testVocabValueConditionException(): void
    {
        $ref_factory = \Mockery::mock(Factory::class);
        $ref_factory->shouldReceive('custom->constraint')
                    ->never();

        $factory = new ilMDLOMDataFactory(
            new ilMDLOMDataConstraintProvider($ref_factory)
        );
        $path = $this->createMock(ilMDPathRelative::class);

        $this->expectException(ilMDBuildingBlocksException::class);
        $factory->conditionalVocabValue('value', [], $path);
    }

    public function testStringConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->string('value');
        $this->assertNull($data->getError());
        $data = $factory->string('');
        $this->assertIsString($data->getError());
    }

    public function testNullConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->null();
        $this->assertNull($data->getError());
    }

    public function testLangConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->language('de');
        $this->assertNull($data->getError());
        $data = $factory->language('not lang');
        $this->assertIsString($data->getError());
    }

    public function testVocabConstraint(): void
    {
        $path = $this->createMock(ilMDPathRelative::class);
        $factory = $this->getFactoryMock();
        $vocab1 = $this->getMockBuilder(ilMDVocabulary::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $vocab1->method('values')->willReturn(['value1', 'value2']);
        $vocab1->method('source')->willReturn('source');
        $vocab2 = $this->getMockBuilder(ilMDVocabulary::class)
                       ->disableOriginalConstructor()
                       ->getMock();
        $vocab2->method('values')->willReturn(['sheep', 'cow']);
        $vocab2->method('source')->willReturn('different source');
        $vocab2->method('conditionValue')->willReturn('condition');

        $data = $factory->vocabSource(
            'source',
            [$vocab1, $vocab2]
        );
        $this->assertNull($data->getError());
        $data = $factory->vocabSource(
            'not source',
            [$vocab1, $vocab2]
        );
        $this->assertIsString($data->getError());

        $data = $factory->vocabValue('value1', [$vocab1, $vocab2]);
        $this->assertNull($data->getError());
        $data = $factory->vocabValue('not value', [$vocab1, $vocab2]);
        $this->assertIsString($data->getError());
        $data = $factory->conditionalVocabValue(
            'sheep',
            [$vocab1, $vocab2],
            $path
        );
        $this->assertNull($data->getError('condition'));
        $this->assertIsString($data->getError('something else'));
        $data = $factory->conditionalVocabValue(
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
        $vocab->method('values')->willReturn(['sheep', 'cow']);
        $vocab->method('source')->willReturn('different source');
        $vocab->method('conditionValue')->willReturn('condition');
        $data = $factory->conditionalVocabValue(
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
        $data = $factory->datetime('2001');
        $this->assertNull($data->getError());
        $data = $factory->datetime('2001-12-01T23:56:01.1234Z');
        $this->assertNull($data->getError());
        $data = $factory->datetime('something else');
        $this->assertIsString($data->getError());
        $data = $factory->datetime('2001-13-01T23:56:01.1234Z');
        $this->assertIsString($data->getError());
    }

    public function testNonNegativeIntegerConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->nonNegativeInt('12345');
        $this->assertNull($data->getError());
        $data = $factory->nonNegativeInt('0000');
        $this->assertNull($data->getError());
        $data = $factory->nonNegativeInt('-12345');
        $this->assertIsString($data->getError());
    }

    public function testDurationConstraint(): void
    {
        $factory = $this->getFactoryMock();
        $data = $factory->duration('P120M');
        $this->assertNull($data->getError());
        $factory = $this->getFactoryMock();
        $data = $factory->duration('PT120M');
        $this->assertNull($data->getError());
        $data = $factory->duration('P2Y13M78DT345H1M12.0S');
        $this->assertNull($data->getError());
        $data = $factory->duration('something else');
        $this->assertIsString($data->getError());
        $data = $factory->duration('P12S');
        $this->assertIsString($data->getError());
    }
}
