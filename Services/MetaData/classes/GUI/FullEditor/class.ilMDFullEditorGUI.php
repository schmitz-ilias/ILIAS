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

use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Component\Panel\Panel;
use ILIAS\UI\Component\Button\Standard as StandardButton;
use ILIAS\UI\Component\Dropdown\Standard as StandardDropdown;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\Data\Factory as Data;
use ILIAS\Data\URI;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorGUI
{
    protected const TABLE = 'table';
    protected const PANEL = 'panel';
    protected const ROOT = 'root';
    protected const FORM = 'form';

    protected ilMDLOMDatabaseRepository $repo;
    protected ilMDPathFactory $path_factory;
    protected ilMDLOMDataFactory $data_factory;
    protected ilMDMarkerFactory $marker_factory;
    protected UIFactory $factory;
    protected Renderer $renderer;
    protected Refinery $refinery;
    protected ilLanguage $lng;
    protected ilMDLOMEditorGUIStructure $ui_structure;
    protected ilMDLOMVocabulariesStructure $vocab_structure;
    protected ilMDLOMPresenter $presenter;
    protected Data $data;
    protected ilObjUser $user;
    protected ilMDFullEditorActionProvider $action_provider;
    protected ilMDFullEditorInputProvider $input_provider;
    protected URI $base_link;

    public function __construct(
        ilMDLOMDatabaseRepository $repo,
        ilMDPathFactory $path_factory,
        ilMDLOMDataFactory $data_factory,
        ilMDMarkerFactory $marker_factory,
        ilMDLOMLibrary $library,
        UIFactory $factory,
        Renderer $renderer,
        Refinery $refinery,
        ilLanguage $lng,
        ilMDLOMPresenter $presenter,
        Data $data,
        ilObjUser $user,
        URI $base_link
    ) {
        $this->repo = $repo;
        $this->path_factory = $path_factory;
        $this->data_factory = $data_factory;
        $this->marker_factory = $marker_factory;
        $this->factory = $factory;
        $this->renderer = $renderer;
        $this->refinery = $refinery;
        $this->lng = $lng;
        $this->lng->loadLanguageModule('meta');
        $this->ui_structure = $library->getLOMEditorGUIDictionary($path_factory)
                                      ->getStructureWithTags();
        $this->vocab_structure = $library->getLOMVocabulariesDictionary($path_factory)
                                         ->getStructureWithTags();
        $this->presenter = $presenter;
        $this->data = $data;
        $this->user = $user;
        $this->action_provider = new ilMDFullEditorActionProvider();
        $this->input_provider = new ilMDFullEditorInputProvider();
        $this->base_link = $base_link;
    }

    public function prepareMD(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): ilMDRootElement {
        $root = clone $root;
        if (count($elements = $root->getSubElementsByPath($path)) < 1) {
            throw new ilMDGUIException(
                'The path to the current' .
                ' element does not lead to an element.'
            );
        }
        while (!empty($elements)) {
            $next_elements = [];
            foreach ($elements as $element) {
                $scaffolds = $this->repo->getScaffoldForElement($element);
                foreach ($scaffolds as $scaffold) {
                    $element->addScaffoldToSubElements($scaffold);
                }
                $next_elements = array_merge(
                    $next_elements,
                    $element->getSubElements()
                );
            }
            $elements = $next_elements;
        }
        return $root;
    }

    /**
     * @param ilMDRootElement  $root
     * @param ilMDPathFromRoot $path
     * @param Signal[]         $create_signals
     * @param Signal[]         $update_signals
     * @param Signal[]         $delete_signals
     * @return ilTable2GUI|StandardForm|Panel
     */
    public function getContent(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        array $create_signals,
        array $update_signals,
        array $delete_signals
    ): ilTable2GUI|StandardForm|Panel {
        switch ($this->decideContentType($root, $path)) {
            case self::FORM:
                return $this->getForm($root, $path);

            case self::TABLE:
                return $this->getTable(
                    $root,
                    $path,
                    $create_signals,
                    $update_signals,
                    $delete_signals
                );

            case self::PANEL:
                return $this->getPanel(
                    $root,
                    $path,
                    $create_signals,
                    $update_signals,
                    $delete_signals
                );

            case self::ROOT:
                return $this->getRootPanel(
                    $root,
                    $create_signals,
                    $update_signals,
                    $delete_signals
                );

            default:
                throw new ilMDGUIException(
                    'Invalid content type.'
                );
        }
    }

    /**
     * @param ilMDRootElement  $root
     * @param ilMDPathFromRoot $path
     * @return Modal[]
     */
    public function getDeleteModals(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): array {
        switch ($this->decideContentType($root, $path)) {
            case self::PANEL:
                return [$path->getPathAsString() => $this->action_provider
                    ->getDeleteModal(
                        $this->factory,
                        $this->presenter,
                        $this->base_link,
                        $path,
                        $path,
                        $root,
                        $this->getPropertiesByPreview($root, $path)
                    )
                ];

            case self::FORM:
                return [$path->getPathAsString() => $this->action_provider
                            ->getDeleteModal(
                                $this->factory,
                                $this->presenter,
                                $this->base_link,
                                $path,
                                $path,
                                $root,
                                $this->getPropertiesByData($root, $path)
                            )
                ];

            case self::TABLE:
                $elements = $root->getSubElementsByPath($path);
                $modals = [];
                foreach ($elements as $element) {
                    if ($element->isScaffold()) {
                        continue;
                    }
                    $appended_path = (clone $path)
                        ->addMDIDFilter($element->getMDID());
                    $modals[$appended_path->getPathAsString()]
                        = $this->action_provider->getDeleteModal(
                            $this->factory,
                            $this->presenter,
                            $this->base_link,
                            $path,
                            $appended_path,
                            $root,
                            $this->getPropertiesByData($root, $appended_path)
                        );
                }
                return $modals;

            case self::ROOT:
                $modals = [];
                foreach ($root->getSubElements() as $element) {
                    if ($element->isScaffold()) {
                        continue;
                    }
                    $appended_path = (clone $path)
                        ->addStep($element->getName())
                        ->addMDIDFilter($element->getMDID());
                    $modals[$appended_path->getPathAsString()] =
                        $this->action_provider->getDeleteModal(
                            $this->factory,
                            $this->presenter,
                            $this->base_link,
                            $path,
                            $appended_path,
                            $root,
                            $this->getPropertiesByPreview(
                                $root,
                                $appended_path
                            )
                        );
                }
                return $modals;

            default:
                throw new ilMDGUIException(
                    'Invalid content type.'
                );
        }
    }

    /**
     * @param ilMDRootElement  $root
     * @param ilMDPathFromRoot $path
     * @param Signal[]         $create_signals
     * @param Signal[]         $update_signals
     * @param Signal[]         $delete_signals
     * @return StandardButton|StandardDropdown|null
     */
    public function getToolbarContent(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        array $create_signals,
        array $update_signals,
        array $delete_signals
    ): StandardButton|StandardDropdown|null {
        switch ($this->decideContentType($root, $path)) {
            case self::FORM:
                if (!key_exists($path->getPathAsString(), $delete_signals)) {
                    return null;
                }
                return $this->action_provider->getStandardDeleteButton(
                    $delete_signals[$path->getPathAsString()],
                    $this->factory,
                    $this->presenter,
                    true
                );

            case self::TABLE:
                return $this->factory->button()->standard(
                    'add row',
                    '#'
                );

            case self::PANEL:
                return null;

            case self::ROOT:
                return $this->factory->dropdown()->standard(
                    [$this->factory->button()->shy(
                        'something',
                        '#'
                    )]
                )->withLabel('add');

            default:
                throw new ilMDGUIException(
                    'Invalid content type.'
                );
        }
    }

    protected function decideContentType(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
    ): string {
        // root panel for root
        if ($path->isAtStart()) {
            return self::ROOT;
        }

        $struct = $this->getNewUIStructure()
                       ->movePointerToEndOfPath($path);
        // panel if element has subnodes in tree
        foreach ($struct->getSubElementsAtPointer() as $sub_name) {
            $struct->movePointerToSubElement($sub_name);
            if ($struct->getTagAtPointer()?->isInTree()) {
                return self::PANEL;
            }
            $struct->movePointerToSuperElement();
        }

        // table for table collections
        $mode = $struct->getTagAtPointer()->getCollectionMode();
        if ($mode === ilMDLOMEditorGUIDictionary::COLLECTION_TABLE) {
            return self::TABLE;
        }

        // else form
        return self::FORM;
    }

    /**
     * @param ilMDRootElement  $root
     * @param ilMDPathFromRoot $path
     * @param Signal[]         $create_signals
     * @param Signal[]         $update_signals
     * @param Signal[]         $delete_signals
     * @return ilTable2GUI
     */
    protected function getTable(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        array $create_signals,
        array $update_signals,
        array $delete_signals
    ): ilTable2GUI {
        $elements = $root->getSubElementsByPath($path);
        $table =  new ilMDFullEditorTableGUI(
            $this,
            $root,
            $path
        );
        $table->init(
            $this->ui_structure,
            $this->presenter,
            $this->input_provider
        );
        $delete_buttons = [];
        foreach ($delete_signals as $path => $signal) {
            $delete_buttons[$path] = $this->action_provider
                ->getShyDeleteButton(
                    $signal,
                    $this->factory,
                    $this->presenter
                );
        }
        $table->parse(
            $this->ui_structure,
            $this->presenter,
            $this->input_provider,
            $delete_buttons,
            $this->factory,
            $this->renderer
        );
        return $table;
    }

    /**
     * @param ilMDRootElement  $root
     * @param ilMDPathFromRoot $path
     * @param Signal[]         $create_signals
     * @param Signal[]         $update_signals
     * @param Signal[]         $delete_signals
     * @param bool             $subpanel
     * @return Panel
     */
    protected function getPanel(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        array $create_signals,
        array $update_signals,
        array $delete_signals,
        bool $subpanel = false
    ): Panel {
        $elements = $root->getSubElementsByPath($path);
        $struct = $this->getNewUIStructure()->movePointerToEndOfPath($path);
        $properties = $this->getPropertiesByPreview($root, $path);

        //actions
        $buttons = [];
        if (key_exists($path->getPathAsString(), $delete_signals)) {
            $buttons[] = $this->action_provider->getShyDeleteButton(
                $delete_signals[$path->getPathAsString()],
                $this->factory,
                $this->presenter,
                true
            );
        }
        $buttons[] = $this->factory->button()->shy('add something', '#');
        $dropdown = $this->factory->dropdown()->standard($buttons);

        if ($subpanel) {
            $tag = $struct->getTagAtPointer();
            $repr_path = $tag?->getPathToRepresentation();
            return $this->factory->panel()->sub(
                $this->presenter->getElementsLabel($elements, $repr_path),
                !empty($properties) ?
                    $this->factory->listing()->characteristicValue()
                                             ->text($properties) :
                    []
            )->withActions($dropdown);
        }
        return $this->factory->panel()->standard(
            $this->presenter->getElementNameWithParents($elements[0]),
            !empty($properties) ?
                $this->factory->listing()->characteristicValue()
                              ->text($properties) :
                []
        )->withActions($dropdown);
    }

    /**
     * @param ilMDRootElement $root
     * @param Signal[]        $create_signals
     * @param Signal[]        $update_signals
     * @param Signal[]        $delete_signals
     * @return Panel
     */
    protected function getRootPanel(
        ilMDRootElement $root,
        array $create_signals,
        array $update_signals,
        array $delete_signals
    ): Panel {
        $subpanels = [];
        foreach ($root->getSubElements() as $key => $el) {
            if ($el->isScaffold()) {
                continue;
            }
            $path = $this->path_factory
                ->getPathFromRoot()
                ->addStep($el->getName())
                ->addMDIDFilter($el->getMDID());
            $delete = [];
            if (key_exists($path->getPathAsString(), $delete_signals)) {
                $delete[$path->getPathAsString()] =
                    $delete_signals[$path->getPathAsString()];
            }
            $subpanels[] = $this->getPanel(
                $root,
                $path,
                $create_signals,
                $update_signals,
                $delete,
                true
            );
        }
        return $this->factory->panel()->standard(
            $this->presenter->getElementName($root->getName()),
            $subpanels
        );
    }

    protected function getForm(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): StandardForm {
        $section = $this->input_provider->getInputSection(
            $root,
            $path,
            $this->getNewVocabStructure(),
            $this->factory->input()->field(),
            $this->refinery,
            $this->presenter,
            $this->user,
            $this->data
        );
        return $this->factory->input()->container()->form()->standard(
            '#',
            [$section]
        );
    }

    /**
     * @param ilMDRootElement  $root
     * @param ilMDPathFromRoot $path
     * @return string[]
     */
    protected function getPropertiesByPreview(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
    ): array {
        $elements = $root->getSubElementsByPath($path);
        $struct = $this->getNewUIStructure()->movePointerToEndOfPath($path);

        $sub_els = [];
        foreach ($elements as $element) {
            foreach ($element->getSubElements() as $sub_el) {
                if ($sub_el->isScaffold()) {
                    continue;
                }
                $struct->movePointerToSubElement($sub_el->getName());
                if (!($tag = $struct->getTagAtPointer())) {
                    $struct->movePointerToSuperElement();
                    continue;
                };
                $mode = $tag->getCollectionMode();
                $label = $this->presenter->getElementsLabel(
                    [$sub_el],
                    $tag->getPathToRepresentation(),
                    !$struct->isUniqueAtPointer()
                );
                $res = [
                    [$sub_el],
                    $label,
                    $tag->getPathToPreview()
                ];
                $struct->movePointerToSuperElement();
                if (!isset($sub_els[$label])) {
                    $sub_els[$label] = $res;
                    continue;
                }
                $sub_els[$label][0][] = $sub_el;
            }
        }
        $properties = [];
        foreach ($sub_els as $el) {
            $value = $this->presenter->getElementsPreview(
                $el[0],
                $el[2] ?? null
            );
            $properties[$el[1]] = $value;
        }

        return $properties;
    }

    /**
     * @param ilMDRootElement  $root
     * @param ilMDPathFromRoot $path
     * @return string[]
     */
    protected function getPropertiesByData(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
    ): array {
        $elements = $root->getSubElementsByPath($path);
        $properties = [];

        if (empty($properties)) {
            $data_els = $this->input_provider->getDataElements(
                $elements[0],
                $this->getNewUIStructure()
            );
            foreach ($data_els as $data_el) {
                if ($data_el->isScaffold()) {
                    continue;
                }
                $title = $this->presenter->getElementNameWithParents(
                    $data_el,
                    false,
                    $elements[0]->getName()
                );
                $descr = $this->presenter->getDataValue($data_el->getData());
                $properties[$title] = $descr;
            }
        }
        return $properties;
    }

    protected function getNewUIStructure(): ilMDLOMEditorGUIStructure
    {
        return clone $this->ui_structure;
    }

    protected function getNewVocabStructure(): ilMDLOMVocabulariesStructure
    {
        return clone $this->vocab_structure;
    }
}
