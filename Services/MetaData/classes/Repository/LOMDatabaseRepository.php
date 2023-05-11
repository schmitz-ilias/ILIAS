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

namespace ILIAS\MetaData\Repository;

use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Elements\Structure\StructureFactory;
use ILIAS\MetaData\Elements\Scaffolds\ScaffoldFactory;
use ILIAS\MetaData\Paths\FactoryInterface as PathFactoryInterface;
use ILIAS\MetaData\Paths\Navigator\NavigatorFactoryInterface;
use ILIAS\MetaData\Structure\RepositoryInterface as StructureRepositoryInterface;
use ILIAS\MetaData\Elements\Structure\StructureSetInterface;
use ILIAS\MetaData\Repository\Dictionary\DictionaryInterface;
use ILIAS\MetaData\Repository\Dictionary\DictionaryInitiatorInterface;
use ILIAS\MetaData\Repository\Validation\CleanerInterface;
use ILIAS\MetaData\Elements\RessourceID\RessourceIDInterface;
use ILIAS\MetaData\Elements\Factory as ElementFactory;
use ILIAS\MetaData\Repository\Dictionary\LOMDictionaryInitiator;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class LOMDatabaseRepository implements RepositoryInterface
{
    protected ElementFactory $element_factory;
    protected ScaffoldFactory $scaffold_factory;
    protected PathFactoryInterface $path_factory;
    protected NavigatorFactoryInterface $navigator_factory;
    protected StructureSetInterface $structure;
    protected DictionaryInterface $dictionary;
    protected CleanerInterface $cleaner;

    protected \ilDBInterface $db;
    protected \ilLogger $logger;

    protected RessourceIDInterface $ressource_id;

    public function __construct(
        RessourceIDInterface $ressource_id,
        ElementFactory $element_factory,
        ScaffoldFactory $scaffold_factory,
        PathFactoryInterface $path_factory,
        NavigatorFactoryInterface $navigator_factory,
        StructureRepositoryInterface $structure_repository,
        DictionaryInitiatorInterface $dictionary_initiator,
        CleanerInterface $cleaner,
        \ilDBInterface $db,
        \ilLogger $logger
    ) {
        $this->ressource_id = $ressource_id;

        $this->element_factory = $element_factory;
        $this->scaffold_factory = $scaffold_factory;
        $this->path_factory = $path_factory;
        $this->navigator_factory = $navigator_factory;
        $this->structure = $structure_repository->getStructure();
        $this->dictionary = $dictionary_initiator->get();
        $this->cleaner = $cleaner;

        $this->db = $db;
        $this->logger = $logger;
    }

    public function getRessourceID(): RessourceIDInterface
    {
        return $this->ressource_id;
    }

    /**
     * @return ElementInterface[]
     */
    public function getScaffoldsForElement(
        ElementInterface $element
    ): \Generator {
        $navigator = $this->navigator_factory->structureNavigator(
            $this->path_factory->toElement($element),
            $this->structure->getRoot()
        );
        $structure_element = $navigator->elementAtLastStep();

        $sub_names = [];
        foreach ($element->getSubElements() as $sub) {
            $sub_names[] = $sub->getDefinition()->name();
        }

        foreach ($structure_element->getSubElements() as $sub) {
            $unique = $sub->getDefinition()->unqiue();
            $name = $sub->getDefinition()->name();
            if (!$unique || !in_array($name, $sub_names)) {
                yield $this->scaffold_factory->scaffold($sub->getDefinition());
            }
        }
    }

    public function deleteAllMDElements(): void
    {
        $rbac_id = $this->getRessourceID()->objID();
        $obj_id = $this->getRessourceID()->subID();
        $obj_type = $this->getRessourceID()->type();
        foreach (LOMDictionaryInitiator::TABLES as $table) {
            $query = "DELETE FROM " . $table . " " .
                "WHERE rbac_id = " . $this->db->quote($rbac_id, \ilDBConstants::T_INTEGER) . " " .
                "AND obj_id = " . $this->db->quote($obj_id, \ilDBConstants::T_INTEGER) . " " .
                "AND obj_type = " . $this->db->quote($obj_type, \ilDBConstants::T_TEXT);

            $this->db->query($query);
        }
    }
}
