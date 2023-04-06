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

use ILIAS\UI\Factory;
use ILIAS\UI\Component\Modal\Interruptive as InterruptiveModal;
use ILIAS\UI\Component\Modal\RoundTrip as RoundtripModal;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\Data\URI;
use ILIAS\UI\Component\Signal as Signal;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorActionModalProvider
{
    public const MAX_MODAL_CHARS = 150;

    protected ilMDFullEditorActionLinkProvider $link_provider;
    protected Factory $factory;
    protected ilMDLOMPresenter $presenter;
    protected ilMDFullEditorPropertiesProvider $prop_provider;

    public function __construct(
        ilMDFullEditorActionLinkProvider $link_provider,
        Factory $factory,
        ilMDLOMPresenter $presenter,
        ilMDFullEditorPropertiesProvider $prop_provider
    ) {
        $this->link_provider = $link_provider;
        $this->factory = $factory;
        $this->presenter = $presenter;
        $this->prop_provider = $prop_provider;
    }

    public function delete(
        ilMDPathFromRoot $base_path,
        ilMDPathFromRoot $delete_path,
        ilMDRootElement $root,
        bool $props_from_data = false
    ): ilMDFullEditorFlexibleModal {
        $action = $this->link_provider->delete(
            $base_path,
            $delete_path
        );
        $elements = $root->getSubElementsByPath($delete_path);

        $items = [];
        $index = 0;
        if ($props_from_data) {
            $content = $this->prop_provider->getPropertiesByData($elements);
        } else {
            $content = $this->prop_provider->getPropertiesByPreview($elements);
        }
        foreach ($content as $title => $descr) {
            if (($len = strlen($title) - self::MAX_MODAL_CHARS) > 0) {
                $title = substr($title, 0, -$len - 1) . "\xe2\x80\xa6";
            }
            if (($len = strlen($descr) - self::MAX_MODAL_CHARS) > 0) {
                $descr = substr($descr, 0, -$len - 1) . "\xe2\x80\xa6";
            }
            $items[] = $this->factory->modal()->interruptiveItem()->keyValue(
                'md_delete_' . $index,
                $title,
                $descr
            );
            $index++;
        }

        $modal = $this->factory->modal()->interruptive(
            $this->getModalTitle(
                ilMDFullEditorActionProvider::DELETE,
                $elements[0]
            ),
            $this->presenter->txt('meta_delete_confirm'),
            (string) $action
        )->withAffectedItems($items);

        return new ilMDFullEditorFlexibleModal($modal);
    }

    public function update(
        ilMDPathFromRoot $update_path,
        ilMDRootElement $root,
        StandardForm $form,
        ?Request $request = null
    ): ilMDFullEditorFlexibleModal {
        $modal =  $this->getRoundtripModal(
            $update_path,
            $root,
            $form,
            ilMDFullEditorActionProvider::UPDATE,
            $request
        );

        return new ilMDFullEditorFlexibleModal($modal);
    }

    public function create(
        ilMDPathFromRoot $create_path,
        ilMDRootElement $root,
        StandardForm $form,
        ?Request $request = null
    ): ilMDFullEditorFlexibleModal {
        // if the modal is empty, directly return the form action
        if (empty($form->getInputs())) {
            return new ilMDFullEditorFlexibleModal($form->getPostURL());
        }

        $modal = $this->getRoundtripModal(
            $create_path,
            $root,
            $form,
            ilMDFullEditorActionProvider::CREATE,
            $request
        );

        return new ilMDFullEditorFlexibleModal($modal);
    }

    protected function getRoundtripModal(
        ilMDPathFromRoot $action_path,
        ilMDRootElement $root,
        StandardForm $form,
        string $action_cmd,
        ?Request $request = null
    ): RoundtripModal {
        $elements = $root->getSubElementsByPath($action_path);
        $button = $this->factory->button()->standard(
            $form->getSubmitCaption() ?? $this->presenter->txt('save'),
            '#'
        )->withOnLoadCode(function ($id) {
            return 'il.MetaModalFormButtonHandler.init("' . $id . '");';
        });

        // For error handling, pass the request to the form
        if ($request) {
            $form = $form->withRequest($request);
        }

        $modal = $this->factory->modal()->roundtrip(
            $this->getModalTitle($action_cmd, $elements[0]),
            $form
        )->withActionButtons([$button]);

        // For error handling, make the modal open on load
        if ($request) {
            $modal = $modal->withOnLoad($modal->getShowSignal());
        }

        return $modal;
    }

    protected function getModalTitle(
        string $action_cmd,
        ilMDBaseElement $element
    ): string {
        switch ($action_cmd) {
            case ilMDFullEditorActionProvider::UPDATE:
                $title_key = 'meta_edit_element';
                break;

            case ilMDFullEditorActionProvider::CREATE:
                $title_key = 'meta_add_element';
                break;

            case ilMDFullEditorActionProvider::DELETE:
                $title_key = 'meta_delete_element';
                break;

            default:
                throw new ilMDGUIException(
                    'Invalid action: ' . $action_cmd
                );
        }
        return $this->presenter->txtFill(
            $title_key,
            [$this->presenter->getElementNameWithParents(
                $element,
                false,
                '',
                false
            )]
        );
    }
}
