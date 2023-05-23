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

namespace ILIAS\MetaData\Editor\Digest;

use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Input\Field\Section;
use ILIAS\UI\Component\Modal\Interruptive as InterruptiveModal;
use ILIAS\MetaData\Paths\FactoryInterface as PathFactory;
use ILIAS\MetaData\Editor\Presenter\PresenterInterface;
use ILIAS\MetaData\Elements\SetInterface;
use ILIAS\MetaData\Editor\Http\RequestForFormInterface;
use ILIAS\MetaData\Paths\Navigator\NavigatorFactoryInterface;
use ILIAS\MetaData\Repository\Validation\Data\LangValidator;
use ILIAS\MetaData\Editor\Http\LinkFactory;
use ILIAS\MetaData\Editor\Http\Command;
use ILIAS\MetaData\Repository\Validation\Data\DurationValidator;
use ILIAS\UI\Component\Signal;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ContentAssembler
{
    protected PathFactory $path_factory;
    protected NavigatorFactoryInterface $navigator_factory;
    protected UIFactory $ui_factory;
    protected Refinery $refinery;
    protected PresenterInterface $presenter;
    protected PathCollection $path_collection;
    protected LinkFactory $link_factory;
    protected CopyrightHandler $copyright_handler;

    public function __construct(
        PathFactory $path_factory,
        NavigatorFactoryInterface $navigator_factory,
        UIFactory $factory,
        Refinery $refinery,
        PresenterInterface $presenter,
        PathCollection $path_collection,
        LinkFactory $link_factory,
        CopyrightHandler $copyright_handler
    ) {
        $this->path_factory = $path_factory;
        $this->navigator_factory = $navigator_factory;
        $this->ui_factory = $factory;
        $this->refinery = $refinery;
        $this->presenter = $presenter;
        $this->link_factory = $link_factory;
        $this->copyright_handler = $copyright_handler;
    }

    /**
     * @return StandardForm[]|InterruptiveModal[]|string[]
     */
    public function get(
        SetInterface $set,
        ?RequestForFormInterface $request = null
    ): \Generator {
        $sections = [
            'general' => $this->getGeneralSection($set),
            'authors' => $this->getAuthorsSection($set)
        ];
        foreach ($this->getCopyrightContent($set) as $type => $entity) {
            if ($type === ContentType::FORM) {
                $sections['rights'] = $entity;
                continue;
            }
            yield $type => $entity;
        }
        $sections['tlt'] = $this->getTypicalLearningTimeSection($set);
        $form = $this->ui_factory->input()->container()->form()->standard(
            (string) $this->link_factory->custom(Command::UPDATE_DIGEST)->get(),
            $sections
        );

        if (isset($request)) {
            return $request->applyRequestToForm($form);
        }
        yield ContentType::FORM => $form;
    }

    protected function getGeneralSection(
        SetInterface $set
    ): Section {
        $ff = $this->ui_factory->input()->field();
        $root = $set->getRoot();
        $inputs = [];

        $title_el = $this->navigator_factory->navigator(
            $this->path_collection->title(),
            $root
        )->lastElementAtFinalStep();
        $inputs[$this->path_factory->toElement($title_el, true)->toString()] = $ff
            ->text($this->presenter->utilities()->txt('meta_title'))
            ->withRequired(true)
            ->withValue($title_el->getData()->value());

        $decr_els = $this->navigator_factory->navigator(
            $this->path_collection->descriptions(),
            $root
        )->elementsAtFinalStep();
        foreach ($decr_els as $el) {
            $label = $this->presenter->utilities()->txt('meta_description');
            foreach ($el->getSuperElement()->getSubElements() as $sub) {
                if (
                    $sub->getDefinition()->name() !== 'language' ||
                    ($value = $sub->getData()->value()) === ''
                ) {
                    continue;
                }
                $label .= ' (' . $this->presenter->data()->language($value) . ')';
            }
            $inputs[$this->path_factory->toElement($el, true)->toString()] = $ff
                ->textarea($label)
                ->withValue($el->getData()->value());
        }

        $langs = [];
        foreach (LangValidator::LANGUAGES as $key) {
            $langs[$key] = $this->presenter->data()->language($key);
        }
        $lang_input = $ff->select(
            $this->presenter->utilities()->txt('meta_language'),
            $langs
        );
        $lang_els = $this->navigator_factory->navigator(
            $this->path_collection->languages(),
            $root
        )->elementsAtFinalStep();
        foreach ($lang_els as $el) {
            $inputs[$this->path_factory->toElement($el, true)->toString()] = (clone $lang_input)
                ->withValue($el->getData());
        }

        $keywords = [];
        $keyword_els = $this->navigator_factory->navigator(
            $keywords_path = $this->path_collection->keywords(),
            $root
        )->elementsAtFinalStep();
        foreach ($keyword_els as $el) {
            if (!$el->isScaffold()) {
                $strings[] = $el->getData()->value();
            }
        }
        $inputs[$keywords_path->toString()] = $ff->tag(
            $this->presenter->utilities()->txt('keywords'),
            $keywords
        )->withValue($keywords);

        return $ff->section(
            $inputs,
            $this->presenter->utilities()->txt('meta_general')
        );
    }

    protected function getAuthorsSection(
        SetInterface $set
    ): Section {
        $ff = $this->ui_factory->input()->field();
        $inputs = [];

        $author_els = $this->navigator_factory->navigator(
            $this->path_collection->firstThreeAuthors(),
            $set->getRoot()
        )->elementsAtFinalStep();
        $labels = [
            $this->presenter->utilities()->txt('meta_first_author'),
            $this->presenter->utilities()->txt('meta_second_author'),
            $this->presenter->utilities()->txt('meta_third_author')
        ];
        foreach ($author_els as $el) {
            $inputs[$this->path_factory->toElement($el, true)->toString()] = $ff
                ->text(array_shift($labels))
                ->withValue($el->getData()->value());
        }

        return $ff->section(
            $inputs,
            $this->presenter->utilities()->txt('meta_authors')
        );
    }

    /**
     * @return Section[]|InterruptiveModal[]|string[]
     */
    protected function getCopyrightContent(
        SetInterface $set
    ): \Generator {
        if (!$this->copyright_handler->isCPSelectionActive()) {
            return;
        }
        $modal = $this->getChangeCopyrightModal();
        $signal = $modal->getShowSignal();

        yield ContentType::MODAL => $modal;
        yield ContentType::JS_SOURCE => 'Services/MetaData/js/ilMetaCopyrightListener.js';
        yield ContentType::FORM => $this->getCopyrightSection($set, $signal);
    }

    protected function getChangeCopyrightModal(): InterruptiveModal
    {
        $modal = $this->ui_factory->modal()->interruptive(
            $this->presenter->utilities()->txt("meta_copyright_change_warning_title"),
            $this->presenter->utilities()->txt("meta_copyright_change_info"),
            (string) $this->link_factory->custom(Command::UPDATE_DIGEST)->get()
        );

        return $modal;
    }

    protected function getCopyrightSection(
        SetInterface $set,
        Signal $signal
    ): Section {
        $ff = $this->ui_factory->input()->field();

        $cp_description_el = $this->navigator_factory->navigator(
            $this->path_collection->copyright(),
            $set->getRoot()
        )->lastElementAtFinalStep();
        $cp_description = $cp_description_el->getData()->value();
        if ($cp_description) {
            $current_id = $this->copyright_handler->extractCPEntryID($cp_description);
        } else {
            $current_id = $this->copyright_handler->getDefaultCPEntryID();
        }

        $options = [];
        foreach ($this->copyright_handler->getCPEntries() as $entry) {
            //give the option to block harvesting
            $sub_inputs = [];
            if (
                $this->copyright_handler->doesObjectTypeSupportHarvesting($set->getRessourceID()->type()) &&
                $this->copyright_handler->isCopyrightTemplateActive($entry)
            ) {
                $sub_inputs['copyright_oer_blocked_' . $entry->getEntryId()] = $ff
                    ->checkbox(
                        $this->presenter->utilities()->txt('meta_oer_blocked'),
                        $this->presenter->utilities()->txt('meta_oer_blocked_info')
                    )
                    ->withValue(
                        $this->copyright_handler->isOerHarvesterBlocked($set->getRessourceID()->objID())
                    );
            }

            $option = $ff->group($sub_inputs, $entry->getTitle());

            // outdated entries throw an error when selected
            if ($entry->getOutdated()) {
                $option = $option
                    ->withLabel(
                        '(' . $this->presenter->utilities()->txt('meta_copyright_outdated') .
                        ') ' . $entry->getTitle()
                    )
                    ->withAdditionalTransformation(
                        $this->refinery->custom()->constraint(
                            function () {
                                return false;
                            },
                            $this->presenter->utilities()->txt('meta_copyright_outdated_error')
                        )
                    );
            }

            $options[$this->copyright_handler->createIdentifierForEntry($entry)] = $option;
        }

        //custom input as the last option
        $custom_text = $ff
            ->textarea($this->presenter->utilities()->txt('meta_description'))
            ->withValue($current_id === 0 ? $cp_description : '');
        $custom = $ff->group(
            ['copyright_text' => $custom_text],
            $this->presenter->utilities()->txt('meta_cp_own')
        );
        $options['custom'] = $custom;

        $copyright = $ff
            ->switchableGroup(
                $options,
                $this->presenter->utilities()->txt('meta_copyright')
            )
            ->withValue($current_id)
            ->withAdditionalOnLoadCode(
                function ($id) use ($signal) {
                    return 'il.MetaDataCopyrightListener.init("' .
                        $signal . '","' . $id . '");';
                }
            );

        $path_string = $this->path_factory->toElement($cp_description_el, true)->toString();
        return $ff->section(
            [$path_string => $copyright],
            $this->presenter->utilities()->txt('meta_rights')
        );
    }

    protected function getTypicalLearningTimeSection(
        SetInterface $set
    ): Section {
        $ff = $this->ui_factory->input()->field();
        $inputs = [];

        $tlt_el = $this->navigator_factory->navigator(
            $this->path_collection->firstTypicalLearningTime(),
            $set->getRoot()
        )->lastElementAtFinalStep();
        $path = $this->path_factory->toElement($tlt_el, true)->toString();
        preg_match(
            DurationValidator::DURATION_REGEX,
            $tlt_el->getData()->value(),
            $matches,
            PREG_UNMATCHED_AS_NULL
        );
        $num = $ff->numeric('placeholder')
                  ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0));
        $labels = [
            $this->presenter->utilities()->txt('years'),
            $this->presenter->utilities()->txt('months'),
            $this->presenter->utilities()->txt('days'),
            $this->presenter->utilities()->txt('hours'),
            $this->presenter->utilities()->txt('minutes'),
            $this->presenter->utilities()->txt('seconds')
        ];
        $inputs = [];
        foreach ($labels as $key => $label) {
            $inputs[] = (clone $num)
                ->withLabel($label)
                ->withValue($matches[$key + 1] ?? null);
        }
        $group = $ff->group(
            $inputs
        )->withAdditionalTransformation(
            $this->refinery->custom()->transformation(function ($vs) use ($path) {
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

        return $ff->section(
            [$path => $group],
            $this->presenter->utilities()->txt('meta_typical_learning_time')
        );
    }
}
