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

namespace ILIAS\MetaData\Editor\Full\Services\Actions;

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Component\Modal\RoundTrip as RoundtripModal;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\UI\Component\Input\Field\Group as Group;
use Psr\Http\Message\ServerRequestInterface as Request;
use ILIAS\MetaData\Editor\Presenter\Presenter;
use ILIAS\MetaData\Paths\PathInterface;
use ILIAS\MetaData\Elements\SetInterface;
use ILIAS\MetaData\Editor\Full\Services\PropertiesFetcher;
use ILIAS\MetaData\Editor\Links\Command;
use ILIAS\MetaData\Elements\ElementInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ModalFactory
{
    public const MAX_LENGTH = 128;

    protected LinkProvider $link_provider;
    protected UIFactory $factory;
    protected Presenter $presenter;
    protected PropertiesFetcher $prop_provider;

    public function __construct(
        LinkProvider $link_provider,
        UIFactory $factory,
        Presenter $presenter,
        PropertiesFetcher $prop_provider
    ) {
        $this->link_provider = $link_provider;
        $this->factory = $factory;
        $this->presenter = $presenter;
        $this->prop_provider = $prop_provider;
    }

    public function delete(
        PathInterface $base_path,
        ElementInterface $to_be_deleted,
        bool $props_from_data = false
    ): FlexibleModal {
        $action = $this->link_provider->delete(
            $base_path,
            $to_be_deleted
        );

        $items = [];
        $index = 0;
        if ($props_from_data) {
            $content = $this->prop_provider->getPropertiesByData($to_be_deleted);
        } else {
            $content = $this->prop_provider->getPropertiesByPreview($to_be_deleted);
        }
        foreach ($content as $key => $value) {
            $items[] = $this->factory->modal()->interruptiveItem()->keyValue(
                'md_delete_' . $index,
                $this->presenter->utilities()->shortenString($key, self::MAX_LENGTH),
                $this->presenter->utilities()->shortenString($value, self::MAX_LENGTH),
            );
            $index++;
        }

        $modal = $this->factory->modal()->interruptive(
            $this->getModalTitle(
                Command::DELETE_FULL,
                $to_be_deleted
            ),
            $this->presenter->utilities()->txt('meta_delete_confirm'),
            (string) $action
        )->withAffectedItems($items);

        return new FlexibleModal($modal);
    }

    public function update(
        ElementInterface $to_be_updated,
        StandardForm $form,
        ?Request $request = null
    ): FlexibleModal {
        $modal =  $this->getRoundtripModal(
            $to_be_updated,
            $form,
            Command::UPDATE_FULL,
            $request
        );

        return new FlexibleModal($modal);
    }

    public function create(
        ElementInterface $to_be_created,
        StandardForm $form,
        ?Request $request = null
    ): FlexibleModal {
        // if the modal is empty, directly return the form action
        if (empty($form->getInputs())) {
            return new FlexibleModal($form->getPostURL());
        }

        $modal = $this->getRoundtripModal(
            $to_be_created,
            $form,
            Command::CREATE_FULL,
            $request
        );

        return new FlexibleModal($modal);
    }

    protected function getRoundtripModal(
        ElementInterface $element,
        StandardForm $form,
        Command $action_cmd,
        ?Request $request = null
    ): RoundtripModal {
        $modal = $this->factory->modal()->roundtrip(
            $this->getModalTitle($action_cmd, $element),
            null,
            $form->getInputs(),
            $form->getPostURL()
        );

        // For error handling, make the modal open on load and pass request
        if ($request) {
            $modal = $modal->withRequest($request);

            /*
             * Show error message in a box, since KS groups don't pass along
             * errors on their own.
             */
            if (
                ($group = $modal->getInputs()[0]) instanceof Group &&
                $error = $group->getError()
            ) {
                $modal = $this->factory->modal()->roundtrip(
                    $modal->getTitle(),
                    [$this->factory->messageBox()->failure($error)],
                    $modal->getInputs(),
                    $modal->getPostURL()
                )->withRequest($request);
            }

            $modal = $modal->withOnLoad($modal->getShowSignal());
        }

        return $modal;
    }

    protected function getModalTitle(
        Command $action_cmd,
        ElementInterface $element
    ): string {
        switch ($action_cmd) {
            case Command::UPDATE_FULL:
                $title_key = 'meta_edit_element';
                break;

            case Command::CREATE_FULL:
                $title_key = 'meta_add_element';
                break;

            case Command::DELETE_FULL:
                $title_key = 'meta_delete_element';
                break;

            default:
                throw new \ilMDEditorException(
                    'Invalid action: ' . $action_cmd->name
                );
        }
        return $this->presenter->utilities()->txtFill(
            $title_key,
            $this->presenter->elements()->nameWithParents(
                $element,
                null,
                false,
                true
            )
        );
    }
}
