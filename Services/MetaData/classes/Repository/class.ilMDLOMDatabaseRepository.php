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

use classes\Elements\Data\ilMDLOMDataFactory;
use classes\Elements\Data\ilMDData;
use Validation\ilMDLOMDataConstraintProvider;
use classes\Elements\ilMDBaseElement;
use classes\Elements\ilMDElement;
use classes\Elements\ilMDRootElement;
use classes\Elements\ilMDScaffoldElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMDatabaseRepository implements ilMDRepository
{
    protected const MANIP_CREATE = 'create';
    protected const MANIP_UPDATE = 'update';
    protected const MANIP_DELETE = 'delete';

    protected ilDBInterface $db;
    protected ilMDLOMDatabaseDictionary $db_dictionary;
    protected ilMDLOMVocabulariesDictionary $vocab_dictionary;
    protected ilMDLOMDataFactory $data_factory;
    protected ilMDPathFactory $path_factory;
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
        $this->data_factory = new ilMDLOMDataFactory(
            new ilMDLOMDataConstraintProvider($DIC->refinery())
        );
        $this->path_factory = new ilMDPathFactory();
        $library = new ilMDLOMLibrary(new ilMDTagFactory());
        $this->db_dictionary = $library->getLOMDatabaseDictionary($this->db);
        $this->vocab_dictionary = $library->getLOMVocabulariesDictionary(
            $this->path_factory
        );
        $this->logger = $DIC->logger()->meta();

        $this->rbac_id = $rbac_id;
        $this->obj_id = $obj_id;
        $this->obj_type = $obj_type;
    }

    public function createAndUpdateMDElements(ilMDRootElement $root): void
    {
        /**
         * @param ilMDBaseElement          $element
         * @param ilMDLOMDatabaseStructure $structure
         * @param int                      $super_md_id
         * @param int[]                    $parent_ids
         * @return int
         */
        $action = function (
            ilMDBaseElement $element,
            ilMDLOMDatabaseStructure $structure,
            int $super_md_id,
            array $parent_ids
        ): int {
            $marker = $element->getMarker();
            $tag = $structure->getTagAtPointer();

            //update non-scaffold elements
            if ($element instanceof ilMDElement) {
                switch ($marker->getData()->getType()) {
                    case ilMDLOMDataFactory::TYPE_NULL:
                        break;

                    case $element->getData()->getType():
                        $this->executeManip(
                            self::MANIP_UPDATE,
                            $tag,
                            $element->getMDID(),
                            $super_md_id,
                            $marker->getData(),
                            $parent_ids
                        );
                        break;

                    default:
                        throw new ilMDRepositoryException(
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
                    $next_id = $super_md_id;
                    if (
                        in_array(
                            ilMDLOMDatabaseDictionary::EXP_MD_ID,
                            $tag->getExpectedParams()
                        ) &&
                        $tag->getCreate()
                    ) {
                        $next_id = $this->db->nextId($tag->getTable());
                    }
                    $this->executeManip(
                        self::MANIP_CREATE,
                        $tag,
                        $next_id,
                        $super_md_id,
                        $marker->getData(),
                        $parent_ids
                    );
                    break;

                default:
                    throw new ilMDRepositoryException(
                        'Can not create element ' .
                        $element->getName() .
                        ' with non-matching data type ' .
                        $marker->getData()->getType() . '.'
                    );
            }

            /**
             * This is specifically for updating/creating orComposites,
             * since they are a special case.
             */
            if ($element->getName() === 'orComposite') {
                $value_el = ($element->getSubElements('type')[0] ?? null)
                                ?->getSubElements('value')[0] ?? null;
                $type = '';
                if (!$value_el?->isScaffold()) {
                    $type = $value_el?->getData()?->getValue();
                }
                if ($marker = $value_el?->getMarker()) {
                    $type = $marker->getData()->getValue();
                }
                if ($type === 'browser') {
                    $next_id = ilMDLOMDatabaseDictionary::MD_ID_BROWSER;
                }
                if ($type === 'operating system') {
                    $next_id = ilMDLOMDatabaseDictionary::MD_ID_OS;
                }
            }
            return $next_id;
        };

        $null_action = function (): void {
        };

        $this->validateMD(
            'create and upated',
            $root,
            true
        );

        $this->iterateThroughMD(
            true,
            $root,
            $this->getNewDBStructure(),
            $action,
            $null_action,
            0,
            [],
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
        $el = $element;
        while (!($el instanceof ilMDRootElement)) {
            array_unshift($name_path, $el->getName());
            $el = $el->getSuperElement();
        }
        $structure = $this->getNewDBStructure();
        foreach ($name_path as $next_name) {
            $structure->movePointerToSubElement($next_name);
        }

        //get the sub-elements as scaffolds
        $sub_elements = $structure->getSubElementsAtPointer();
        $scaffolds = [];
        foreach ($sub_elements as $sub_element) {
            if ($name !== '' && $name !== $sub_element) {
                continue;
            }
            $structure->movePointerToSubElement($sub_element);
            if (
                $structure->isUniqueAtPointer() &&
                !empty($element->getSubElements($sub_element))
            ) {
                $structure->movePointerToSuperElement();
                continue;
            }
            $scaffolds[] = new ilMDScaffoldElement(
                $sub_element,
                $structure->isUniqueAtPointer(),
                []
            );
            $structure->movePointerToSuperElement();
        }
        return $scaffolds;
    }

    public function getMD(): ilMDRootElement
    {
        $structure = $this->getNewDBStructure();
        $root =  new ilMDRootElement(
            $this->rbac_id,
            $this->obj_id,
            $this->obj_type,
            $structure->getNameAtPointer(),
            $this->getSubElements(
                $structure,
                [],
                0,
                1
            ),
            $this->data_factory->null()
        );
        $this->validateMD(
            'read',
            $root,
            false
        );
        return $root;
    }

    /**
     * @return ilMDBaseElement[]
     */
    public function getMDOnPath(ilMDPathFromRoot $path): array
    {
        $this->validatePath($path);
        $structure = $this->getNewDBStructure();
        $root = new ilMDRootElement(
            $this->rbac_id,
            $this->obj_id,
            $this->obj_type,
            $structure->getNameAtPointer(),
            $this->getSubElements(
                $structure,
                [],
                0,
                1,
                $path
            ),
            $this->data_factory->null()
        );

        $this->validateMD(
            'read',
            $root,
            false
        );

        return $root->getSubElementsByPath($path, $this);
    }

    /**
     * @throws ilMDStructureException
     */
    protected function validatePath(ilMDPathFromRoot $path): void
    {
        $structure = $this->getNewDBStructure();
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
     * @param ilMDLOMDatabaseStructure $structure
     * @param int[]                    $parent_ids
     * @param int                      $super_id
     * @param int                      $depth
     * @param ilMDPathFromRoot|null    $path
     * @return ilMDElement[]
     */
    protected function getSubElements(
        ilMDLOMDatabaseStructure $structure,
        array $parent_ids,
        int $super_id,
        int $depth,
        ?ilMDPathFromRoot $path = null
    ): array {
        //stop the recursion after a while, just to be safe.
        if ($depth >= 20) {
            throw new ilMDRepositoryException(
                'Recursion reached its maximum depth'
            );
        }

        $sub_elements = [];
        foreach ($structure->getSubElementsAtPointer() as $sub_name) {
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
            $res = $this->executeRead($tag, $parent_ids, $super_id);

            //check whether unique elements are actually unique
            $unique = $new_structure->isUniqueAtPointer();
            if ($unique && $res->rowCount() > 1) {
                $this->logger->warning(
                    'There are multiples of the unique element ' .
                    $sub_name . ' in the table ' . $tag->getTable() .
                    ' for the object with rbac_id=' . $this->rbac_id .
                    ', obj_id=' . $this->obj_id .
                    ', obj_type=' . $this->obj_type .
                    '. Using the first element found.'
                );
            }

            $index = 1;
            while ($row = $this->db->fetchAssoc($res)) {
                $new_parent_ids = $parent_ids;

                //get the id of the element
                if (!isset($row[ilMDLOMDatabaseDictionary::RES_MD_ID])) {
                    throw new ilMDRepositoryException(
                        'Query for element ' . $sub_name .
                        ' did not return an ID.'
                    );
                }
                $md_id = (int) $row[ilMDLOMDatabaseDictionary::RES_MD_ID];
                if ($tag->isParent()) {
                    $new_parent_ids[] = $md_id;
                }

                //check the filter
                if (
                    isset($path) &&
                    $depth < $path->getPathLength()
                ) {
                    if (
                        !empty($path->getIndexFilter($depth)) &&
                        !in_array($index, $path->getIndexFilter($depth))
                    ) {
                        continue;
                    }
                    if (
                        !empty($path->getMDIDFilter($depth)) &&
                        !in_array($md_id, $path->getMDIDFilter($depth))
                    ) {
                        continue;
                    }
                }
                $index += 1;

                //get the data of the element, if it should have any
                $type = $new_structure->getTypeAtPointer();
                if (
                    !array_key_exists(ilMDLOMDatabaseDictionary::RES_DATA, $row) &&
                    $type !== ilMDLOMDataFactory::TYPE_NULL
                ) {
                    throw new ilMDRepositoryException(
                        'A read query for the elemement ' . $sub_name .
                        ' with data should return data.'
                    );
                }
                if (
                    array_key_exists(ilMDLOMDatabaseDictionary::RES_DATA, $row) &&
                    $type === ilMDLOMDataFactory::TYPE_NULL
                ) {
                    throw new ilMDRepositoryException(
                        'A read query for the elemement ' . $sub_name .
                        ' with no data should not return any data.'
                    );
                }
                $value = (string) ($row[ilMDLOMDatabaseDictionary::RES_DATA] ?? '');
                if (
                    $type !== ilMDLOMDataFactory::TYPE_NULL &&
                    $value === ''
                ) {
                    continue;
                }

                $data = $this->data_factory->byPath(
                    $value,
                    $this->path_factory
                        ->getStructurePointerAsPath($new_structure),
                    $this->vocab_dictionary
                );

                //create the element
                $sub_elements[] = new ilMDElement(
                    $sub_name,
                    $new_structure->isUniqueAtPointer(),
                    $this->getSubElements(
                        $new_structure,
                        $new_parent_ids,
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
        /**
         * @param ilMDBaseElement          $element
         * @param ilMDLOMDatabaseStructure $structure
         * @param int                      $super_md_id
         * @param int[]                    $parent_ids
         * @return int
         */
        $action = function (
            ilMDBaseElement $element,
            ilMDLOMDatabaseStructure $structure,
            int $super_md_id,
            array $parent_ids
        ): int {
            if (!($element instanceof ilMDElement)) {
                return 0;
            }

            foreach ($element->getSubElements() as $sub_element) {
                if ($sub_element->getMarker()) {
                    return $element->getMDID();
                }
            }

            $null_action = function (): void {
            };

            /**
             * @param ilMDBaseElement          $element
             * @param ilMDLOMDatabaseStructure $structure
             * @param int                      $super_md_id
             * @param int[]                    $parent_ids
             * @return void
             */
            $delete_action = function (
                ilMDBaseElement $element,
                ilMDLOMDatabaseStructure $structure,
                int $super_md_id,
                array $parent_ids
            ): void {
                if ($element->isScaffold()) {
                    return;
                }

                $this->executeManip(
                    self::MANIP_DELETE,
                    $structure->getTagAtPointer(),
                    $element->getMDID(),
                    $super_md_id,
                    $this->data_factory->null(),
                    $parent_ids
                );
            };

            $this->iterateThroughMD(
                false,
                $element,
                $structure,
                $null_action,
                $delete_action,
                $super_md_id,
                $parent_ids,
                0
            );
            return $element->getMDID();
        };

        $null_action = function (): void {
        };

        $this->iterateThroughMD(
            true,
            $root,
            $this->getNewDBStructure(),
            $action,
            $null_action,
            0,
            [],
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

    /**
     * @param bool                     $only_marked
     * @param ilMDBaseElement          $element
     * @param ilMDLOMDatabaseStructure $structure
     * @param callable                 $top_down_action
     * @param callable                 $bottom_up_action
     * @param int                      $super_md_id
     * @param int[]                    $parent_ids
     * @param int                      $depth
     * @return void
     */
    protected function iterateThroughMD(
        bool $only_marked,
        ilMDBaseElement $element,
        ilMDLOMDatabaseStructure $structure,
        callable $top_down_action,
        callable $bottom_up_action,
        int $super_md_id,
        array $parent_ids,
        int $depth
    ): void {
        //stop the recursion after a while, just to be safe.
        if ($depth >= 20) {
            throw new ilMDRepositoryException(
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
            $parent_ids
        );

        if (!is_int($new_super_md_id)) {
            $new_super_md_id = $element->getMDID();
        }

        $new_parent_ids = $parent_ids;
        if ($structure->getTagAtPointer()->isParent()) {
            $new_parent_ids[] = $new_super_md_id;
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
                $new_parent_ids,
                $depth + 1
            );
        }

        $bottom_up_action(
            $element,
            $structure,
            $super_md_id,
            $parent_ids
        );
    }

    /**
     * Checks whether the MD set and its data are correct. If not in
     * 'throw exception' mode, will delete MD elements that have invalid
     * data.
     */
    protected function validateMD(
        string $action_description,
        ilMDRootElement $root,
        bool $throw_exception
    ): void {
        /**
         * @param ilMDBaseElement          $element
         * @param ilMDLOMDatabaseStructure $structure
         * @param int                      $super_md_id
         * @param int[]                    $parent_ids
         * @return void
         */
        $action = function (
            ilMDBaseElement $element,
            ilMDLOMDatabaseStructure $structure,
            int $super_md_id,
            array $parent_ids
        ) use ($throw_exception, $action_description): int {
            $name_path = $element->getName();
            $el = $element;
            while (!$el->isRoot()) {
                $el = $el->getSuperElement();
                $name_path = $el->getName() . '>' . $name_path;
            }
            $id_string = ($element instanceof ilMDElement) ?
                ' (ID=' . $element->getMDID() . ')' :
                ' (scaffold)';
            $error_intro = 'Error during MD action ' . $action_description .
                ' for element ' . $name_path . $id_string .
                ' of the object with' .
                ' rbac_id=' . $this->rbac_id .
                ', obj_id=' . $this->obj_id .
                ', obj_type=' . $this->obj_type . ': ';

            if ($element instanceof ilMDElement) {
                $data = $element->getData();
            }
            if ($marker = $element->getMarker()) {
                $data = $marker->getData();
            }
            if (!isset($data)) {
                return 0;
            }

            if ($data->getType() !== $structure->getTypeAtPointer()) {
                $error = $error_intro . 'Data type is ' . $data->getType() .
                    ', should be ' . $structure->getTypeAtPointer();
                if ($throw_exception) {
                    throw new ilMDRepositoryException($error);
                }
                $this->logger->warning($error);
                $element->getSuperElement()->deleteFromSubElements($element);
                return 0;
            }

            /*
             * if appropriate, grab the value of the element this element
             * is conditional on.
             */
            if ($path = $data->getPathToConditionElement()) {
                $struct = clone $structure;
                $el = $element;
                for ($i = 1; $i < $path->getPathLength(); $i++) {
                    $step = $path->getStep($i);
                    if ($step === ilMDPath::SUPER_ELEMENT) {
                        $struct->movePointerToSuperElement();
                        $el = $el->getSuperElement();
                        continue;
                    }
                    $struct->movePointerToSubElement($step);
                    $els = $el->getSubElements($step);
                    if (count($els) > 1) {
                        throw new ilMDRepositoryException(
                            $error_intro . 'Path to condition element ' .
                            'is not unique.'
                        );
                    }
                    if (count($els) === 0) {
                        $condition_value = '';
                        break;
                    }
                    $el = $els[0];
                }
                if (!isset($condition_value)) {
                    $condition_value =
                        $el->getMarker()?->getData()->getValue();
                }
                if (!isset($condition_value)) {
                    $condition_value = ($el instanceof ilMDElement) ?
                        $el->getData()->getValue() :
                        '';
                }
            }
            if ($error = $data->getError($condition_value ?? null)) {
                if ($throw_exception) {
                    throw new ilMDRepositoryException($error_intro . $error);
                }
                $this->logger->warning($error_intro . $error);
                $element->getSuperElement()->deleteFromSubElements($element);
            }
            return 0;
        };

        $null_action = function (): void {
        };

        $this->iterateThroughMD(
            false,
            $root,
            $this->getNewDBStructure(),
            $action,
            $null_action,
            0,
            [],
            0
        );
    }

    /**
     * @param string          $action
     * @param ilMDDatabaseTag $tag
     * @param int             $md_id
     * @param int             $super_md_id
     * @param ilMDData        $data
     * @param int[]           $parent_ids
     * @return void
     */
    protected function executeManip(
        string $action,
        ilMDDatabaseTag $tag,
        int $md_id,
        int $super_md_id,
        ilMDData $data,
        array $parent_ids
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
                    if (empty($parent_ids)) {
                        throw new ilMDRepositoryException(
                            'Parent ID is needed, but not set.'
                        );
                    }
                    $params[] = $parent_ids[array_key_last($parent_ids)];
                    $param_types[] = ilDBConstants::T_INTEGER;
                    break;

                case ilMDLOMDatabaseDictionary::EXP_SECOND_PARENT_MD_ID:
                    if (count($parent_ids) < 2) {
                        throw new ilMDRepositoryException(
                            'Second parent ID is needed, but not set.'
                        );
                    }
                    $params[] = $parent_ids[array_key_last($parent_ids) - 1];
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

                case ilMDLOMDatabaseDictionary::EXP_OBJ_IDS:
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
                    break;

                default:
                    throw new ilMDRepositoryException(
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
                throw new ilMDRepositoryException(
                    'Invalid manipulate action'
                );
        }
    }

    /**
     * @param ilMDDatabaseTag $tag
     * @param int[]           $parent_ids
     * @param int             $super_id
     * @return ilDBStatement
     */
    protected function executeRead(
        ilMDDatabaseTag $tag,
        array $parent_ids,
        int $super_id
    ): ilDBStatement {
        $params = [];
        $param_types = [];
        foreach ($tag->getExpectedParams() as $expected_param) {
            switch ($expected_param) {
                case ilMDLOMDatabaseDictionary::EXP_PARENT_MD_ID:
                    if (empty($parent_ids)) {
                        throw new ilMDRepositoryException(
                            'Parent ID is needed, but not set.'
                        );
                    }
                    $params[] = $parent_ids[array_key_last($parent_ids)];
                    $param_types[] = ilDBConstants::T_INTEGER;
                    break;

                case ilMDLOMDatabaseDictionary::EXP_SECOND_PARENT_MD_ID:
                    if (count($parent_ids) < 2) {
                        throw new ilMDRepositoryException(
                            'Second parent ID is needed, but not set.'
                        );
                    }
                    $params[] = $parent_ids[array_key_last($parent_ids) - 1];
                    $param_types[] = ilDBConstants::T_INTEGER;
                    break;

                case ilMDLOMDatabaseDictionary::EXP_SUPER_MD_ID:
                    $params[] = $super_id;
                    $param_types[] = ilDBConstants::T_INTEGER;
                    break;

                case ilMDLOMDatabaseDictionary::EXP_OBJ_IDS:
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
                    break;

                case ilMDLOMDatabaseDictionary::EXP_MD_ID:
                case ilMDLOMDatabaseDictionary::EXP_DATA:
                    break;

                default:
                    throw new ilMDRepositoryException(
                        'Invalid expected parameter'
                    );
            }
        }

        return $this->db->queryF(
            $tag->getRead(),
            $param_types,
            $params
        );
    }

    protected function getNewDBStructure(): ilMDLOMDatabaseStructure
    {
        return $this->db_dictionary->getStructure();
    }

    protected function getNewVocabStructure(): ilMDLOMVocabulariesStructure
    {
        return $this->vocab_dictionary->getStructure();
    }
}
