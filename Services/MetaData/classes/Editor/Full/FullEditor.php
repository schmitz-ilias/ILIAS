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

namespace ILIAS\MetaData\Editor\Full;

use ILIAS\UI\Component\Panel\Panel;
use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Component\Dropdown\Standard as StandardDropdown;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\UI\Factory as UIFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use ILIAS\MetaData\Editor\Dictionary\DictionaryInterface as EditorDictionaryInterface;
use ILIAS\MetaData\Editor\Presenter\Presenter;
use ILIAS\MetaData\Repository\Validation\Dictionary\DictionaryInterface as ConstraintDictionaryInterface;
use ILIAS\MetaData\Elements\SetInterface;
use ILIAS\MetaData\Paths\PathInterface;
use ILIAS\MetaData\Paths\Navigator\NavigatorFactoryInterface;
use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Editor\Full\Services\Services as FullEditorServices;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class FullEditor
{
    public const TABLE = 'table';
    public const PANEL = 'panel';
    public const ROOT = 'root';
    public const FORM = 'form';

    protected UIFactory $factory;
    protected EditorDictionaryInterface $editor_dictionary;
    protected ConstraintDictionaryInterface $constraint_dictionary;
    protected NavigatorFactoryInterface $navigator_factory;
    protected Presenter $presenter;
    protected FullEditorServices $services;

    public function __construct(
        UIFactory $factory,
        EditorDictionaryInterface $editor_dictionary,
        ConstraintDictionaryInterface $constraint_dictionary,
        NavigatorFactoryInterface $navigator_factory,
        Presenter $presenter,
        FullEditorServices $services
    ) {
        $this->factory = $factory;
        $this->presenter = $presenter;
        $this->editor_dictionary = $editor_dictionary;
        $this->constraint_dictionary = $constraint_dictionary;
        $this->navigator_factory = $navigator_factory;
        $this->services = $services;
    }

    public function manipulateMD(): ilMDFullEditorMDManipulator
    {
        return $this->manipulator;
    }

    /**
     * @param SetInterface                   $set
     * @param PathInterface                  $path
     * @param ilMDFullEditorFlexibleSignal[] $create_signals
     * @param ilMDFullEditorFlexibleSignal[] $update_signals
     * @param ilMDFullEditorFlexibleSignal[] $delete_signals
     * @return ilTable2GUI|StandardForm|Panel
     */
    public function getContent(
        SetInterface $set,
        PathInterface $path,
        array $create_signals,
        array $update_signals,
        array $delete_signals
    ): ilTable2GUI|StandardForm|Panel {
        switch ($this->decideContentType($set, $path)) {
            case self::FORM:
                return $this->getForm($set, $path);

            case self::TABLE:
                return $this->getTable(
                    $set,
                    $path,
                    $create_signals,
                    $update_signals,
                    $delete_signals
                );

            case self::PANEL:
                return $this->getPanel(
                    $set,
                    $path,
                    $create_signals,
                    $update_signals,
                    $delete_signals
                );

            case self::ROOT:
                return $this->getRootPanel(
                    $set,
                    $create_signals,
                    $update_signals,
                    $delete_signals
                );

            default:
                throw new ilMDEditorException(
                    'Invalid content type.'
                );
        }
    }

    /**
     * @param ilMDRootElement       $root
     * @param ilMDPathFromRoot      $path
     * @param Request|null          $request
     * @param ilMDPathFromRoot|null $path_for_request
     * @return ilMDFullEditorFlexibleModal[]
     */
    public function getCreateModals(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        ?Request $request = null,
        ?ilMDPathFromRoot $path_for_request = null
    ): array {
        switch ($this->decideContentType($root, $path)) {
            case self::TABLE:
                $form = $this->form_provider->getCreateForm(
                    $root,
                    $path,
                    $path,
                    false
                );
                $req = null;
                if (
                    $path->getPathAsString() ===
                    $path_for_request?->getPathAsString()
                ) {
                    $req = $request;
                }
                return [$path->getPathAsString() =>
                    $this->action_provider->getModal()->create(
                        $path,
                        $root,
                        $form,
                        $req
                    )];

            case self::PANEL:
                return $this->getCreateModalsForPanel(
                    $root,
                    $path,
                    $path,
                    $request,
                    $path_for_request
                );

            case self::ROOT:
                $modals = $this->getCreateModalsForPanel(
                    $root,
                    $path,
                    $path,
                    $request,
                    $path_for_request
                );
                foreach ($root->getSubElements() as $sub) {
                    if ($sub->isScaffold()) {
                        continue;
                    }
                    $appended_path = (clone $path)
                        ->addStep($sub->getName())
                        ->addMDIDFilter($sub->getMDID());
                    $modals = array_merge(
                        $modals,
                        $this->getCreateModalsForPanel(
                            $root,
                            $path,
                            $appended_path,
                            $request,
                            $path_for_request
                        )
                    );
                }
                return $modals;

            case self::FORM:
                return [];

            default:
                throw new ilMDEditorException(
                    'Invalid content type.'
                );
        }
    }

    /**
     * @param ilMDRootElement       $root
     * @param ilMDPathFromRoot      $base_path
     * @param ilMDPathFromRoot      $path
     * @param Request|null          $request
     * @param ilMDPathFromRoot|null $path_for_request
     * @return ilMDFullEditorFlexibleModal[]
     */
    protected function getCreateModalsForPanel(
        ilMDRootElement $root,
        ilMDPathFromRoot $base_path,
        ilMDPathFromRoot $path,
        ?Request $request = null,
        ?ilMDPathFromRoot $path_for_request = null
    ): array {
        $element = $root->getSubElementsByPath($path)[0];
        $modals = [];
        foreach ($element->getSubElements() as $sub) {
            if (!$sub->isScaffold()) {
                continue;
            }
            $appended_path = (clone $path)->addStep(
                $sub->getName()
            );
            $req = null;
            if (
                $appended_path->getPathAsString() ===
                $path_for_request?->getPathAsString()
            ) {
                $req = $request;
            }
            $form = $this->form_provider->getCreateForm(
                $root,
                $appended_path,
                $base_path,
                false
            );

            $modals[$appended_path->getPathAsString()] =
                $this->action_provider->getModal()->create(
                    $appended_path,
                    $root,
                    $form,
                    $req
                );
        }
        return $modals;
    }

    /**
     * @param ilMDRootElement       $root
     * @param ilMDPathFromRoot      $path
     * @param Request|null          $request
     * @param ilMDPathFromRoot|null $path_for_request
     * @return ilMDFullEditorFlexibleModal[]
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
                    $form = $this->form_provider->getUpdateForm(
                        $root,
                        $appended_path,
                        $path,
                        false
                    );
                    $req = null;
                    if (
                        $appended_path->getPathAsString() ===
                        $path_for_request?->getPathAsString()
                    ) {
                        $req = $request;
                    }
                    $modals[$appended_path->getPathAsString()] =
                        $this->action_provider->getModal()->update(
                            $appended_path,
                            $root,
                            $form,
                            $req
                        );
                }
                return $modals;

            case self::ROOT:
            case self::PANEL:
            case self::FORM:
                return [];

            default:
                throw new ilMDEditorException(
                    'Invalid content type.'
                );
        }
    }

    /**
     * @param ilMDRootElement  $root
     * @param ilMDPathFromRoot $path
     * @return ilMDFullEditorFlexibleModal[]
     */
    public function getDeleteModals(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): array {
        switch ($this->decideContentType($root, $path)) {
            case self::PANEL:
                if (!$this->action_provider->isElementDeletable(
                    $root,
                    $this->getNewConstraintStructure(),
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
                    $this->getNewConstraintStructure(),
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
                        $this->getNewConstraintStructure(),
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
                        $this->getNewConstraintStructure(),
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
                throw new ilMDEditorException(
                    'Invalid content type.'
                );
        }
    }

    /**
     * @param ilMDRootElement                   $root
     * @param ilMDPathFromRoot                  $path
     * @param ilMDFullEditorFlexibleSignal[]    $create_signals
     * @param ilMDFullEditorFlexibleSignal[]    $update_signals
     * @param ilMDFullEditorFlexibleSignal[]    $delete_signals
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
                if (!key_exists($path->getPathAsString(), $create_signals)) {
                    return null;
                }
                $elements = $root->getSubElementsByPath($path);
                foreach ($elements as $element) {
                    if ($element instanceof ilMDScaffoldElement) {
                        return $this->action_provider->getButton()->create(
                            $create_signals[$path->getPathAsString()],
                            $element
                        );
                    }
                }
                return null;

            case self::PANEL:
                return null;

            case self::ROOT:
                $buttons = [];
                foreach ($root->getSubElements() as $sub) {
                    if (!$sub instanceof ilMDScaffoldElement) {
                        continue;
                    }
                    $appended_path = (clone $path)->addStep(
                        $sub->getName()
                    );
                    if (!key_exists(
                        $appended_path->getPathAsString(),
                        $create_signals
                    )) {
                        continue;
                    }
                    $buttons[$appended_path->getPathAsString()] =
                        $this->action_provider->getButton()->create(
                            $create_signals[$appended_path->getPathAsString()],
                            $sub,
                            true
                        );
                }
                return $this->factory
                    ->dropdown()
                    ->standard($buttons)
                    ->withLabel($this->presenter->txt('add'));

            default:
                throw new ilMDEditorException(
                    'Invalid content type.'
                );
        }
    }

    public function decideContentType(
        ElementInterface ...$elements
    ): string {
        if ($elements[0]->isRoot()) {
            return self::ROOT;
        }
        $tag = $this->editor_dictionary->tagForElement($elements[0]);
        if (!$tag?->isCollected()) {
            return self::FORM;
        }
        if ($tag?->isLastInTree()) {
            return self::TABLE;
        }
        return self::PANEL;
    }

    /**
     * @param ilMDRootElement                   $root
     * @param ilMDPathFromRoot                  $path
     * @param ilMDFullEditorFlexibleSignal[]    $create_signals
     * @param ilMDFullEditorFlexibleSignal[]    $update_signals
     * @param ilMDFullEditorFlexibleSignal[]    $delete_signals
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
     * @param ilMDRootElement                   $root
     * @param ilMDPathFromRoot                  $path
     * @param ilMDFullEditorFlexibleSignal[]    $create_signals
     * @param ilMDFullEditorFlexibleSignal[]    $update_signals
     * @param ilMDFullEditorFlexibleSignal[]    $delete_signals
     * @param bool                              $subpanel
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
        $element = $root->getSubElementsByPath($path)[0];
        foreach ($element->getSubElements() as $sub) {
            if (!$sub instanceof ilMDScaffoldElement) {
                continue;
            }
            $appended_path = (clone $path)->addStep(
                $sub->getName()
            );
            if (key_exists(
                $appended_path->getPathAsString(),
                $create_signals
            )) {
                $buttons[] = $this->action_provider->getButton()->create(
                    $create_signals[$appended_path->getPathAsString()],
                    $sub,
                    true
                );
            }
        }
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
     * @param ilMDRootElement                   $root
     * @param ilMDFullEditorFlexibleSignal[]    $create_signals
     * @param ilMDFullEditorFlexibleSignal[]    $update_signals
     * @param ilMDFullEditorFlexibleSignal[]    $delete_signals
     * @return Panel
     */
    protected function getRootPanel(
        ilMDRootElement $root,
        array $create_signals,
        array $update_signals,
        array $delete_signals
    ): Panel {
        $content = [];

        $content[] = $this->factory->messageBox()->info(
            $this->presenter->txt('meta_full_editor_navigation_info')
        );

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
            $content[] = $this->getPanel(
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
            $content
        );
    }

    protected function getForm(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): StandardForm {
        return $this->form_provider->getUpdateForm(
            $root,
            $path,
            $path
        );
    }

    /**
     * @return ElementInterface[]
     */
    protected function getElements(SetInterface $set, PathInterface $path): \Generator
    {
        yield from $this->navigator_factory->navigator(
            $path,
            $set->getRoot()
        )->elementsAtFinalStep();
    }
}
