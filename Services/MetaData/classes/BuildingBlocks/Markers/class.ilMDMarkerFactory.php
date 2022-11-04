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

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDMarkerFactory
{
    protected ilMDLOMDataFactory $data_factory;

    public function __construct($data_factory)
    {
        $this->data_factory = $data_factory;
    }

    protected function marker(ilMDData $data): ilMDMarker
    {
        return new ilMDMarker($data);
    }

    public function markerByPath(
        string $value,
        ilMDPathFromRoot $path,
        ilMDLOMVocabulariesDictionary $vocab_dict
    ): ilMDMarker {
        return $this->marker($this->data_factory->byPath(
            $value,
            $path,
            $vocab_dict
        ));
    }

    public function nullMarker(): ilMDMarker
    {
        return $this->marker($this->data_factory->none());
    }

    public function stringMarker(string $value): ilMDMarker
    {
        return $this->marker($this->data_factory->string($value));
    }

    public function languageMarker(string $value): ilMDMarker
    {
        return $this->marker($this->data_factory->language($value));
    }

    /**
     * @param string            $value
     * @param ilMDVocabulary[]  $vocabularies
     * @return ilMDData
     */
    public function vocabSourceMarker(
        string $value,
        array $vocabularies
    ): ilMDMarker {
        return $this->marker($this->data_factory->vocabSource(
            $value,
            $vocabularies
        ));
    }

    /**
     * @param string            $value
     * @param ilMDVocabulary[]  $vocabularies
     * @return ilMDData
     */
    public function vocabValueMarker(
        string $value,
        array $vocabularies
    ): ilMDMarker {
        return $this->marker($this->data_factory->vocabValue(
            $value,
            $vocabularies
        ));
    }

    /**
     * @param string            $value
     * @param ilMDVocabulary[]  $vocabularies
     * @param ilMDPathRelative  $path_to_condition
     * @return ilMDData
     */
    public function conditionalVocabValueMarker(
        string $value,
        array $vocabularies,
        ilMDPathRelative $path_to_condition
    ): ilMDMarker {
        return $this->marker($this->data_factory->conditionalVocabValue(
            $value,
            $vocabularies,
            $path_to_condition
        ));
    }

    public function datetimeMarker(string $value): ilMDMarker
    {
        return $this->marker($this->data_factory->datetime($value));
    }

    public function nonNegativeIntMarker(string $value): ilMDMarker
    {
        return $this->marker($this->data_factory->nonNegativeInt($value));
    }

    public function durationMarker(string $value): ilMDMarker
    {
        return $this->marker($this->data_factory->duration($value));
    }
}
