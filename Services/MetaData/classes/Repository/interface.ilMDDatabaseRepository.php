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
use ILIAS\Refinery\Factory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDDatabaseRepository implements ilMDRepository
{
    public const MD_TABLES = [
        'annotation' => 'il_meta_annotation',
        'classification' => 'il_meta_classification',
        'contribute' => 'il_meta_contribute',
        'description' => 'il_meta_description',
        'educational' => 'il_meta_educational',
        'entity' => 'il_meta_entity',
        'format' => 'il_meta_format',
        'general' => 'il_meta_general',
        'identifier' => 'il_meta_identifier',
        'identifier_' => 'il_meta_identifier_',
        'keyword' => 'il_meta_keyword',
        'language' => 'il_meta_language',
        'lifecycle' => 'il_meta_lifecycle',
        'location' => 'il_meta_location',
        'meta_data' => 'il_meta_meta_data',
        'relation' => 'il_meta_relation',
        'requirement' => 'il_meta_requirement',
        'rights' => 'il_meta_rights',
        'tar' => 'il_meta_tar',
        'taxon' => 'il_meta_taxon',
        'taxon_path' => 'il_meta_taxon_path',
        'technical' => 'il_meta_technical'
    ];

    public const MD_STRUCTURE = [
        'name' => 'lom', 'unique' => true, 'sub' => [
            ['name' => 'general', 'unique' => true, 'sub' => [
                ['name' => 'identifier', 'unique' => false, 'sub' => [
                    [
                        'name' => 'catalog',
                        'unique' => true,
                        'constraint' => 'trivial',
                        'table' => 'identifier',
                        'field' => 'catalog',
                        'where' => ['parent_type' => 'meta_general']
                    ],
                    [
                        'name' => 'entry',
                        'unique' => true,
                        'constraint' => 'trivial',
                        'table' => 'identifier',
                        'field' => 'entry',
                        'where' => ['parent_type' => 'meta_general']
                    ],
                ]],
                ['name' => 'title', 'unique' => true, 'sub' => []],
                ['name' => 'language', 'unique' => false, 'sub' => []],
                ['name' => 'description', 'unique' => false, 'sub' => []],
                ['name' => 'keyword', 'unique' => false, 'sub' => []],
                ['name' => 'coverage', 'unique' => false, 'sub' => []],
                ['name' => 'structure', 'unique' => true, 'sub' => []],
                ['name' => 'aggregationLevel', 'unique' => true, 'sub' => []],
            ]],
            ['name' => 'lifeCycle', 'unique' => true, 'sub' => []],
            ['name' => 'metaMetadata', 'unique' => true, 'sub' => []],
            ['name' => 'technical', 'unique' => true, 'sub' => []],
            ['name' => 'educational', 'unique' => false, 'sub' => []],
            ['name' => 'rights', 'unique' => true, 'sub' => []],
            ['name' => 'relation', 'unique' => false, 'sub' => []],
            ['name' => 'annotation', 'unique' => true, 'sub' => []],
            ['name' => 'classification', 'unique' => true, 'sub' => []]
        ]
    ];

    protected ilDBInterface $db;
    protected Factory $factory;

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
        $this->factory = $DIC->refinery();

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
        // TODO: Implement getScaffoldForElement() method.
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
        foreach (self::MD_TABLES as $table) {
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

    protected function getTrivialConstraint(): Constraint
    {
        return $this->factory->custom()->constraint(
            function (string $arg) {
                return true;
            },
            ''
        );
    }
}
