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

namespace classes\Elements;

use classes\Elements\Data\ilMDData;
use ilMDRepository;
use ilMDBuildingBlocksException;
use ilMDPathFromRoot;

/**
 * Root element of the MD. Has no superordinate element, and carries the
 * ids of the associated ILIAS object.
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDRootElement extends ilMDElement
{
    /**
     * object id (NOT ref_id!) of rbac object (e.g for page objects the obj_id
     * of the content object; for media objects this is set to 0, because their
     * object id are not assigned to ref ids)
     */
    protected int $rbac_id;

    /**
     * obj_id (e.g for structure objects the obj_id of the structure object)
     */
    protected int $obj_id;

    /**
     * type of the object (e.g st,pg,crs ...)
     */
    protected string $obj_type;

    /**
     * @param int               $rbac_id
     * @param int               $obj_id
     * @param string            $obj_type
     * @param string            $name
     * @param ilMDBaseElement[] $sub_elements
     * @param ilMDData          $data
     */
    public function __construct(
        int $rbac_id,
        int $obj_id,
        string $obj_type,
        string $name,
        array $sub_elements,
        ilMDData $data
    ) {
        parent::__construct($name, true, $sub_elements, 0, $data);
        $this->rbac_id = $rbac_id;
        $this->obj_id = $obj_id;
        $this->obj_type = $obj_type;
    }

    protected function setSuperElement(?ilMDBaseElement $super_element): void
    {
        throw new ilMDBuildingBlocksException(
            "Root elements can not have a superordinate element."
        );
    }

    /**
     * Returns the elements at the end of the path.
     * There might be multiple if there are branches
     * in the path. If a repository is supplied,
     * Elements on the path that don't exist
     * in the MD set are added as scaffolds.
     * @return ilMDBaseElement[]
     */
    public function getSubElementsByPath(
        ilMDPathFromRoot $path,
        ?ilMDRepository $repo = null,
        bool $prefer_first_scaffold = false
    ): array {
        $elements = [$this];
        for ($i = 1; $i < $path->getPathLength(); $i++) {
            $next_elements = [];
            foreach ($elements as $element) {
                $index = 1;
                foreach ($element->getSubElements($path->getStep($i)) as $sub) {
                    $index += 1;
                    if (
                        !empty($path->getIndexFilter($i)) &&
                        !in_array($index, $path->getIndexFilter($i))
                    ) {
                        continue;
                    }
                    if (
                        !empty($path->getMDIDFilter($i)) &&
                        ($sub->isScaffold() ||
                            !in_array($sub->getMDID(), $path->getMDIDFilter($i)))
                    ) {
                        continue;
                    }
                    $next_elements[] = $sub;
                }

                if (
                    empty($element->getSubElements($path->getStep($i))) &&
                    isset($repo)
                ) {
                    foreach ($repo->getScaffoldForElement(
                        $element,
                        $path->getStep($i)
                    ) as $scaffold) {
                        $element->addScaffoldToSubElements($scaffold);
                        $next_elements[] = $scaffold;
                    }
                }
            }

            $filtered_next_els = $next_elements;
            if ($prefer_first_scaffold) {
                foreach ($next_elements as $el) {
                    if ($el->isScaffold()) {
                        $filtered_next_els = [$el];
                        break;
                    }
                }
            }

            $elements = $filtered_next_els;
        }
        return $elements;
    }

    public function getRbacId(): int
    {
        return $this->rbac_id;
    }

    public function getObjId(): int
    {
        return $this->obj_id;
    }

    public function getObjType(): string
    {
        return $this->obj_type;
    }

    public function isRoot(): bool
    {
        return true;
    }
}
