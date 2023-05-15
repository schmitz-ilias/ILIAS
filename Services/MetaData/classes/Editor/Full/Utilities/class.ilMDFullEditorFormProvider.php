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
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\Data\URI;
use classes\Elements\ilMDRootElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorFormProvider
{
    protected const CREATE = 'create';
    protected const UPDATE = 'update';

    protected Factory $factory;
    protected ilMDFullEditorActionProvider $action_provider;
    protected ilMDFullEditorInputProvider $input_provider;
    protected ilMDLOMEditorGUIDictionary $ui_dict;

    public function __construct(
        Factory $factory,
        ilMDFullEditorActionProvider $action_provider,
        ilMDFullEditorInputProvider $input_provider,
        ilMDLOMEditorGUIDictionary $ui_dict
    ) {
        $this->factory = $factory;
        $this->action_provider = $action_provider;
        $this->input_provider = $input_provider;
        $this->ui_dict = $ui_dict;
    }

    public function getUpdateForm(
        ilMDRootElement $root,
        ilMDPathFromRoot $path_to_element,
        ilMDPathFromRoot $base_path,
        bool $with_title = true
    ): StandardForm {
        $link = $this->action_provider
            ->getLink()
            ->update(
                $base_path,
                $path_to_element
            );

        return $this->getFormForElement(
            $root,
            $path_to_element,
            $path_to_element,
            $base_path,
            $link,
            $with_title,
            false
        );
    }

    public function getCreateForm(
        ilMDRootElement $root,
        ilMDPathFromRoot $path_to_element,
        ilMDPathFromRoot $base_path,
        bool $with_title = true
    ): StandardForm {
        $ui_struct = $this->ui_dict->getStructure()
                       ->movePointerToEndOfPath($path_to_element);
        $empty = false;
        $link = $this->action_provider
            ->getLink()
            ->create(
                $base_path,
                $path_to_element
            );

        if ($f_path = $ui_struct->getTagAtPointer()?->getPathToForward()) {
            return $this->getFormForElement(
                $root,
                $this->appendPath(
                    $path_to_element,
                    $f_path
                ),
                $path_to_element,
                $base_path,
                $link,
                $with_title,
                $empty
            );
        }

        foreach ($ui_struct->getSubElementsAtPointer() as $sub_name) {
            $ui_struct->movePointerToSubElement($sub_name);
            if ($ui_struct->getTagAtPointer()?->isInTree()) {
                $empty = true;
                break;
            }
            $ui_struct->movePointerToSuperElement();
        }

        return $this->getFormForElement(
            $root,
            $path_to_element,
            $path_to_element,
            $base_path,
            $link,
            $with_title,
            $empty
        );
    }

    protected function getFormForElement(
        ilMDRootElement $root,
        ilMDPathFromRoot $path_to_element,
        ilMDPathFromRoot $action_path,
        ilMDPathFromRoot $base_path,
        URI $link,
        bool $with_title = true,
        bool $empty = false
    ): StandardForm {
        $section = [];
        if (!$empty) {
            $section = [$this->input_provider->getInputFields(
                $root,
                $path_to_element,
                $action_path,
                $with_title
            )];
        }

        return $this->factory->input()->container()->form()->standard(
            (string) $link,
            $section
        );
    }

    protected function appendPath(
        ilMDPathFromRoot $path,
        ilMDPathRelative $path_relative
    ): ilMDPathFromRoot {
        if ($path->getStep() !== $path_relative->getStep(0)) {
            throw new ilMDEditorException(
                'Can not merge non-matching paths.'
            );
        }
        $res = clone $path;
        for ($i = 1; $i < $path_relative->getPathLength(); $i++) {
            $res->addStep($path_relative->getStep($i));
        }
        return $res;
    }
}
