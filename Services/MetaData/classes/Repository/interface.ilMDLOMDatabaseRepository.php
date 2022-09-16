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
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMDatabaseRepository implements ilMDRepository
{
    protected const MANIP_CREATE = 'create';
    protected const MANIP_UPDATE = 'update';
    protected const MANIP_DELETE = 'delete';

    protected ilDBInterface $db;
    protected ilMDLOMDatabaseStructure $structure;
    protected ilMDLOMVocabulariesStructure $vocab_structure;
    protected ilMDLOMDataFactory $data_factory;
    protected ilLogger $logger;

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
            new ilMDTagFactory(),
            $this->db
        ))->getStructureWithTags();
        $this->vocab_structure = (new ilMDLOMVocabulariesDictionary(
            new ilMDTagFactory(),
        ))->getStructureWithTags();
        $this->data_factory = new ilMDLOMDataFactory(
            $DIC->refinery()
        );
        $this->logger = $DIC->logger()->meta();

        $this->rbac_id = $rbac_id;
        $this->obj_id = $obj_id;
        $this->obj_type = $obj_type;
    }

    public function createAndUpdateMDElements(ilMDRootElement $root): void
    {
        $action = function (
            ilMDBaseElement $element,
            ilMDLOMDatabaseStructure $structure,
            int $super_md_id,
            ?int $parent_id
        ): int {
            $marker = $element->getMarker();
            $tag = $structure->getTagAtPointer();
            if ($error = $marker->getData()->getError()) {
                throw new ilMDDatabaseException($error);
            }

            //update non-scaffold elements
            if ($element instanceof ilMDElement) {
                switch ($marker->getData()->getType()) {
                    case ilMDLOMDataFactory::TYPE_NONE:
                        break;

                    case $element->getData()->getType():
                        if ($error = $marker->getData()->getError()) {
                            throw new ilMDDatabaseException($error);
                        }

                        $this->executeManip(
                            self::MANIP_UPDATE,
                            $tag,
                            $element->getMDID(),
                            $super_md_id,
                            $marker->getData(),
                            $parent_id
                        );
                        break;

                    default:
                        throw new ilMDDatabaseException(
                            'Can not update element ' .
                            $element->getName() .
                            ' with non-matching data type.'
                        );
                }
                return $element->getMDID();
            }

            //create scaffold elements
            switch ($marker->getData()->getType()) {
                case $structure->getTypeAtPointer():
                    $this->executeManip(
                        self::MANIP_CREATE,
                        $tag,
                        $next_id = $this->db->nextId($tag->getTable()),
                        $super_md_id,
                        $marker->getData(),
                        $parent_id
                    );
                    break;

                default:
                    throw new ilMDDatabaseException(
                        'Can not create element ' .
                        $element->getName() .
                        ' with non-matching data type ' .
                        $marker->getData()->getType() . '.'
                    );
            }
            return $next_id;
        };

        $null_action = function (): void {
        };

        $this->iterateThroughMD(
            true,
            $root,
            clone $this->structure->movePointerToRoot(),
            $action,
            $null_action,
            0,
            null,
            0
        );
    }

    /**
     * @return ilMDScaffoldElement[]
     */
    public function getScaffoldForElement(
        ilMDBaseElement $element,
        string $name = ''
    ): array {
        //navigate to element
        $name_path = [];
        while (!($element instanceof ilMDRootElement)) {
            array_unshift($name_path, $element->getName());
            $element = $element->getSuperElement();
        }
        $this->structure->movePointerToRoot();
        foreach ($name_path as $next_name) {
            $this->structure->movePointerToSubElement($next_name);
        }

        //get the sub-elements as scaffolds
        $sub_elements = $this->structure->getSubElementsAtPointer();
        $scaffolds = [];
        foreach ($sub_elements as $sub_element) {
            if ($name !== '' && $name !== $sub_element) {
                continue;
            }
            $this->structure->movePointerToSubElement($sub_element);
            if (
                $this->structure->isUniqueAtPointer() &&
                $element->getSubElement($sub_element) !== null
            ) {
                $this->structure->movePointerToSuperElement();
                continue;
            }
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
        $this->structure->movePointerToRoot();
        $structure = clone $this->structure;
        return new ilMDRootElement(
            $this->rbac_id,
            $this->obj_id,
            $this->obj_type,
            $structure->getNameAtPointer(),
            $this->getSubElements(
                $structure,
                null,
                0,
                1
            ),
            $this->data_factory->MDData(
                $structure->getTypeAtPointer(),
                ''
            )
        );
    }

    /**
     * @return ilMDBaseElement[]
     */
    public function getMDOnPath(ilMDPath $path): array
    {
        $this->validatePath($path);
        $this->structure->movePointerToRoot();
        $structure = clone $this->structure;
        $root = new ilMDRootElement(
            $this->rbac_id,
            $this->obj_id,
            $this->obj_type,
            $structure->getNameAtPointer(),
            $this->getSubElements(
                $structure,
                null,
                0,
                1,
                $path
            ),
            $this->data_factory->MDData(
                $structure->getTypeAtPointer(),
                ''
            )
        );

        /*
         * Navigate to the last element in the path, creating as scaffold
         * what is not there yet.
         */
        $elements = [$root];
        for ($i = 1; $i < $path->getPathLength(); $i++) {
            $next_elements = [];
            foreach ($elements as $element) {
                if (!empty($element->getSubElements())) {
                    $next_elements = array_merge(
                        $next_elements,
                        $element->getSubElements()
                    );
                    continue;
                }

                foreach ($this->getScaffoldForElement($element) as $scaffold) {
                    if ($path->getStep($i) !== $scaffold->getName()) {
                        continue;
                    }
                    $element->addScaffoldToSubElements($scaffold);
                    $next_elements[] = $scaffold;
                }
            }
            $elements = $next_elements;
        }
        return $elements;
    }

    /**
     * @throws ilMDStructureException
     */
    protected function validatePath(ilMDPath $path): void
    {
        $structure = clone $this->structure;
        $structure->movePointerToRoot();
        for ($i = 1; $i < $path->getPathLength(); $i++) {
            try {
                $structure->movePointerToSubElement(
                    $path->getStep($i)
                );
            } catch (ilMDStructureException $ex) {
                throw new ilMDStructureException(
                    'Invalid path: ' . $ex->getMessage()
                );
            }
        }
    }

    /**
     * @return ilMDElement[]
     */
    protected function getSubElements(
        ilMDLOMDatabaseStructure $structure,
        ?int $parent_id,
        int $super_id,
        int $depth,
        ?ilMDPath $path = null
    ): array {
        //stop the recursion after a while, just to be safe.
        if ($depth >= 20) {
            throw new ilMDDatabaseException(
                'Recursion reached its maximum depth'
            );
        }

        $sub_elements = [];
        $new_parent_id = $parent_id;
        foreach ($structure->getSubElementsAtPointer() as $sub_name) {
            // TODO remove after testing
            if (!in_array($sub_name, ['general', 'lifeCycle', 'metaMetadata']) && $depth === 1) {
                continue;
            }
            // TODO up to here
            if (
                isset($path) &&
                $depth < $path->getPathLength() &&
                $path->getStep($depth) !== $sub_name
            ) {
                continue;
            }
            $new_structure = clone $structure;
            $new_structure->movePointerToSubElement($sub_name);
            $tag = $new_structure->getTagAtPointer();
            $res = $this->executeRead($tag, $parent_id, $super_id);

            //check whether unique elements are actually unique
            $unique = $new_structure->isUniqueAtPointer();
            if ($unique && $res->rowCount() > 1) {
                $this->logger->error(
                    'There are multiples of the unique element ' .
                    $sub_name . ' in the table ' . $tag->getTable() .
                    ' for the object with rbac_id=' . $this->rbac_id .
                    ', obj_id=' . $this->obj_id .
                    ', obj_type=' . $this->obj_type .
                    '. Using the first element found.'
                );
            }

            $index = 0;
            while ($row = $this->db->fetchAssoc($res)) {
                if (
                    isset($path) &&
                    $depth < $path->getPathLength() &&
                    !empty($path->getIndexFilter($depth)) &&
                    !in_array($index, $path->getIndexFilter($depth))
                ) {
                    continue;
                }
                $index += 1;

                //get the id of the element
                if (!isset($row[ilMDLOMDatabaseDictionary::RES_MD_ID])) {
                    throw new ilMDDatabaseException(
                        'Query for element ' . $sub_name .
                        ' did not return an ID.'
                    );
                }
                $md_id = (int) $row[ilMDLOMDatabaseDictionary::RES_MD_ID];
                if ($tag->isParent()) {
                    $new_parent_id = $md_id;
                }

                //get the data of the element, if it should have any
                $type = $new_structure->getTypeAtPointer();
                if (
                    !array_key_exists(ilMDLOMDatabaseDictionary::RES_DATA, $row) &&
                    $type !== ilMDLOMDataFactory::TYPE_NONE
                ) {
                    throw new ilMDDatabaseException(
                        'A read query for the elemement ' . $sub_name .
                        ' with data should return data.'
                    );
                }
                if (
                    array_key_exists(ilMDLOMDatabaseDictionary::RES_DATA, $row) &&
                    $type === ilMDLOMDataFactory::TYPE_NONE
                ) {
                    throw new ilMDDatabaseException(
                        'A read query for the elemement ' . $sub_name .
                        ' with no data should not return any data.'
                    );
                }
                $value = (string) ($row[ilMDLOMDatabaseDictionary::RES_DATA] ?? '');
                if (
                    $type !== ilMDLOMDataFactory::TYPE_NONE &&
                    $value === ''
                ) {
                    continue;
                }

                $vocab = null;
                if (
                    $type === ilMDLOMDataFactory::TYPE_VOCAB_VALUE ||
                    $type === ilMDLOMDataFactory::TYPE_VOCAB_SOURCE
                ) {
                    $this->vocab_structure->movePointerToEndOfPath(
                        $new_structure->getPointerAsPath()
                    );
                    $vocab = $this->vocab_structure
                        ->getTagAtPointer()
                        ->getVocabulary();
                    $this->vocab_structure->movePointerToRoot();
                }

                $data = $this->data_factory->MDData($type, $value, $vocab);
                if ($error = $data->getError()) {
                    $this->logger->error(
                        'The element ' . $sub_name .
                        ' with the value ' . $value .
                        ' and the ID ' . $md_id .
                        ' in the table ' . $tag->getTable() .
                        ' for the object with' .
                        ' rbac_id=' . $this->rbac_id .
                        ', obj_id=' . $this->obj_id .
                        ', obj_type=' . $this->obj_type .
                        ' contains invalid data: ' . $error
                    );
                    continue;
                }

                //create the element
                $sub_elements[] = new ilMDElement(
                    $sub_name,
                    $new_structure->isUniqueAtPointer(),
                    $this->getSubElements(
                        $new_structure,
                        $new_parent_id,
                        $md_id,
                        $depth + 1,
                        $path
                    ),
                    $md_id,
                    $data
                );
                if ($unique) {
                    break;
                }
            }
        }
        return $sub_elements;
    }

    public function deleteMDElements(ilMDRootElement $root): void
    {
        $action = function (
            ilMDBaseElement $element,
            ilMDLOMDatabaseStructure $structure,
            int $super_md_id,
            ?int $parent_id
        ): int {
            if (!($element instanceof ilMDElement)) {
                throw new ilMDDatabaseException(
                    'Scaffold elements can not be deleted.'
                );
            }

            foreach ($element->getSubElements() as $sub_element) {
                if ($sub_element->getMarker()) {
                    return $element->getMDID();
                }
            }

            $null_action = function (): void {
            };

            $delete_action = function (
                ilMDBaseElement $element,
                ilMDLOMDatabaseStructure $structure,
                int $super_md_id,
                ?int $parent_id
            ): void {
                if ($element->isScaffold()) {
                    return;
                }

                $this->executeManip(
                    self::MANIP_DELETE,
                    $structure->getTagAtPointer(),
                    $element->getMDID(),
                    $super_md_id,
                    $this->data_factory->MDNullData(),
                    $parent_id
                );
            };

            $this->iterateThroughMD(
                false,
                $element,
                $structure,
                $null_action,
                $delete_action,
                $super_md_id,
                $parent_id,
                0
            );
            return $element->getMDID();
        };

        $null_action = function (): void {
        };

        $this->iterateThroughMD(
            true,
            $root,
            clone $this->structure->movePointerToRoot(),
            $action,
            $null_action,
            0,
            null,
            0
        );
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

    protected function iterateThroughMD(
        bool $only_marked,
        ilMDBaseElement $element,
        ilMDLOMDatabaseStructure $structure,
        callable $top_down_action,
        callable $bottom_up_action,
        int $super_md_id,
        ?int $parent_id,
        int $depth
    ): void {
        //stop the recursion after a while, just to be safe.
        if ($depth >= 20) {
            throw new ilMDDatabaseException(
                'Recursion reached its maximum depth'
            );
        }

        if ($only_marked && !$element->getMarker()) {
            return;
        }

        $new_super_md_id = $top_down_action(
            $element,
            $structure,
            $super_md_id,
            $parent_id
        );

        if (!is_int($new_super_md_id)) {
            $new_super_md_id = $element->getMDID();
        }

        $new_parent_id = $parent_id;
        if ($structure->getTagAtPointer()->isParent()) {
            $new_parent_id = $element->getMDID();
        }

        $results = [];
        foreach ($element->getSubElements() as $sub_element) {
            $new_structure = clone $structure;
            $new_structure->movePointerToSubElement($sub_element->getName());
            $this->iterateThroughMD(
                $only_marked,
                $sub_element,
                $new_structure,
                $top_down_action,
                $bottom_up_action,
                $new_super_md_id,
                $new_parent_id,
                $depth + 1
            );
        }

        $bottom_up_action(
            $element,
            $structure,
            $super_md_id,
            $parent_id
        );
    }

    protected function executeManip(
        string $action,
        ilMDDatabaseTag $tag,
        int $md_id,
        int $super_md_id,
        ilMDData $data,
        ?int $parent_id
    ): void {
        $params = [];
        $param_types = [];
        foreach ($tag->getExpectedParams() as $expected_param) {
            switch ($expected_param) {
                case ilMDLOMDatabaseDictionary::EXP_MD_ID:
                    $params[] = $md_id;
                    $param_types[] = ilDBConstants::T_INTEGER;
                    break;

                case ilMDLOMDatabaseDictionary::EXP_PARENT_MD_ID:
                    if (!is_int($parent_id)) {
                        throw new ilMDDatabaseException(
                            'Parent ID is needed, but not set.'
                        );
                    }
                    $params[] = $parent_id;
                    $param_types[] = ilDBConstants::T_INTEGER;
                    break;

                case ilMDLOMDatabaseDictionary::EXP_DATA:
                    if (
                        $action === self::MANIP_CREATE ||
                        $action === self::MANIP_UPDATE
                    ) {
                        $params[] = $data->getValue();
                        $param_types[] = ilDBConstants::T_TEXT;
                    }
                    break;

                case ilMDLOMDatabaseDictionary::EXP_SUPER_MD_ID:
                    $params[] = $super_md_id;
                    $param_types[] = ilDBConstants::T_INTEGER;
                    break;

                default:
                    throw new ilMDDatabaseException(
                        'Invalid expected parameter'
                    );
            }
        }
        $params = array_merge(
            $params,
            [$this->rbac_id, $this->obj_id, $this->obj_type]
        );
        $param_types = array_merge(
            $param_types,
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );

        switch ($action) {
            case self::MANIP_CREATE:
                if ($tag->getCreate()) {
                    $this->db->ManipulateF(
                        $tag->getCreate(),
                        $param_types,
                        $params
                    );
                }
                break;

            case self::MANIP_UPDATE:
                if ($tag->getUpdate()) {
                    $this->db->ManipulateF(
                        $tag->getUpdate(),
                        $param_types,
                        $params
                    );
                }
                break;

            case self::MANIP_DELETE:
                if ($tag->getDelete()) {
                    $this->db->ManipulateF(
                        $tag->getDelete(),
                        $param_types,
                        $params
                    );
                }
                break;

            default:
                throw new ilMDDatabaseException(
                    'Invalid manipulate action'
                );
        }
    }

    protected function executeRead(
        ilMDDatabaseTag $tag,
        ?int $parent_id,
        int $super_id
    ): ilDBStatement {
        $params = [];
        $param_types = [];
        foreach ($tag->getExpectedParams() as $expected_param) {
            switch ($expected_param) {
                case ilMDLOMDatabaseDictionary::EXP_PARENT_MD_ID:
                    if (!is_int($parent_id)) {
                        throw new ilMDDatabaseException(
                            'Parent ID is needed, but not set.'
                        );
                    }
                    $params[] = $parent_id;
                    $param_types[] = ilDBConstants::T_INTEGER;
                    break;

                case ilMDLOMDatabaseDictionary::EXP_SUPER_MD_ID:
                    $params[] = $super_id;
                    $param_types[] = ilDBConstants::T_INTEGER;
                    break;

                case ilMDLOMDatabaseDictionary::EXP_MD_ID:
                case ilMDLOMDatabaseDictionary::EXP_DATA:
                    break;

                default:
                    throw new ilMDDatabaseException(
                        'Invalid expected parameter'
                    );
            }
        }
        $params = array_merge(
            $params,
            [$this->rbac_id, $this->obj_id, $this->obj_type]
        );
        $param_types = array_merge(
            $param_types,
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );

        return $this->db->queryF(
            $tag->getRead(),
            $param_types,
            $params
        );
    }
}
