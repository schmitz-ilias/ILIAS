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

namespace ILIAS\MetaData\Repository\Validation\Data;

use ILIAS\MetaData\Elements\Data\Type;
use ILIAS\MetaData\Vocabularies\VocabulariesInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class DataValidatorService
{
    protected VocabulariesInterface $vocabularies;

    public function __construct(VocabulariesInterface $vocabularies)
    {
        $this->vocabularies = $vocabularies;
    }

    /**
     * @return DataValidatorInterface[]
     */
    protected function validators(): \Generator
    {
        yield Type::DATETIME->value => new DatetimeValidator();
        yield Type::DURATION->value => new DurationValidator();
        yield Type::LANG->value => new LangValidator();
        yield Type::NON_NEG_INT->value => new NonNegIntValidator();
        yield Type::NULL->value => new NullValidator();
        yield Type::STRING->value => new StringValidator();
        yield Type::VOCAB_SOURCE->value => new VocabSourceValidator($this->vocabularies);
        yield Type::VOCAB_VALUE->value => new VocabValueValidator($this->vocabularies);
    }
}
