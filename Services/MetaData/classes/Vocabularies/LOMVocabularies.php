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

namespace ILIAS\MetaData\Vocabularies;

use ILIAS\MetaData\Elements\Base\BaseElementInterface;
use ILIAS\MetaData\Structure\Dictionaries\DictionaryInterface;
use ILIAS\MetaData\Structure\Dictionaries\DictionaryInitiatorInterface;
use ILIAS\MetaData\Vocabularies\Dictionary\Tag;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class LOMVocabularies implements VocabulariesInterface
{
    protected DictionaryInterface $dictionary;

    public function __construct(
        DictionaryInitiatorInterface $dictionary_initiator
    ) {
        $this->dictionary = $dictionary_initiator->get();
    }

    /**
     * @return VocabularyInterface[]
     */
    public function vocabulariesForElement(
        BaseElementInterface $element
    ): \Generator {
        foreach ($this->dictionary->tagsForElement($element) as $tag) {
            /** @var $tag Tag */
            yield $tag->vocabulary();
        }
    }
}
