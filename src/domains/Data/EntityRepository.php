<?php

// classpath
namespace Mimoto\Data;

// Mimoto classes
use Mimoto\Mimoto;
use Mimoto\Core\CoreConfig;
use Mimoto\EntityConfig\EntityConfig;
use Mimoto\EntityConfig\MimotoEntityPropertyTypes;
use Mimoto\Event\MimotoEvent;
use Mimoto\Selection\SelectionRule;


/**
 * EntityRepository
 *
 * @author Sebastian Kersten (@supertaboo)
 */
class EntityRepository
{
    
    /**
     * The EventService
     * @var class
     */
    private $_EventService;
    
    /**
     * The requested entities
     * @var array
     */
    private $_aEntities = [];
    
    
    
    // ----------------------------------------------------------------------------
    // --- Constructor ------------------------------------------------------------
    // ----------------------------------------------------------------------------
    
    
    /**
     * Constructor
     * @param EventService $EventService
     */
    public function __construct($EventService)
    {
        // register
        $this->_EventService = $EventService;
    }
    
    
    
    // ----------------------------------------------------------------------------
    // --- Public methods - runtime usage -----------------------------------------
    // ----------------------------------------------------------------------------
    
    
    /**
     * Create new entity
     * @return MimotoEntity
     */
    public function create(EntityConfig $entityConfig)
    {
        // init and send
        return $this->createEntity($entityConfig);
    }
    
    /**
     * Get single entity by id
     * @param int $nId
     * @return MimotoEntity
     * @throws MimotoEntityException
     */
    public function get(EntityConfig $entityConfig, $nEntityId)
    {
        if (substr($nEntityId, 0, strlen(CoreConfig::CORE_PREFIX)) == CoreConfig::CORE_PREFIX)
        {



            // 1. hoe ophalen core data
            // 2. aparte functie (kent alle types - registerClass by key)
            // 3. create default -> setId() en setName()

            // TEMP - ease and quick fix



            // a. load
            $coreData = CoreConfig::getCoreData($entityConfig->getId(), $nEntityId);

            // b. verify
            if ($coreData instanceof MimotoEntity)
            {
                $entity = $coreData;
            }
            else
            {
                // prepare
                $entityData = array(
                    'id' => $nEntityId,
                    'created' => CoreConfig::EPOCH
                );

                // create
                $entity = $this->createEntity($entityConfig, $entityData);

                if ($coreData !== false)
                {
                    foreach ($coreData as $sPropertyName => $value)
                    {
                        $entity->setValue($sPropertyName, $value);
                    }
                }

                else
                {
                    if ($entity->hasProperty('name')) $entity->setValue('name', $nEntityId);
                }
            }


            //$entity->setValue('name', $entityConfig->getName()); // note: niet op alles van toepassing, dus niet te gebruiken
            // verplaats maar naar function getData() in de entity config

            // send
            return $entity;
        }
        else
        {

            // #todo - validate entityId (hoort hier, want centraal validatie van input

            // validate
            //if (is_nan($nEntityId) || $nEntityId < 0) { throw new MimotoEntityException("( '-' ) - Sorry, the entity id '$nEntityId' you passed is not a valid. Should be an integer > 0"); }

            // load
            $stmt = Mimoto::service('database')->prepare('SELECT * FROM `'.$entityConfig->getMySQLTable().'` WHERE id = :id');
            $params = array(
                ':id' => $nEntityId
            );
            $stmt->execute($params);

            // load
            $aResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // verify
            if (count($aResults) !== 1)
            {

                //Mimoto::service('log')->silent("Entity not found", "Sorry, I can't find the '" . $entityConfig->getName() . "' entity with id='$nEntityId'");
                return null;
            }
            else
            {
                // setup
                $entity = $this->createEntity($entityConfig, $aResults[0]);

                // send
                return $entity;
            }
        }
    }
    
    /**
     * Find a collection entities
     * @return array containing zero or more entities
     */
    public function find(EntityConfig $entityConfig, $criteria)
    {
        
        // init
        $aEntities = [];
        
        // setup
        //$aEntities->setCriteria($criteria);


        $sQuery = 'SELECT * FROM `'.$entityConfig->getMySQLTable().'`';
        $params = array();

        if (isset($criteria['value']))
        {
            $bFirst = true;
            foreach($criteria['value'] as $sKey => $value)
            {
                // prepare
                if ($bFirst) { $bFirst = false; $sQuery .= ' WHERE '; } else { $sQuery .= ', '; }

                // compose
                $sQuery .= '`'.$sKey.'` = :'.$sKey;

                // add
                $params[':'.$sKey] = $value;
            }
        }

        // load
        $stmt = Mimoto::service('database')->prepare($sQuery);
        $stmt->execute($params);

        // load
        $aResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);


        // register
        $nResultCount = count($aResults);
        for ($i = 0; $i < $nResultCount; $i++)
        {
            // register
            $aEntities[] = $this->createEntity($entityConfig, $aResults[$i]);
        }

        // send
        return $aEntities;
    }



    public function select(EntityConfig $entityConfig, SelectionRule $rule)
    {
        // init
        $aEntities = [];

        // validate
        if (empty($rule->getType()))
        {
            // report
            if (Mimoto::isInDebugMode()) Mimoto::service('log')->warn("Missing selection parameter", "Selection rule is missing an entity type");

            // send
            return $aEntities;
        }


        if (!empty($rule->getId()))
        {
            // load
            $eEntity = Mimoto::service('data')->get($rule->getType(), $rule->getId());

            // validate
            if (empty($eEntity)) return $aEntities;


            // validate
            if (!empty($rule->getProperty()))
            {
                // register
                $xProperty = $rule->getProperty();

                // convert to label in case of id
                $sPropertyName = (MimotoDataUtils::isValidId($xProperty)) ? Mimoto::service('config')->getPropertyNameById($xProperty) : $xProperty;

                    // validate
                if ($eEntity->hasProperty($sPropertyName))
                {
                    // register
                    $sPropertyType = $eEntity->getPropertyType($sPropertyName);

                    switch($sPropertyType)
                    {
                        case MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION:

                            $aEntities = $eEntity->getValue($sPropertyName);
                            break;

                        case MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY:

                            $aEntities[] = $eEntity->getValue($sPropertyName);
                            break;
                    }

                    // verify
                    if (!empty($rule->getChildTypes()))
                    {
                        // register
                        $aChildTypes = $rule->getChildTypes();

                        // filter
                        for ($nEntityIndex = 0; $nEntityIndex < count($aEntities); $nEntityIndex++)
                        {
                            // register
                            $eChild = $aEntities[$nEntityIndex];

                            // validate
                            if (!in_array($eChild->getEntityTypeName(), $aChildTypes))
                            {
                                // remove
                                array_splice($aEntities, $nEntityIndex, 1);

                                // update
                                $nEntityIndex--;
                            }
                        }
                    }


                    // register
                    $aRegisteredChildValues = $rule->getRegisteredChildValues();

                    // verify
                    if (!empty($aRegisteredChildValues))
                    {
                        // filter
                        for ($nEntityIndex = 0; $nEntityIndex < count($aEntities); $nEntityIndex++)
                        {
                            // register
                            $eChild = $aEntities[$nEntityIndex];

                            // search
                            $nValueCount = count($aRegisteredChildValues);
                            for ($nValueIndex = 0; $nValueIndex < $nValueCount; $nValueIndex++)
                            {
                                // register
                                $xPropertyName = $aRegisteredChildValues[$nValueIndex];
                                $valueToCompare = $rule->getChildValue($xPropertyName);

                                // convert to label in case of id
                                $sPropertyName = (MimotoDataUtils::isValidId($xPropertyName)) ? Mimoto::service('config')->getPropertyNameById($xPropertyName) : $xPropertyName;

                                // validate
                                if (!$eChild->hasProperty($sPropertyName) || $eChild->getValue($sPropertyName) != $valueToCompare)
                                {
                                    // remove
                                    array_splice($aEntities, $nEntityIndex, 1);

                                    // update
                                    $nEntityIndex--;
                                    break;

                                }
                            }
                        }
                    }
                }
            }
            else
            {
                $aEntities[] = $eEntity;
            }

        }
        else
        {
            // compose
            $sQuery = 'SELECT * FROM `'.$entityConfig->getMySQLTable().'`';
            $params = array();

            // read
            $aRegisteredValues = $rule->getRegisteredValues();

            // verify
            if (!empty($aRegisteredValues))
            {

                // 1. #todo replace ` with \`

                $nPropertyValueCount = count($aRegisteredValues);
                for ($nPropertyValueIndex = 0; $nPropertyValueIndex < $nPropertyValueCount; $nPropertyValueIndex++)
                {
                    // register
                    $xPropertyName = $aRegisteredValues[$nPropertyValueIndex];

                    $value = $rule->getValue($xPropertyName);

                    // convert to label in case of id
                    $sPropertyName= (MimotoDataUtils::isValidId($xPropertyName)) ? Mimoto::service('config')->getPropertyNameById($xPropertyName) : $xPropertyName;


                    // prepare
                    $sQuery .= ($nPropertyValueIndex == 0) ? ' WHERE ' : ', ';

                    // compose
                    $sQuery .= '`'.$sPropertyName.'` = :'.$sPropertyName;

                    // add
                    $params[':'.$sPropertyName] = $value;
                }
            }

            // load
            $stmt = Mimoto::service('database')->prepare($sQuery);
            $stmt->execute($params);

            // load
            $aResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // register
            $nResultCount = count($aResults);
            for ($nResultIndex = 0; $nResultIndex < $nResultCount; $nResultIndex++)
            {
                // register
                $aEntities[] = $this->createEntity($entityConfig, $aResults[$nResultIndex]);
            }
        }

        // send
        return $aEntities;
    }




    /**
     * Store entity
     * @param MimotoEntity $entity
     */
    public function store(EntityConfig $entityConfig, MimotoEntity $entity)
    {
        // read
        $aModifiedValues = $entity->getChanges();

        // save nothing if no changes
        if (count($aModifiedValues) == 0 && MimotoDataUtils::isValidId($entity->getId())) { return $entity; }

        // determine
        $bIsExistingEntity = (MimotoDataUtils::isValidId($entity->getId())) ? true : false;

        // load
        $aPropertyNames = $entityConfig->getPropertyNames();
        
        // init
        $aQueryElements = [];
        $aNewConnectionsToStore = [];
        
        // load properties
        $nPropertyCount = count($aPropertyNames);
        for ($nPropertyIndex = 0; $nPropertyIndex < $nPropertyCount; $nPropertyIndex++)
        {
            // register
            $sPropertyName = $aPropertyNames[$nPropertyIndex];

            // skip if no changes
            if (!isset($aModifiedValues[$sPropertyName])) { continue; }
            
            // read
            $propertyConfig = $entityConfig->getPropertyConfig($sPropertyName);
            $propertyValue = $entityConfig->getPropertyValue($sPropertyName);

            // set value
            switch($propertyValue->type)
            {
                case EntityConfig::PROPERTY_VALUE_MYSQL_COLUMN:
                    
                    switch($propertyConfig->type)
                    {
                        case MimotoEntityPropertyTypes::PROPERTY_TYPE_VALUE:
                    
                            $aQueryElements[] = (object) array(
                                'key' => $propertyValue->mysqlColumnName,
                                'value' => MimotoDataUtils::convertRuntimeValueToStorableValue($entity->getValue($sPropertyName), $propertyConfig->settings->type->type, $propertyConfig->settings->type->value)
                            );
                            break;
                    }
                    
                    break;

                case EntityConfig::PROPERTY_VALUE_MYSQLCONNECTION_TABLE:

                    $modifiedCollection = $aModifiedValues[$sPropertyName];


                    switch($propertyConfig->type)
                    {
                        case MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY:
                        case MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION:

                            if (count($modifiedCollection->added) > 0)
                            {
                                $nAddedCount = count($modifiedCollection->added);
                                for ($k = 0; $k < $nAddedCount; $k++)
                                {
                                    // register
                                    $newItem = $modifiedCollection->added[$k];

                                    // add
                                    $aNewConnectionsToStore[] = (object) array(
                                        'dbtable' => $propertyValue->mysqlConnectionTable,
                                        'connection' => $newItem
                                    );

                                    // toggle
                                    $newItem->setNewFlag(true);
                                }
                            }

                            if (count($modifiedCollection->updated) > 0)
                            {
                                $nUpdatedCount = count($modifiedCollection->updated);
                                for ($k = 0; $k < $nUpdatedCount; $k++)
                                {
                                    // register
                                    $existingItem = $modifiedCollection->updated[$k];

                                    // alter
                                    $this->alterExistingItemInCollection($propertyValue->mysqlConnectionTable, $existingItem);
                                }
                            }

                            if (count($modifiedCollection->removed) > 0)
                            {
                                $nRemovedCount = count($modifiedCollection->removed);
                                for ($k = 0; $k < $nRemovedCount; $k++)
                                {
                                    // register
                                    $existingItem = $modifiedCollection->removed[$k];

                                    // remove
                                    $this->removeItemFromCollection($propertyValue->mysqlConnectionTable, $existingItem);
                                }
                            }

                            break;
                    }


                    break;
            }
        }


        if ($bIsExistingEntity && count($aQueryElements) > 0 || !$bIsExistingEntity)
        {

            $sQuery = '';

            if ($bIsExistingEntity && count($aQueryElements) > 0)
            {
                $sQuery = 'UPDATE';
            }
            elseif (!$bIsExistingEntity)
            {
                $sQuery = 'INSERT';
            }


            $sQuery .= ' `' . $entityConfig->getMySQLTable().'`';
            $sQuery .= (count($aQueryElements) > 0 || !$bIsExistingEntity) ? ' SET ' : '';

            $params = array();

            // compose
            $nQueryItemCount = count($aQueryElements);
            for ($nQueryItemIndex = 0; $nQueryItemIndex < $nQueryItemCount; $nQueryItemIndex++)
            {
                $sQuery .= '`' . $aQueryElements[$nQueryItemIndex]->key . '` = :' . $aQueryElements[$nQueryItemIndex]->key;
                $params[':' . $aQueryElements[$nQueryItemIndex]->key] = $aQueryElements[$nQueryItemIndex]->value;
                if ($nQueryItemIndex < $nQueryItemCount - 1)
                {
                    $sQuery .= ', ';
                }
            }

            // compose
            if ($bIsExistingEntity)
            {
                $sQuery .= " WHERE id = :id";
                $params[':id'] = $entity->getId();
            } else
            {
                $sQuery .= ((count($aQueryElements) > 0) ? ', ' : '') . "created = :created";
                $params[':created'] = date("YmdHis");
            }


            // load
            $stmt = Mimoto::service('database')->prepare($sQuery);
            $stmt->execute($params);


            // broadcast
            if (!$bIsExistingEntity)
            {
                // read and store
                $entity->setId(Mimoto::service('database')->lastInsertId());
            }

        }



        // --- events ---
        
        
        // broadcast
        if ($bIsExistingEntity)
        {   
            // register
            $sEvent = MimotoEvent::UPDATED;
        }
        else
        {
            // register
            $sEvent = MimotoEvent::CREATED;
        }


        // --- store new connections

        $nNewConnectionsToStoreCount = count($aNewConnectionsToStore);
        for ($nNewConnectionsToStoreIndex = 0; $nNewConnectionsToStoreIndex < $nNewConnectionsToStoreCount; $nNewConnectionsToStoreIndex++)
        {
            // register
            $newConnectionsToStore = $aNewConnectionsToStore[$nNewConnectionsToStoreIndex];

            // complete
            if (empty($newConnectionsToStore->connection->getParentId())) $newConnectionsToStore->connection->setParentId($entity->getId());

            // add
            $this->addItemToCollection($newConnectionsToStore->dbtable, $newConnectionsToStore->connection);
        }


        // setup
        $event = new MimotoEvent($entity, $sEvent);

        // broadcast
        $this->_EventService->sendUpdate($event->getType(), $event);
        
        
        
        // --- finish up ---
        
        
        // update
        $entity->acceptChanges();

        // send
        return $entity;
    }

    /**
     * Delete entity
     * @param entity $entity
     */
    public function delete(EntityConfig $entityConfig, MimotoEntity $eInstance = null, $nConnectionId = null)
    {
        // 1. validate
        if (empty($eInstance) || empty($nConnectionId)) return;


        // ---


        // 2. search
        $connection = MimotoDataUtils::getConnectionById($nConnectionId);

        // 3. validate
        if (empty($connection)) return false;

        // 4. get parent
        $eParent = Mimoto::service('data')->get($connection->getParentEntityTypeName(), $connection->getParentId());



        // --- broadcast ---


        // 5. setup
        $event = new MimotoEvent($eInstance, MimotoEvent::DELETED);

        // 6. broadcast
        $this->_EventService->sendUpdate($event->getType(), $event);


        // --- broadcast - end ---


        // 7. register
        $sPropertyName = $connection->getParentPropertyName();

        // remove
        switch($eParent->getPropertyType($sPropertyName))
        {
            case MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY:

                $eParent->set($sPropertyName, null);
                break;

            case MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION:

                $eParent->remove($sPropertyName, $connection);
                break;
        }

        // store
        Mimoto::service('data')->store($eParent);


        // ---


        // load
        $aParents = $this->getAllParents($eInstance);

        // verify and toggle
        if (
            count($aParents) == 0 ||
            $connection->getParentEntityTypeName() == CoreConfig::MIMOTO_ROOT ||
            $connection->getParentEntityTypeName() == CoreConfig::MIMOTO_DATASET ||
            $connection->getParentEntityTypeName() == CoreConfig::MIMOTO_ENTITY ||
            $connection->getParentEntityTypeName() == CoreConfig::MIMOTO_COMPONENT
        )
        {
            // cleanup children
            $this->cleanupChildren($eInstance);

            // cleanup entity
            $stmt = Mimoto::service('database')->prepare('DELETE FROM `'.$entityConfig->getMySQLTable().'` WHERE id = :id');
            $params = array(
                ':id' => $eInstance->getId()
            );
            $stmt->execute($params);
        }
    }

    private function cleanupParents(MimotoEntity $eInstance)
    {
        // load
        $aParents = $this->getAllParents($eInstance);

        // parse
        $nParentCount = count($aParents);
        for ($nParentIndex = 0; $nParentIndex < $nParentCount; $nParentIndex++)
        {
            // register
            $parent = $aParents[$nParentIndex];

            // #fix - Temp workaround for broken garbage collection
            if (!empty($parent->entity))
            {
                // remove
                switch(Mimoto::service('config')->getPropertyTypeById($parent->propertyId))
                {
                    case MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY:

                        $parent->entity->setValue($parent->propertyName, null);
                        break;

                    case MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION:

                        $parent->entity->removeValue($parent->propertyName, $eInstance);
                        break;
                }

                // store
                Mimoto::service('data')->store($parent->entity);
            }
        }
    }

    private function getAllParents(MimotoEntity $eInstance)
    {
        // init
        $aParentEntities = [];

        // load all connections
        $stmt = Mimoto::service('database')->prepare(
            "SELECT * FROM `".CoreConfig::MIMOTO_CONNECTION."` WHERE ".
            "child_entity_type_id = :child_entity_type_id && ".
            "child_id = :child_id ".
            "ORDER BY parent_id ASC, sortindex ASC"
        );
        $params = array(
            ':child_entity_type_id' => $eInstance->getEntityTypeId(),
            ':child_id' => $eInstance->getId()
        );
        $stmt->execute($params);

        // load
        $aResults = $stmt->fetchAll();

        // register
        $nResultCount = count($aResults);
        for ($nResultIndex = 0; $nResultIndex < $nResultCount; $nResultIndex++)
        {
            // register
            $result = $aResults[$nResultIndex];

            // convert
            $sParentEntityTypeName = Mimoto::service('config')->getEntityNameById($result['parent_entity_type_id']);

            // #fix - Temp workaround for broken garbage collection
            if (!empty($sParentEntityTypeName))
            {
                // compose
                $parent = (object) array(
                    'entity' => Mimoto::service('data')->get($sParentEntityTypeName, $result['parent_id']),
                    'propertyId' => $result['parent_property_id'],
                    'propertyName' => Mimoto::service('config')->getPropertyNameById($result['parent_property_id'])
                );

                // store
                $aParentEntities[] = $parent;
            }
        }

        // send
        return $aParentEntities;
    }

    private function cleanupChildren(MimotoEntity $eInstance)
    {
        // load
        $aChildren = $this->getAllChildren($eInstance);

        // parse
        $nChildCount = count($aChildren);
        for ($nChildIndex = 0; $nChildIndex < $nChildCount; $nChildIndex++)
        {
            // register
            $child = $aChildren[$nChildIndex];

            // remove
            switch(Mimoto::service('config')->getPropertyTypeById($child->parentPropertyId))
            {
                case MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY:

                    $eInstance->setValue($child->parentPropertyName, null);
                    break;

                case MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION:

                    $eInstance->removeValue($child->parentPropertyName, $child->entity);
                    break;
            }

            // store
            Mimoto::service('data')->store($eInstance);

            // load
            $aParents = $this->getAllParents($child->entity);

            // cleanup
            if (count($aParents) == 0)
            {
                // 1. get all children
                $this->cleanupChildren($child->entity);

                // 2. remove
                Mimoto::service('data')->delete($child->entity);
            }
        }

    }

    private function getAllChildren(MimotoEntity $eInstance)
    {
        // init
        $aChildEntities = [];

        // load all connections
        $stmt = Mimoto::service('database')->prepare(
            "SELECT * FROM `".CoreConfig::MIMOTO_CONNECTION."` WHERE ".
            "parent_entity_type_id = :parent_entity_type_id && ".
            "parent_id = :parent_id ".
            "ORDER BY parent_id ASC, sortindex ASC"
        );
        $params = array(
            ':parent_entity_type_id' => $eInstance->getEntityTypeId(),
            ':parent_id' => $eInstance->getId()
        );
        $stmt->execute($params);

        // load
        $aResults = $stmt->fetchAll();

        // register
        $nResultCount = count($aResults);
        for ($nResultIndex = 0; $nResultIndex < $nResultCount; $nResultIndex++)
        {
            // register
            $result = $aResults[$nResultIndex];

            // convert
            $sChildEntityTypeName = Mimoto::service('config')->getEntityNameById($result['child_entity_type_id']);

            // #fix - Temp workaround for broken garbage collection
            if (!empty($sChildEntityTypeName))
            {
                // compose
                $child = (object) array(
                    'entity' => Mimoto::service('data')->get($sChildEntityTypeName, $result['child_id']),
                    'parentPropertyId' => $result['parent_property_id'],
                    'parentPropertyName' => Mimoto::service('config')->getPropertyNameById($result['parent_property_id'])

                );

                // get
                $aChildEntities[] = $child;
            }
        }

        // send
        return $aChildEntities;
    }

    
    
    // ----------------------------------------------------------------------------
    // --- Private methods --------------------------------------------------------
    // ----------------------------------------------------------------------------
    
    
    /**
     * Create entity from MySQL result
     * @param MySQL query result $mysqlResult
     * @param int $nIndex
     * @return entity
     */
    private function createEntity(EntityConfig $entityConfig, $result = null)
    {
        // read
        $nEntityId = (!empty($result)) ? $result['id'] : null;


        // make sure an entity is available only once
        if (empty($nEntityId))
        {
            // init
            $eInstance = new MimotoEntity($entityConfig->getId(), $entityConfig->getName(), false);
        }
        else if (!isset($this->_aEntities[$this->getEntityIdentifier($entityConfig->getName(), $nEntityId)]))
        {
            // init
            $eInstance = new MimotoEntity($entityConfig->getId(), $entityConfig->getName(), false);

            // register
            $eInstance->setId($nEntityId);
            $eInstance->setCreated($result['created']);

            // store - #todo restore a working version of the entity caching
            //$this->_aEntities[$this->getEntityIdentifier($entityConfig->getName(), $nEntityId)] = $eInstance;
        }
        else
        {
            // load
            $eInstance = $this->_aEntities[$this->getEntityIdentifier($entityConfig->getName(), $nEntityId)];
        }


        // load
        $aPropertyNames = $entityConfig->getPropertyNames();


        // load properties
        $nPropertyCount = count($aPropertyNames);
        for ($nPropertyIndex = 0; $nPropertyIndex < $nPropertyCount; $nPropertyIndex++)
        {
            // register
            $sPropertyName = $aPropertyNames[$nPropertyIndex];
            $propertyConfig = $entityConfig->getPropertyConfig($sPropertyName);
            $propertyValue = $entityConfig->getPropertyValue($sPropertyName);

            // setup property
            $eInstance->setupProperty($propertyConfig);

            // verify
            if (!empty($nEntityId))
            {
                // set value
                switch($propertyValue->type)
                {
                    case EntityConfig::PROPERTY_VALUE_MYSQL_COLUMN:

                        // verify (Mimoto root-records like "_Mimoto_file._Mimoto_file" have no values)
                        if (isset($result[$propertyValue->mysqlColumnName]))
                        {
                            // 1. register
                            $value = MimotoDataUtils::convertStoredValueToRuntimeValue($result[$propertyValue->mysqlColumnName], $propertyConfig->settings->type->type, $propertyConfig->settings->type->value);

                            // 2. store
                            $eInstance->set($propertyConfig->name, $value);
                        }
                        break;

                    case EntityConfig::PROPERTY_VALUE_MYSQLCONNECTION_TABLE:
                        
                        // init
                        $aCollection = array();

                        // load
                        $stmt = Mimoto::service('database')->prepare(
                            'SELECT * FROM `'.$propertyValue->mysqlConnectionTable.'`'.
                            ' WHERE parent_id = :parent_id'.
                            ' && parent_property_id = :parent_property_id'.
                            ' && parent_entity_type_id = :parent_entity_type_id'.
                            ' ORDER BY sortindex'
                        );
                        $params = array(
                            ':parent_id' => $eInstance->getId(),
                            ':parent_property_id' => $propertyConfig->id,
                            ':parent_entity_type_id' => $eInstance->getEntityTypeId()
                        );

                        $stmt->execute($params);

                        // load
                        $aResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                        foreach ($aResults as $row)
                        {
                            // init
                            $connection = new MimotoEntityConnection();

                            // compose
                            $connection->setId($row['id']);
                            $connection->setParentEntityTypeId($row['parent_entity_type_id']);
                            $connection->setParentPropertyId($row['parent_property_id']);
                            $connection->setParentId($row['parent_id']);
                            $connection->setChildEntityTypeId($row['child_entity_type_id']);
                            $connection->setChildId($row['child_id']);
                            $connection->setSortIndex($row['sortindex']);

                            // store
                            $aCollection[] = $connection;
                        }

                        if ($propertyConfig->type == MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY)
                        {
                            if (isset($aCollection[0]))
                            {
                                // register collection data
                                $eInstance->setValue($propertyConfig->name, $aCollection[0]);
                            }
                        }
                        elseif ($propertyConfig->type == MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION)
                        {
                            // register collection data
                            $eInstance->setValue($propertyConfig->name, $aCollection);
                        }

                        break;
                }
            }
        }

        // start tracking changes
        $eInstance->trackChanges();



        // -- default values


        if (empty($nEntityId))
        {
            // load properties
            $nPropertyCount = count($aPropertyNames);
            for ($nPropertyIndex = 0; $nPropertyIndex < $nPropertyCount; $nPropertyIndex++)
            {
                // register
                $sPropertyName = $aPropertyNames[$nPropertyIndex];
                $propertyConfig = $entityConfig->getPropertyConfig($sPropertyName);


                // verify
                if (isset($propertyConfig->settings->{EntityConfig::SETTING_VALUE_DEFAULTVALUE}))
                {
                    // register
                    $defaultValueSetting = $propertyConfig->settings->{EntityConfig::SETTING_VALUE_DEFAULTVALUE};

                    switch($defaultValueSetting->type)
                    {
                        case MimotoEntityPropertyTypes::PROPERTY_SETTING_DEFAULTVALUE_TYPE_CURRENTTIMESTAMP:

                            $eInstance->set($propertyConfig->name, date('Y.m.d h:i:s'));
                            break;

                        case MimotoEntityPropertyTypes::PROPERTY_SETTING_DEFAULTVALUE_TYPE_CURRENTUSER:

                            $eInstance->set($propertyConfig->name, Mimoto::service('session')->currentUser());
                            break;

                        case MimotoEntityPropertyTypes::PROPERTY_SETTING_DEFAULTVALUE_TYPE_NEWENTITYINSTANCE:

                            // create
                            $eNewEntityInstance = Mimoto::service('data')->create($defaultValueSetting->value);

                            // setup
                            if ($defaultValueSetting->defaultValues)
                            {

                                foreach ($defaultValueSetting->defaultValues as $sDefaultValuePropertyName => $sDefaultValuePropertyValue)
                                {
                                    // validate
                                    if ($eNewEntityInstance->getPropertyType($sDefaultValuePropertyName) == MimotoEntityPropertyTypes::PROPERTY_TYPE_VALUE)
                                    {
                                        $eNewEntityInstance->set($sDefaultValuePropertyName, $sDefaultValuePropertyValue);
                                    }

                                }
                            }

                            // store
                            Mimoto::service('data')->store($eNewEntityInstance);

                            // connect
                            $eInstance->set($propertyConfig->name, $eNewEntityInstance);
                            break;
                    }
                }
            }
        }


        // ---


        // send
        return $eInstance;
    }
    
    
    private function getEntityIdentifier($sEntityName, $nEntityId)
    {
        return $sEntityName.'.'.$nEntityId;
    }


    private function addItemToCollection($sDBTable, MimotoEntityConnection $newItem)
    {
        // load
        $stmt = Mimoto::service('database')->prepare(
            "INSERT INTO `".$sDBTable."` SET ".
            "parent_entity_type_id = :parent_entity_type_id, ".
            "parent_property_id = :parent_property_id, ".
            "parent_id = :parent_id, ".
            "child_entity_type_id = :child_entity_type_id, ".
            "child_id = :child_id, ".
            "sortindex = :sortindex"
        );
        $params = array(
            ':parent_entity_type_id' => $newItem->getParentEntityTypeId(),
            ':parent_property_id' => $newItem->getParentPropertyId(),
            ':parent_id' => $newItem->getParentId(),
            ':child_entity_type_id' => $newItem->getChildEntityTypeId(),
            ':child_id' => $newItem->getChildId(),
            ':sortindex' => $newItem->getSortIndex()
        );

        $stmt->execute($params);

        // complete
        $newItem->setId(Mimoto::service('database')->lastInsertId());
    }

    private function alterExistingItemInCollection($sDBTable, MimotoEntityConnection $existingItem)
    {
        // load
        $stmt = Mimoto::service('database')->prepare(
            'UPDATE `'.$sDBTable.'` SET '.
            'sortindex = :sortindex '.
            'WHERE id = :id'
        );
        $params = array(
            ':sortindex' => $existingItem->getSortIndex(),
            ':id' => $existingItem->getId()
        );
        $stmt->execute($params);
    }

    private function removeItemFromCollection($sDBTable, MimotoEntityConnection $connection)
    {
        // load
        $stmt = Mimoto::service('database')->prepare(
            'DELETE FROM `'.$sDBTable.'` WHERE id = :id'
        );
        $params = array(
            ':id' => $connection->getId()
        );
        $stmt->execute($params);


        // --- cleanup file if no other connections ---

        // read
        $eEntity = Mimoto::service('data')->get($connection->getChildEntityTypeName(), $connection->getChildId());
        $aParents = $this->getAllParents($eEntity);

        // verify and cleanup
        if (empty($aParents)) Mimoto::service('data')->delete($eEntity);

        // --- cleanup files

        if ($eEntity->getEntityTypeName() == CoreConfig::MIMOTO_FILE)
        {
            // composer
            $sFile = Mimoto::value('config')->media->upload_dir.$eEntity->get('name');

            // verify
            if (file_exists($sFile)) { unlink($sFile); }
        }

    }
}
