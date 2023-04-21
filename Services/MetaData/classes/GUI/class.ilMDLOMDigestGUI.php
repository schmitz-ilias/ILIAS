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

use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\UI\Factory as UIFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Input\Field\Section;
use ILIAS\UI\Component\Modal\Interruptive;
use ILIAS\UI\Component\Signal;
use classes\Elements\Data\ilMDLOMDataFactory;
use classes\Vocabularies\ilMDVocabulary;
use classes\Elements\ilMDBaseElement;
use classes\Elements\Markers\ilMDMarkerFactory;
use classes\Elements\ilMDRootElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMDigestGUI
{
    protected ilMDLOMDatabaseRepository $repo;
    protected ilMDPathFactory $path_factory;
    protected ilMDMarkerFactory $marker_factory;
    protected UIFactory $factory;
    protected Refinery $refinery;
    protected ilLanguage $lng;

    protected ilMDVocabulary $role_vocab;
    protected ilMDVocabulary $cp_vocab;

    public function __construct(
        ilMDLOMDatabaseRepository $repo,
        ilMDPathFactory $path_factory,
        ilMDMarkerFactory $marker_factory,
        ilMDLOMLibrary $library,
        UIFactory $factory,
        Refinery $refinery,
        ilLanguage $lng
    ) {
        $this->repo = $repo;
        $this->path_factory = $path_factory;
        $this->marker_factory = $marker_factory;
        $this->factory = $factory;
        $this->refinery = $refinery;
        $this->lng = $lng;
        $this->lng->loadLanguageModule('meta');

        $this->role_vocab = $library
            ->getLOMVocabulariesDictionary($path_factory)
            ->getStructure()
            ->movePointerToEndOfPath(
                $this->path_factory
                    ->getPathFromRoot()
                    ->addStep('lifeCycle')
                    ->addStep('contribute')
                    ->addStep('role')
                    ->addStep('value')
            )
            ->getTagAtPointer()
            ->getVocabularies()[0];

        $this->cp_vocab = $library
            ->getLOMVocabulariesDictionary($path_factory)
            ->getStructure()
            ->movePointerToEndOfPath(
                $this->path_factory
                    ->getPathFromRoot()
                    ->addStep('rights')
                    ->addStep('copyrightAndOtherRestrictions')
                    ->addStep('value')
            )
            ->getTagAtPointer()
            ->getVocabularies()[0];
    }

    public function getForm(
        ilMDRootElement $root,
        string $post_url,
        ?Request $request = null,
        ?Signal $signal = null,
    ): StandardForm {
        $ff = $this->factory->input()->field();

        // section general
        // title
        $el = $this->getTitleStringElement($root);
        $title = $ff
            ->text(
                $this->lng->txt('meta_title')
            )
            ->withRequired(true)
            ->withValue(
                $el->isScaffold() ? '' : $el->getData()->getValue()
            );

        // description(s)
        $descrs = [];
        foreach ($this->getDescriptionStringElements($root) as $el) {
            $label = $this->lng->txt('meta_description');
            if (!empty(
                $lang = $el->getSuperElement()->getSubElements('language')
            )) {
                $label .= ' ' .
                    $this->lng->txt(
                        'meta_l_' .
                        $lang[0]->getData()->getValue()
                    );
            }
            $descrs[] = $ff
                ->textarea($label)
                ->withValue(
                    $el->isScaffold() ? '' : $el->getData()->getValue()
                );
        }
        $descriptions = $ff->group($descrs);

        // language(s)
        $language = $ff
            ->select(
                $this->lng->txt('meta_language'),
                array_combine(
                    ilMDLOMDataFactory::LANGUAGES,
                    array_map(
                        fn (string $arg) => $this->lng->txt('meta_l_' . $arg),
                        ilMDLOMDataFactory::LANGUAGES
                    )
                )
            );
        $langs = [];
        foreach ($this->getLanguageElements($root) as $el) {
            $langs[] = clone $language
                ->withValue(
                    $el->isScaffold() ? '' : $el->getData()->getValue()
                );
        }
        $languages = $ff->group($langs);

        // keywords
        $strings = [];
        foreach ($this->getKeywordStringElements($root) as $el) {
            if (!$el->isScaffold()) {
                $strings[] = $el->getData()->getValue();
            }
        }
        $keywords = $ff
            ->tag(
                $this->lng->txt('keywords'),
                $strings
            )
            ->withValue($strings);

        $sec_general = $ff
            ->section(
                [
                    'title' => $title,
                    'descriptions' => $descriptions,
                    'languages' => $languages,
                    'keywords' => $keywords
                ],
                $this->lng->txt('meta_general')
            );

        // section authors
        $els = $this->getAuthorEntityElements($root, 3);
        $first = $ff
            ->text(
                $this->lng->txt('meta_first_author')
            )
            ->withValue(
                $els[0]->isScaffold() ? '' : $els[0]->getData()->getValue()
            );
        $second = $ff
            ->text(
                $this->lng->txt('meta_second_author')
            )
            ->withValue(
                $els[1]->isScaffold() ? '' : $els[1]->getData()->getValue()
            );
        $third = $ff
            ->text(
                $this->lng->txt('meta_third_author')
            )
            ->withValue(
                $els[2]->isScaffold() ? '' : $els[2]->getData()->getValue()
            );

        $sec_authors = $ff
            ->section(
                [
                    'first' => $first,
                    'second' => $second,
                    'third' => $third
                ],
                $this->lng->txt('meta_authors')
            );

        // section rights
        if (
            $this->isCPSelectionActive() &&
            !empty($this->getCPEntries())
        ) {
            $sec_rights = $this->getCopyrightSection($root, $signal);
        }

        // section(s) tlt
        $tlt_sections = [];
        foreach ($this->getTltDurationElement($root) as $dur) {
            preg_match(
                ilMDLOMDataFactory::DURATION_REGEX,
                $dur->isScaffold() ? '' : $dur->getData()->getValue(),
                $matches,
                PREG_UNMATCHED_AS_NULL
            );
            $num = $ff
                    ->numeric('placeholder')
                    ->withAdditionalTransformation(
                        $this->refinery->int()->isGreaterThanOrEqual(0)
                    );
            $nums = [];
            $labels = [
                $this->lng->txt('years'),
                $this->lng->txt('months'),
                $this->lng->txt('days'),
                $this->lng->txt('hours'),
                $this->lng->txt('minutes'),
                $this->lng->txt('seconds')
            ];
            foreach ($labels as $key => $label) {
                $nums[] = (clone $num)
                    ->withLabel($label)
                    ->withValue($matches[$key + 1] ?? null);
            }

            $tlt_sections[] = $ff
                ->section(
                    $nums,
                    $this->lng->txt('meta_typical_learning_time') . ' ' .
                    (count($tlt_sections) > 0 ? count($tlt_sections) + 1 : '')
                )
                ->withAdditionalTransformation(
                    $this->refinery->custom()->transformation(function ($vs) {
                        if (
                            count(array_unique($vs)) === 1 &&
                            array_unique($vs)[0] === null
                        ) {
                            return '';
                        }
                        $r = 'P';
                        $signifiers = ['Y', 'M', 'D', 'H', 'M', 'S'];
                        foreach ($vs as $key => $int) {
                            if (isset($int)) {
                                $r .= $int . $signifiers[$key];
                            }
                            if (
                                $key === 2 &&
                                !isset($vs[3]) &&
                                !isset($vs[4]) &&
                                !isset($vs[5])
                            ) {
                                return $r;
                            }
                            if ($key === 2) {
                                $r .= 'T';
                            }
                        }
                        return $r;
                    })
                );
        }
        $tlts = $ff->group($tlt_sections);

        // Assemble the form
        $sections = [
            'general' => $sec_general,
            'authors' => $sec_authors,
        ];
        if (isset($sec_rights)) {
            $sections['rights'] = $sec_rights;
        }
        $sections['tlts'] = $tlts;
        $form = $this->factory->input()->container()->form()->standard(
            $post_url,
            $sections
        );

        if (isset($request)) {
            return $form->withRequest($request);
        }
        return $form;
    }

    protected function getCopyrightSection(
        ilMDRootElement $root,
        ?Signal $signal
    ): Section {
        $ff = $this->factory->input()->field();
        $oer_settings = $this->getOerHarvesterSettings();

        $description =
            ($el = $this->getRightsDescriptionElement($root))->isScaffold() ?
            '' : $el->getData()->getValue();

        if ($description) {
            $current_id = $this->extractCPEntryID($description);
        } else {
            $current_id = $this->getDefaultCPEntryID();
        }

        $options = [];
        foreach ($this->getCPEntries() as $entry) {
            //give the option to block harvesting
            if (
                $oer_settings->supportsHarvesting($root->getObjType()) &&
                $oer_settings->isActiveCopyrightTemplate($entry->getEntryId())
            ) {
                $blocked_cb = $ff
                    ->checkbox(
                        $this->lng->txt('meta_oer_blocked'),
                        $this->lng->txt('meta_oer_blocked_info')
                    )
                    ->withValue(
                        $this->isOerHarvesterBlocked($root->getRbacId())
                    );
            }

            $option = $ff
                ->group(
                    isset($blocked_cb) ?
                        ['copyright_oer_blocked_' . $entry->getEntryId()
                         => $blocked_cb] : [],
                    $entry->getTitle()
                );

            // outdated entries throw an error when selected
            if ($entry->getOutdated()) {
                $option
                    ->withLabel(
                        "(" . $this->lng->txt('meta_copyright_outdated') .
                        ") " . $entry->getTitle()
                    )
                    ->withAdditionalTransformation(
                        $this->refinery->custom()->constraint(
                            function () {
                                return false;
                            },
                            $this->lng->txt('meta_copyright_outdated_error')
                        )
                    );
            }

            $options[$entry->getEntryId()] = $option;
        }

        //custom input as the last option
        $custom_text = $ff
            ->textarea(
                $this->lng->txt('meta_description')
            )
            ->withValue(
                $current_id === 0 ? $description : ''
            );
        $custom = $ff
            ->group(
                ['copyright_text' => $custom_text],
                $this->lng->txt('meta_cp_own')
            );
        $options[0] = $custom;

        $copyright = $ff
            ->switchableGroup(
                $options,
                $this->lng->txt('meta_copyright')
            )
            ->withValue($current_id);
        if (isset($signal)) {
            $copyright = $copyright->
                withAdditionalOnLoadCode(function ($id) use ($signal) {
                    return 'il.MetaDataCopyrightListener.init("' .
                        $signal . '","' . $id . '");';
                });
        }

        return $ff
            ->section(
                ['copyright' => $copyright],
                $this->lng->txt('meta_rights')
            );
    }

    public function prepareChangeCopyrightModal(
        string $post_url
    ): ?Interruptive {
        if (!$this->isCPSelectionActive()) {
            return null;
        }

        $modal = $this->factory->modal()->interruptive(
            $this->lng->txt("meta_copyright_change_warning_title"),
            $this->lng->txt("meta_copyright_change_info"),
            $post_url
        );

        return $modal;
    }

    public function update(ilMDRootElement $root, Request $request): bool
    {
        $form = $this->getForm($root, '', $request);

        if (!$form->getData()) {
            return false;
        }

        $root_for_delete = clone $root;
        $data = $form->getData();

        // section general
        $data_general = $data['general'];
        // title
        $this
            ->getTitleStringElement($root)
            ->leaveMarkerTrail(
                $this->marker_factory->string($data_general['title']),
                $this->marker_factory->null()
            );

        // description(s)
        $index = 0;
        foreach ($this->getDescriptionStringElements($root) as $el) {
            if ($data_general['descriptions'][$index]) {
                $el->leaveMarkerTrail(
                    $this->marker_factory->string(
                        $data_general['descriptions'][$index]
                    ),
                    $this->marker_factory->null()
                );
                $index += 1;
                continue;
            }
            $this
                ->getDescriptionStringElements($root_for_delete)[$index]
                ->leaveMarkerTrail(
                    $this->marker_factory->null(),
                    $this->marker_factory->null()
                );
            $index += 1;
        }

        // language(s)
        $index = 0;
        foreach ($this->getLanguageElements($root) as $el) {
            if ($data_general['languages'][$index]) {
                $el->leaveMarkerTrail(
                    $this->marker_factory->language(
                        $data_general['languages'][$index]
                    ),
                    $this->marker_factory->null()
                );
                $index += 1;
                continue;
            }
            $this
                ->getLanguageElements($root_for_delete)[$index]
                ->leaveMarkerTrail(
                    $this->marker_factory->null(),
                    $this->marker_factory->null()
                );
            $index += 1;
        }

        // keywords
        // keep keywords with matching input, delete the rest
        $input_keywords = $data_general['keywords'];
        foreach ($this->getKeywordStringElements($root_for_delete) as $el) {
            if ($el->isScaffold()) {
                continue;
            }
            if (in_array($el->getData()->getValue(), $input_keywords)) {
                unset($input_keywords[array_search(
                    $el->getData()->getValue(),
                    $input_keywords
                )]);
                continue;
            }
            $el->getSuperElement()->leaveMarkerTrail(
                $this->marker_factory->null(),
                $this->marker_factory->null()
            );
        }
        // create the remaining inputs as scaffolds
        $general_el = $root->getSubElements('general')[0];
        $keyword_scaffold = $this->repo->getScaffoldForElement(
            $general_el,
            'keyword'
        )[0];
        foreach ($input_keywords as $keyword_string) {
            $new_keyword = clone $keyword_scaffold;
            $general_el->addScaffoldToSubElements($new_keyword);
            $string_scaffold = $this->repo->getScaffoldForElement(
                $new_keyword,
                'string'
            )[0];
            $new_keyword->addScaffoldToSubElements($string_scaffold);
            $new_keyword->getSubElements('string')[0]->leaveMarkerTrail(
                $this->marker_factory->string(
                    $keyword_string
                ),
                $this->marker_factory->null()
            );
        }

        // section authors
        $data_authors = $data['authors'];
        $els = $this->getAuthorEntityElements($root, 3);
        $delete_els = $this->getAuthorEntityElements($root_for_delete, 3);
        foreach ([0 => 'first', 1 => 'second', 2 => 'third'] as $key => $value) {
            if ($data_authors[$value] ?? '') {
                $els[$key]->leaveMarkerTrail(
                    $this->marker_factory->string(
                        $data_authors[$value]
                    ),
                    $this->marker_factory->null()
                );
                continue;
            }
            $delete_els[$key]->leaveMarkerTrail(
                $this->marker_factory->null(),
                $this->marker_factory->null()
            );
        }

        // section rights
        if (
            ($data_rights = $data['rights'] ?? null) &&
            (($data_cp = $data_rights['copyright'])[0] > 0 ||
            $data_cp[1]['copyright_text'])
        ) {
            $data_cp = $data_rights['copyright'];
            $this->getCPAndORValueElement($root)->leaveMarkerTrail(
                $this->marker_factory->vocabValue(
                    'yes',
                    [$this->cp_vocab]
                ),
                $this->marker_factory->null()
            );

            if ($data_cp[0] > 0) {
                $this->getRightsDescriptionElement($root)->leaveMarkerTrail(
                    $this->marker_factory->string(
                        'il_copyright_entry__' . IL_INST_ID . '__' .
                        (int) $data_cp[0]
                    ),
                    $this->marker_factory->null()
                );
            } else {
                $this->getRightsDescriptionElement($root)->leaveMarkerTrail(
                    $this->marker_factory->string(
                        $data_cp[1]['copyright_text']
                    ),
                    $this->marker_factory->null()
                );
            }

            // update oer status
            if (
                $this->getOerHarvesterSettings()
                     ->supportsHarvesting($root->getObjType())
            ) {
                $is_blocked =
                    $data_cp[1]['copyright_oer_blocked_' . $data_cp[0]] ??
                    false;
                $this->setOerHarvesterBlocked(
                    $root->getRbacId(),
                    (bool) $is_blocked
                );
            }
        } else {
            if (!($el = $this->getCPAndORValueElement($root))->isScaffold()) {
                $el->leaveMarkerTrail(
                    $this->marker_factory->vocabValue(
                        'no',
                        [$this->cp_vocab]
                    ),
                    $this->marker_factory->null()
                );
            }
            $this->getRightsDescriptionElement($root_for_delete)
                 ->leaveMarkerTrail(
                     $this->marker_factory->null(),
                     $this->marker_factory->null()
                 );
        }

        // section(s) tlt
        $index = 0;
        foreach ($this->getTltDurationElement($root) as $el) {
            if ($data['tlts'][$index]) {
                $el->leaveMarkerTrail(
                    $this->marker_factory->duration(
                        $data['tlts'][$index]
                    ),
                    $this->marker_factory->null()
                );
                $index += 1;
                continue;
            }
            $this
                ->getTltDurationElement($root_for_delete)[$index]
                ->leaveMarkerTrail(
                    $this->marker_factory->null(),
                    $this->marker_factory->null()
                );
            $index += 1;
        }

        // update and delete
        $this->repo->createAndUpdateMDElements($root);
        $this->repo->deleteMDElements($root_for_delete);
        return true;
    }

    protected function getTitleStringElement(
        ilMDRootElement $root
    ): ilMDBaseElement {
        $path = $this->path_factory
            ->getPathFromRoot()
            ->addStep('general')
            ->addStep('title')
            ->addStep('string');

        return $root->getSubElementsByPath(
            $path,
            $this->repo
        )[0];
    }

    /**
     * @param ilMDRootElement $root
     * @return ilMDBaseElement[]
     */
    protected function getDescriptionStringElements(
        ilMDRootElement $root
    ): array {
        $path = $this->path_factory
            ->getPathFromRoot()
            ->addStep('general')
            ->addStep('description')
            ->addStep('string');

        return $root->getSubElementsByPath(
            $path,
            $this->repo
        );
    }

    /**
     * @param ilMDRootElement $root
     * @return ilMDBaseElement[]
     */
    protected function getLanguageElements(
        ilMDRootElement $root
    ): array {
        $path = $this->path_factory
            ->getPathFromRoot()
            ->addStep('general')
            ->addStep('language');

        return $root->getSubElementsByPath(
            $path,
            $this->repo
        );
    }

    /**
     * @param ilMDRootElement $root
     * @return ilMDBaseElement[]
     */
    protected function getKeywordStringElements(
        ilMDRootElement $root
    ): array {
        $path = $this->path_factory
            ->getPathFromRoot()
            ->addStep('general')
            ->addStep('keyword')
            ->addStep('string');

        return $root->getSubElementsByPath(
            $path,
            $this->repo
        );
    }

    /**
     * @param ilMDRootElement $root
     * @param int             $min
     * @return ilMDBaseElement[]
     */
    protected function getAuthorEntityElements(
        ilMDRootElement $root,
        int $min
    ): array {
        $path = $this->path_factory
            ->getPathFromRoot()
            ->addStep('lifeCycle')
            ->addStep('contribute')
            ->addStep('entity');

        $res = [];
        foreach ($root->getSubElementsByPath(
            $path,
            $this->repo
        ) as $element) {
            if (empty($roles = $element
                ->getSuperElement()
                ->getSubElements('role')
            )) {
                continue;
            }
            if (
                empty($values = $roles[0]->getSubElements('value')) ||
                $values[0]->isScaffold()
            ) {
                continue;
            }
            if (strtolower($values[0]->getData()->getValue()) === 'author') {
                $res[] = $element;
            }
        }

        //if not enough results are returned, add more as scaffolds
        if (count($res) < $min) {
            $path = $this->path_factory
                ->getPathFromRoot()
                ->addStep('lifeCycle')
                ->addStep('contribute');

            foreach ($root->getSubElementsByPath(
                $path,
                $this->repo
            ) as $element) {
                if (empty($roles = $element->getSubElements('role'))) {
                    continue;
                }
                if (empty($values = $roles[0]->getSubElements('value'))) {
                    continue;
                }

                if (
                    $values[0]->isScaffold() &&
                    $marker = $values[0]->getMarker()
                ) {
                    $value = $marker->getData()->getValue();
                }
                if (!($values[0]->isScaffold())) {
                    $value = $values[0]->getData()->getValue();
                }
                if (!isset($value) ||
                    !(strtolower($value) === 'author')) {
                    continue;
                }
                if (
                    !empty($element->getSubElements('entity')) ||
                    !isset($author_el)
                ) {
                    $author_el = $element;
                }
            }

            // if no contribute with role author exists, add a new one as scaffold
            if (!isset($author_el)) {
                $lifecycle = $root->getSubElements('lifeCycle')[0];
                $author_el = $this->repo->getScaffoldForElement(
                    $lifecycle,
                    'contribute'
                )[0];
                $lifecycle->addScaffoldToSubElements($author_el);
                $role = $this->repo->getScaffoldForElement(
                    $author_el,
                    'role'
                )[0];
                $author_el->addScaffoldToSubElements($role);
                $value = $this->repo->getScaffoldForElement(
                    $role,
                    'value'
                )[0];
                $role->addScaffoldToSubElements($value);
                $role->setMarker($this->marker_factory->null());
                $value->setMarker(
                    $this->marker_factory->vocabValue(
                        'author',
                        [$this->role_vocab]
                    )
                );
            }
            $missing = $min - count($res);
            for ($i = 0; $i < $missing; $i++) {
                $s = $this->repo->getScaffoldForElement($author_el, 'entity')[0];
                $res[] = $s;
                $author_el->addScaffoldToSubElements($s);
            }
        }

        return $res;
    }

    protected function getTltDurationElement(
        ilMDRootElement $root
    ): array {
        $path = $this->path_factory
            ->getPathFromRoot()
            ->addStep('educational')
            ->addStep('typicalLearningTime')
            ->addStep('duration');

        return $root->getSubElementsByPath(
            $path,
            $this->repo
        );
    }

    protected function getRightsDescriptionElement(
        ilMDRootElement $root
    ): ilMDBaseElement {
        $path = $this->path_factory
            ->getPathFromRoot()
            ->addStep('rights')
            ->addStep('description')
            ->addStep('string');

        return $root->getSubElementsByPath(
            $path,
            $this->repo
        )[0];
    }

    protected function getCPAndORValueElement(
        ilMDRootElement $root
    ): ilMDBaseElement {
        $path = $this->path_factory
            ->getPathFromRoot()
            ->addStep('rights')
            ->addStep('copyrightAndOtherRestrictions')
            ->addStep('value');

        return $root->getSubElementsByPath(
            $path,
            $this->repo
        )[0];
    }

    // dependencies for CP
    protected function isCPSelectionActive(): bool
    {
        $settings = ilMDSettings::_getInstance();
        return $settings->isCopyrightSelectionActive();
    }

    protected function getOerHarvesterSettings(): ilOerHarvesterSettings
    {
        return ilOerHarvesterSettings::getInstance();
    }

    /**
     * @return ilMDCopyrightSelectionEntry[]
     */
    protected function getCPEntries(): array
    {
        return ilMDCopyrightSelectionEntry::_getEntries();
    }

    protected function extractCPEntryID(string $description): int
    {
        return ilMDCopyrightSelectionEntry::_extractEntryId($description);
    }

    protected function getDefaultCPEntryID(): int
    {
        return ilMDCopyrightSelectionEntry::getDefault();
    }

    protected function isOerHarvesterBlocked(int $rbac_id): bool
    {
        $status = new ilOerHarvesterObjectStatus($rbac_id);
        return $status->isBlocked();
    }

    protected function setOerHarvesterBlocked(
        int $rbac_id,
        bool $is_blocked
    ): void {
        $status = new ilOerHarvesterObjectStatus($rbac_id);
        $status->setBlocked($is_blocked);
        $status->save();
    }
}
