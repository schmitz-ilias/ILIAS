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

use Psr\Http\Message\ServerRequestInterface as Request;
use classes\Elements\ilMDBaseElement;
use classes\Elements\Markers\ilMDMarkerFactory;
use classes\Elements\ilMDRootElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorMDManipulator
{
    protected ilMDRepository $repo;
    protected ilMDFullEditorFormProvider $form_provider;
    protected ilMDMarkerFactory $marker_factory;
    protected ilMDPathFactory $path_factory;
    protected ilMDLOMVocabulariesDictionary $vocab_dict;

    public function __construct(
        ilMDRepository $repo,
        ilMDFullEditorFormProvider $form_provider,
        ilMDMarkerFactory $marker_factory,
        ilMDPathFactory $path_factory,
        ilMDLOMVocabulariesDictionary $vocab_dict
    ) {
        $this->repo = $repo;
        $this->form_provider = $form_provider;
        $this->marker_factory = $marker_factory;
        $this->path_factory = $path_factory;
        $this->vocab_dict = $vocab_dict;
    }

    public function prepare(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): ilMDRootElement {
        $root = clone $root;
        if (count($path_els = $root->getSubElementsByPath($path)) < 1) {
            throw new ilMDGUIException(
                'The path to the current' .
                ' element does not lead to an element.'
            );
        }
        $elements = [];
        foreach ($path_els as $el) {
            $super = $el->getSuperElement() ?? $el;
            if (!in_array($super, $elements, true)) {
                $elements[] = $super;
            }
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
     * Returns false if the data from the request is invalid.
     */
    public function create(
        ilMDRootElement $root,
        ilMDPathFromRoot $node_path,
        ilMDPathFromRoot $create_path,
        Request $request
    ): bool {
        $form = $this->form_provider->getCreateForm(
            $root,
            $create_path,
            $node_path
        );
        $data = [];
        if (
            !empty($form->getInputs()) &&
            !($data = $form->withRequest($request)->getData())
        ) {
            return false;
        }
        $data = $data[0] ?? [];
        foreach ($data as $path_string => $value) {
            $path = $this->path_factory
                ->getPathFromRoot()
                ->setPathFromString($path_string);
            if ($value !== '' && $value !== null) {
                $el = $this->getUniqueElement($root, $path);
                $el->leaveMarkerTrail(
                    $this->marker_factory->byPath(
                        $value,
                        $path,
                        $this->vocab_dict
                    ),
                    $this->marker_factory->null()
                );
            }
        }
        /**
         * Leave a marker on the initial to-be-created element, to make
         * sure it is created even when the form is left empty.
         */
        $element = $this->getUniqueElement($root, $create_path);
        if (!$element->getMarker()) {
            $element->leaveMarkerTrail(
                $this->marker_factory->null(),
                $this->marker_factory->null()
            );
        }
        $this->repo->createAndUpdateMDElements($root);
        return true;
    }

    /**
     * Returns false if the data from the request is invalid.
     */
    public function update(
        ilMDRootElement $root,
        ilMDPathFromRoot $node_path,
        ilMDPathFromRoot $update_path,
        Request $request
    ): bool {
        $form = $this->form_provider->getUpdateForm(
            $root,
            $update_path,
            $node_path
        );
        if (!($data = $form->withRequest($request)->getData())) {
            return false;
        }
        $data = $data[0];
        $delete_root = clone $root;
        foreach ($data as $path_string => $value) {
            $path = $this->path_factory
                ->getPathFromRoot()
                ->setPathFromString($path_string);
            if ($value !== '') {
                $el = $this->getUniqueElement($root, $path);
                $el->leaveMarkerTrail(
                    $this->marker_factory->byPath(
                        $value,
                        $path,
                        $this->vocab_dict
                    ),
                    $this->marker_factory->null()
                );
                continue;
            }
            $el = $this->getUniqueElement($delete_root, $path);
            $el->leaveMarkerTrail(
                $this->marker_factory->null(),
                $this->marker_factory->null()
            );
        }
        $this->repo->createAndUpdateMDElements($root);
        $this->repo->deleteMDElements($delete_root);
        return true;
    }

    /**
     * Returns a trimmed node path if deleted element was the only one at the end
     * of the path.
     */
    public function deleteAndTrimNodePath(
        ilMDRootElement $root,
        ilMDPathFromRoot $node_path,
        ilMDPathFromRoot $delete_path
    ): ilMDPathFromRoot {
        $el = $this->getUniqueElement($root, $delete_path);
        $el->leaveMarkerTrail(
            $this->marker_factory->null(),
            $this->marker_factory->null()
        );
        $this->repo->deleteMDElements($root);

        $node_els = $root->getSubElementsByPath($node_path);
        if (count($node_els) == 1 && $node_els[0] == $el) {
            $node_path = $node_path->removeLastStep();
        }
        return $node_path;
    }

    /**
     * If the supplied path leads to multiple elements,
     * it takes the first scaffold.
     */
    protected function getUniqueElement(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): ilMDBaseElement {
        $els = $root->getSubElementsByPath($path, null, true);
        if (count($els) < 1) {
            throw new ilMDGUIException(
                'The path does not lead to an element.'
            );
        }
        if (count($els) > 1) {
            throw new ilMDGUIException(
                'The path does not lead to a unique element.'
            );
        }
        return $els[0];
    }
}
