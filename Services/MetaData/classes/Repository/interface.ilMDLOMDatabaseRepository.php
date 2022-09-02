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

use ILIAS\Refinery\Constraint;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMDatabaseRepository implements ilMDRepository
{
    protected ilDBInterface $db;
    protected ilMDStructure $structure;

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

    public function __construct(
        int $rbac_id,
        int $obj_id,
        string $obj_type,
    ) {
        global $DIC;

        $this->db = $DIC->database();
        $this->structure = (new ilMDLOMDatabaseDictionary(
            new ilMDMarkerFactory(),
            $this->db
        ))->getStructureWithMarkers();

        $this->rbac_id = $rbac_id;
        $this->obj_id = $obj_id;
        $this->obj_type = $obj_type;
    }

    public function createMDElements(ilMDRootElement $root): void
    {
        // TODO: Implement createMDElements() method.
    }

    /**
     * @return ilMDScaffoldElement[]
     */
    public function getScaffoldForElement(ilMDBaseElement $element): array
    {
        //navigate to element
        $name_path = [];
        while (!($element instanceof ilMDRootElement)) {
            array_unshift($name_path, $element->getName());
            $element = $element->getSuperElement();
        }
        $this->structure->movePointerToRoot();
        foreach ($name_path as $name) {
            $this->structure->movePointerToSubElement($name);
        }

        //get the sub-elements as scaffolds
        $sub_elements = $this->structure->getSubElementsAtPointer();
        $scaffolds = [];
        foreach ($sub_elements as $sub_element) {
            $this->structure->movePointerToSubElement($sub_element);
            $scaffolds[] = new ilMDScaffoldElement(
                $sub_element,
                $this->structure->isUniqueAtPointer(),
                []
            );
            $this->structure->movePointerToSuperElement();
        }
        //move pointer back, just to be safe
        $this->structure->movePointerToRoot();
        return $scaffolds;
    }

    public function getMD(): ilMDRootElement
    {
        // TODO: Implement getMD() method.
    }

    public function updateMDElements(ilMDRootElement $root): void
    {
        // TODO: Implement updateMDElements() method.
    }

    public function deleteMDElements(ilMDRootElement $root): void
    {
        // TODO: Implement deleteMDElements() method.
    }

    public function deleteAllMDElements(): void
    {
        foreach (ilMDLOMDatabaseDictionary::TABLES as $table) {
            $query = "DELETE FROM " . $table . " " .
                "WHERE rbac_id = " . $this->db->quote($this->getRbacId(), ilDBConstants::T_INTEGER) . " " .
                "AND obj_id = " . $this->db->quote($this->getObjId(), ilDBConstants::T_INTEGER);

            $this->db->query($query);
        }
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
