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
     */
    public function __construct(
        int $rbac_id,
        int $obj_id,
        string $obj_type,
        string $name,
        array $sub_elements,
    ) {
        parent::__construct($name, true, $sub_elements);
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
}
