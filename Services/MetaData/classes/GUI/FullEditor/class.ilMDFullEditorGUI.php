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
use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Component\Dropdown\Standard as StandardDropdown;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\UI\Factory as UIFactory;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorGUI
{
    public const TABLE = 'table';
    public const PANEL = 'panel';
    public const ROOT = 'root';
    public const FORM = 'form';

    protected ilMDLOMDatabaseRepository $repo;
    protected ilMDPathFactory $path_factory;
    protected UIFactory $factory;
    protected ilMDLOMEditorGUIDictionary $ui_dict;
    protected ilMDLOMEditorGUIQuirkDictionary $quirk_dict;
    protected ilMDLOMPresenter $presenter;
    protected ilMDFullEditorActionProvider $action_provider;
    protected ilMDFullEditorInputProvider $input_provider;
    protected ilMDFullEditorPropertiesProvider $prop_provider;
    protected ilMDFullEditorFormProvider $form_provider;
    protected ilMDFullEditorTableProvider $table_provider;
    protected ilMDFullEditorDataFinder $data_finder;
    protected ilMDFullEditorMDManipulator $manipulator;

    public function __construct(
        ilMDLOMDatabaseRepository $repo,
        ilMDPathFactory $path_factory,
        ilMDLOMLibrary $library,
        UIFactory $factory,
        ilMDLOMPresenter $presenter,
        ilMDFullEditorUtilitiesCollection $collection
    ) {
        $this->repo = $repo;
        $this->path_factory = $path_factory;
        $this->factory = $factory;
        $this->ui_dict = $library->getLOMEditorGUIDictionary(
            $this->path_factory
        );
        $this->quirk_dict = $library->getLOMEditorGUIQuirkDictionary();
        $this->presenter = $presenter;
        $this->data_finder = $collection->dataFinder();
        $this->action_provider = $collection->actionProvider();
        $this->input_provider = $collection->inputProvider();
        $this->prop_provider = $collection->propertiesProvider();
        $this->form_provider = $collection->formProvider();
        $this->table_provider = $collection->tableProvider();
        $this->manipulator = $collection->manipulator();
    }

    public function manipulateMD(): ilMDFullEditorMDManipulator
    {
        return $this->manipulator;
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
     * @param ilMDRootElement       $root
     * @param ilMDPathFromRoot      $path
     * @param Request|null          $request
     * @param ilMDPathFromRoot|null $path_for_request
     * @return Modal[]
     */
    public function getCreateModals(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        ?Request $request = null,
        ?ilMDPathFromRoot $path_for_request = null
    ): array {
        switch ($this->decideContentType($root, $path)) {
            case self::TABLE:
            case self::ROOT:
            case self::PANEL:
            case self::FORM:
                return [];

            default:
                throw new ilMDGUIException(
                    'Invalid content type.'
                );
        }
    }

    /**
     * @param ilMDRootElement       $root
     * @param ilMDPathFromRoot      $path
     * @param Request|null          $request
     * @param ilMDPathFromRoot|null $path_for_request
     * @return Modal[]
     */
    public function getUpdateModals(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        ?Request $request = null,
        ?ilMDPathFromRoot $path_for_request = null
    ): array {
        switch ($this->decideContentType($root, $path)) {
            case self::TABLE:
                $elements = $root->getSubElementsByPath($path);
                $modals = [];
                foreach ($elements as $element) {
                    if ($element->isScaffold()) {
                        continue;
                    }
                    $appended_path = (clone $path)
                        ->addMDIDFilter($element->getMDID());
                    $form = $this->form_provider->getFormForElement(
                        $root,
                        $appended_path,
                        $path
                    );
                    $opened = false;
                    if (
                        $request &&
                        $appended_path->getPathAsString() ===
                        $path_for_request?->getPathAsString()
                    ) {
                        $form = $form->withRequest($request);
                        $opened = true;
                    }
                    $modal = $this->action_provider->getModal()->update(
                        $appended_path,
                        $root,
                        $form
                    );
                    if ($opened) {
                        $modal = $modal->withOnLoad($modal->getShowSignal());
                    }
                    $modals[$appended_path->getPathAsString()] = $modal;
                }
                return $modals;

            case self::ROOT:
            case self::PANEL:
            case self::FORM:
                return [];

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
                if (!$this->action_provider->isElementDeletable(
                    $root,
                    $this->getNewUIQuirkStructure(),
                    $path
                )) {
                    return [];
                }
                return [$path->getPathAsString() => $this->action_provider
                    ->getModal()->delete($path, $path, $root)
                ];

            case self::FORM:
                if (!$this->action_provider->isElementDeletable(
                    $root,
                    $this->getNewUIQuirkStructure(),
                    $path
                )) {
                    return [];
                }
                return [$path->getPathAsString() => $this->action_provider
                            ->getModal()->delete(
                                $path,
                                $path,
                                $root,
                                true
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
                    if (!$this->action_provider->isElementDeletable(
                        $root,
                        $this->getNewUIQuirkStructure(),
                        $appended_path
                    )) {
                        continue;
                    }
                    $modals[$appended_path->getPathAsString()]
                        = $this->action_provider->getModal()->delete(
                            $path,
                            $appended_path,
                            $root,
                            true
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
                    if (!$this->action_provider->isElementDeletable(
                        $root,
                        $this->getNewUIQuirkStructure(),
                        $appended_path
                    )) {
                        continue;
                    }
                    $modals[$appended_path->getPathAsString()] =
                        $this->action_provider->getModal()->delete(
                            $path,
                            $appended_path,
                            $root
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
     * @return Button|StandardDropdown|null
     */
    public function getToolbarContent(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        array $create_signals,
        array $update_signals,
        array $delete_signals
    ): Button|StandardDropdown|null {
        switch ($this->decideContentType($root, $path)) {
            case self::FORM:
                if (!key_exists($path->getPathAsString(), $delete_signals)) {
                    return null;
                }
                return $this->action_provider->getButton()->delete(
                    $delete_signals[$path->getPathAsString()],
                    false,
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

    public function decideContentType(
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
        $table =  $this->table_provider->getTable(
            $root,
            $path
        );
        $delete_buttons = [];
        foreach ($delete_signals as $path => $signal) {
            $delete_buttons[$path] = $this->action_provider
                ->getButton()->delete(
                    $signal,
                    true
                );
        }
        $update_buttons = [];
        foreach ($update_signals as $path => $signal) {
            $update_buttons[$path] = $this->action_provider
                ->getButton()->update($signal);
        }
        $table->parse(
            $update_buttons,
            $delete_buttons
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
        $properties = $this->prop_provider
            ->getPropertiesByPreview($elements);

        //actions
        $buttons = [];
        if (key_exists($path->getPathAsString(), $delete_signals)) {
            $buttons[] = $this->action_provider->getButton()->delete(
                $delete_signals[$path->getPathAsString()],
                true,
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
            $this->presenter->getElementName($root),
            $subpanels
        );
    }

    protected function getForm(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): StandardForm {
        return $this->form_provider->getFormForElement(
            $root,
            $path,
            $path
        );
    }

    protected function getNewUIStructure(): ilMDLOMEditorGUIStructure
    {
        return $this->ui_dict->getStructure();
    }

    protected function getNewUIQuirkStructure(): ilMDLOMEditorGUIQuirkStructure
    {
        return $this->quirk_dict->getStructure();
    }
}
