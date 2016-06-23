<?php

// classpath
namespace Mimoto\Data;

// Mimoto classes
use Mimoto\library\data\MimotoDataUtils;
use Mimoto\EntityConfig\MimotoEntityPropertyTypes;
use Mimoto\Data\MimotoCollection;


/**
 * MimotoData
 *
 * @author Sebastian Kersten (@supertaboo)
 */
class MimotoData
{
    
    /**
     * The properties connected to this data node
     * 
     * @var array
     */
    private $_aProperties;
    
    /**
     * Track change mode
     * 
     * @var boolean
     */
    private $_bTrackChanges;
    
    
    // selector 
    const SELECTOR_KEY_SEPARATOR = '.';
    const SELECTOR_EXPRESSION_START = '{';
    const SELECTOR_EXPRESSION_END = '}';
    const SELECTOR_EXPRESSION_SEPERATOR = '=';
    const SELECTOR_ARRAY_START = '[';
    const SELECTOR_ARRAY_END = ']';

    
    
    // ----------------------------------------------------------------------------
    // --- Constructor-------------------------------------------------------------
    // ----------------------------------------------------------------------------
    
    
    /**
     * Constructor
     * 
     * @param boolean $bTrackChanges (default changes are tracked)
     */
    public function __construct($bTrackChanges = true)
    {
        // register
        $this->_bTrackChanges = $bTrackChanges;
        
        // init
        $this->_aProperties = [];
    }
    
    
    
    // ----------------------------------------------------------------------------
    // --- Public methods - setup -------------------------------------------------
    // ----------------------------------------------------------------------------
    
    
    /**
     * Setup property by config
     * 
     * @param object $propertyConfig
     */
    public function setupProperty($propertyConfig)
    {
        // store
        $this->_aProperties[$propertyConfig->name] = (object) array(
            'config' => $propertyConfig,
            'data' => (object) array()
        );
    }
    
    public function getPropertyType($sPropertySelector)
    {
        return $this->_aProperties[$sPropertySelector]->config->type;
    }
    
    
    // ----------------------------------------------------------------------------
    // --- Public methods - usage -------------------------------------------------
    // ----------------------------------------------------------------------------
    
    
    /**
     * Get the value of a property
     * @param string $sPropertySelector
     * @param boolean $bGetStorableValue The storable value, in case of an entity or collection. Default is false
     * @return mixed
     */
    public function getValue($sPropertySelector, $bGetStorableValue = false)
    {
        // load
        $property = $this->getProperty($sPropertySelector);
        $sSubpropertySelector = $this->getSubpropertySelector($sPropertySelector, $property);
        
        switch($property->config->type)
        {
            case MimotoEntityPropertyTypes::PROPERTY_TYPE_VALUE: return $this->getValueProperty($property); break;
            case MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY: return $this->getEntityProperty($property, $bGetStorableValue, $sSubpropertySelector); break;
            case MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION: return $this->getCollectionProperty($property, $bGetStorableValue, $sSubpropertySelector); break;
        }
    }
    
    /**
     * Set the value of a property
     * @param string $sPropertySelector
     * @param mixed value
     */
    public function setValue($sPropertySelector, $value)
    {
        // load
        $property = $this->getProperty($sPropertySelector);
        $sSubpropertySelector = $this->getSubpropertySelector($sPropertySelector, $property);
        
        switch($property->config->type)
        {
            case MimotoEntityPropertyTypes::PROPERTY_TYPE_VALUE: $this->setValueProperty($property, $value); break;
            case MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY: $this->setEntityProperty($property, $value, $sSubpropertySelector); break;
            case MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION: $this->setCollectionProperty($property, $value, $sSubpropertySelector); break;
        }
    }
    
    public function addValue($sPropertySelector, $value, $sEntityType = null)
    {
        // load
        $property = $this->getProperty($sPropertySelector);
        $sSubpropertySelector = $this->getSubpropertySelector($sPropertySelector, $property);
        
        switch($property->config->type)
        {
            case MimotoEntityPropertyTypes::PROPERTY_TYPE_VALUE:
                
                throw new MimotoEntityException("( '-' ) - Nope, I'm unable to add a value the non-collection '$property->config->name' which is a value");
            
            case MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY:
                
                if (empty($sSubpropertySelector)) { throw new MimotoEntityException("( '-' ) - Nope, I'm unable to add a value the non-collection '$property->config->name' which is an entity"); }
                
                $this->addEntityProperty($property, $value, $sSubpropertySelector);
                break;
                
            case MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION:
                
                $this->addCollectionProperty($property, $value, $sEntityType, $sSubpropertySelector);
                break;
        }
    }
    
    
    public function serialize()
    {
        
    }
    
    
    // ----------------------------------------------------------------------------
    // --- Private methods - Properties - Value -----------------------------------
    // ----------------------------------------------------------------------------
    
    
    /**
     * Read a value property
     * @param object $property
     * @return mixed
     * @throws MimotoEntityException
     */
    private function getValueProperty($property)
    {
        // validate
        if (!isset($property->data->currentValue)) { throw new MimotoEntityException("( '-' ) - Hmm, the property '$property->config->name' you are trying to get doesn't seems to have a value set yet"); }
        
        // send
        return $property->data->currentValue;
    }
    
    /**
     * Set a value property
     * @param string $property
     * @param mixed $value
     */
    private function setValueProperty($property, $value)
    {
        // store if change tracking is disabled
        if (!$this->_bTrackChanges) { $property->data->persistentValue = $value; }

        // store
        $property->data->currentValue = $value;
    }
    
    
    
    // ----------------------------------------------------------------------------
    // --- Private methods - Properties - Entity ----------------------------------
    // ----------------------------------------------------------------------------
    
    
    /**
     * Read entity
     * @param object $property
     * @param boolean $bGetStorableValue
     * @return int or MimotoEntity
     * @throws MimotoEntityException
     */
    private function getEntityProperty($property, $bGetStorableValue = false, $sSubpropertySelector = '')
    {
        // forward
        if (!empty($sSubpropertySelector)) { return $this->forwardgetEntityProperty($property, $sSubpropertySelector, $bGetStorableValue); }
        
        // validate
        if (!isset($property->data->currentId)) { return 0; }
        
        // send
        if ($bGetStorableValue) { return $property->data->currentId; }
        
        // send
        return $this->loadEntity($property);
    }
    
    /**
     * Forward set entity property
     * @param object $property
     * @param string $sPropertySelector
     * @param boolean $bGetStorableValue Default is set to false
     * @throws MimotoEntityException
     */
    private function forwardGetEntityProperty($property, $sPropertySelector, $bGetStorableValue = false)
    {
        // validate
        if (!MimotoDataUtils::isValidEntityId($property->data->currentId)) { throw new MimotoEntityException("( '-' ) - Sorry, the entity '$property->config->name' for which you are trying to set the property '$sPropertySelector' doesn't seem to be set yet"); }
        
        // load
        $entity = $this->loadEntity($property);
        
        // forward
        return $entity->getValue($sPropertySelector, $bGetStorableValue);
    }
    
    /**
     * Load entity
     * @param object $property
     * @return MimotoEntity
     */
    private function loadEntity($property)
    {
        // check if available
        if (!isset($property->data->entityCache))
        {
            if (MimotoDataUtils::isValidEntityId($property->data->currentId))
            {                    
                // load
                $property->data->entityCache = $GLOBALS['Mimoto.Data']->get($property->config->settings->entityType->name, $property->data->currentId);
            }
        }
        
        // send
        return $property->data->entityCache;
    }
    
    
    /**
     * Set entity property
     * @param object $property
     * @param mixed $value
     * @param string $sPropertySelector
     * @return property
     * @throws MimotoEntityException
     */
    private function setEntityProperty($property, $value, $sPropertySelector)
    {
        // forward
        if (!empty($sPropertySelector)) { $this->forwardSetEntityProperty($property, $sPropertySelector, $value); return; }
        
        // validate
        if (MimotoDataUtils::isEntity($value) && $value->getEntityType() !== $property->config->settings->entityType) { throw new MimotoEntityException("( '-' ) - Sorry, the entity you are trying to set at '$property->config->name' has type '".$value->getEntityType()."' instead of type '$property->config->settings->entityType'"); }

        if (MimotoDataUtils::isEntity($value) )
        {
            // store if change tracking is disabled
            if (!$this->_bTrackChanges) { $property->data->persistentId = $value->getId(); }

            // store
            $property->data->currentId = $value->getId();
            $property->data->entityCache = $value;

            return;
        }
        else
        if (MimotoDataUtils::isValidEntityId($value))
        {
            // store if change tracking is disabled
            if (!$this->_bTrackChanges) { $property->data->persistentId = $value; }

            // store
            $property->data->currentId = $value;

            return;
        }
        else
        if (empty($value) || $value == 0)
        {
            // store if change tracking is disabled
            if (!$this->_bTrackChanges) { $property->data->persistentId = 0; }
            
            // clear
            unset($property->data->currentId);
            unset($property->data->entityCache);
            
            return;
        }

        // validate
        throw new MimotoEntityException("( '-' ) - Sorry, the entity or entity id you are trying to set at '$property->config->name' doesn't seem to be valid");
    }
    
    /**
     * Forward set entity property
     * @param object $property
     * @param string $sPropertySelector
     * @param mixed $value
     * @throws MimotoEntityException
     */
    private function forwardSetEntityProperty($property, $sPropertySelector, $value)
    {
        // validate
        if (!MimotoDataUtils::isValidEntityId($property->data->currentId)) { throw new MimotoEntityException("( '-' ) - Sorry, the entity '$property->config->name' for which you are trying to set the property '$sPropertySelector' doesn't seem to be set yet"); }
        
        // load
        $entity = $this->loadEntity($property);
        
        // forward
        $entity->setValue($sPropertySelector, $value);
    }
    
    
    
    
    
    // ----------------------------------------------------------------------------
    // --- Private methods - Properties - Collection ------------------------------
    // ----------------------------------------------------------------------------
    
    
    /**
     * Read collection
     * @param collection object $property
     * @param boolean $bGetStorableValue
     * @return MimotoCollection ----> id's or anders? | array met id's of MimotoCollection
     * @throws MimotoEntityException
     */
    private function getCollectionProperty($property, $bGetStorableValue = false, $sSubpropertySelector = '')
    {
        
        // 1. collection gaat alleen over volgorde en referenties, niet om feitelijk inhoud
        // 2. inhoud draagt eigen changed-status
        // 3. dit houdt het management van de collection vrij eenvoudig
        
        
        //echo "\nsSubpropertySelector=".$sSubpropertySelector."<br>\n\n";
        
        
        
        // init
        $aConditionals = MimotoDataUtils::getConditionals($sSubpropertySelector);
        
        
            
        if (preg_match("/^\[\]$/", $sSubpropertySelector))
        {
            /* array with comma separated multiple key support */ 
            
        }
        
        // 1. regexp voor alles
        // 2. value voor alles
        
        if (preg_match("/^\/\/$/", $sSubpropertySelector))
                {
            /* regexp */
        }
        

        /* regular value */

        
        if ($bGetStorableValue)
        {
            // 1. de data moet eerst geladen worden
            // 2. indien geladen, opslaan
            // 3. indien opgeslagen, gebruik uit geheugen

            
            $aCollectionItems = $property->data->currentCollection;

            $aCollection = []; 
            
            for ($i = 0; $i < count($aCollectionItems); $i++)
            {
                // register
                $collectionItem = $aCollectionItems[$i];
                
                // load
                $entity = $GLOBALS['Mimoto.Data']->get($collectionItem->childEntityType->name, $collectionItem->childId);
                
                
                $bVerified = true;
                foreach ($aConditionals as $sKey => $value)
                {
                    // verify
                    if ($entity->getValue($sKey) != $value)
                    {
                        $bVerified = false;
                        break;
                    }
                }
                                
                if ($bVerified) { $aCollection[] = $entity; }

            }
            return $aCollection;
        }
        
        
        
        // 1. maak kopie van $property->data->currentCollection
        
        
        return $property->data->currentCollection;
        
//        return $value = (object) array
//        (
//            'name' => $property->config->name,
//            'value' => $property->data->currentCollection
//        );
    }
    
    
    
    
    private function setCollectionProperty($property, $value, $sPropertySelector)
    {
        // forward
        if (!empty($sPropertySelector)) { $this->forwardSetCollectionProperty($property, $sPropertySelector, $value); return; }
        // 1. in de forward komt de selector-query of [1]
        
        // validate
        if (!is_array($value)) { throw new MimotoEntityException("( '-' ) - The collection property '".$property->config->name."' can only accept an array"); }
        
        
        // init
        if (!$this->_bTrackChanges) { $property->data->persistentCollection = []; }
        $property->data->currentCollection = [];
        

        $nItemCount = count($value);
        for ($i = 0; $i < $nItemCount; $i++)
        {
            // register
            $item = $value[$i];
            
            // store
            $subproperty = $item;
            
            // store
            if (!$this->_bTrackChanges) { $property->data->persistentCollection[$i] = clone $subproperty; }
            $property->data->currentCollection[] = $subproperty;
        }
    }
    
    /**
     * Add collection property
     * @param object $property
     * @param mixed $value
     * @param string $sPropertySelector
     * @throws MimotoEntityException
     */
    private function addCollectionProperty($property, $value, $sEntityType, $sPropertySelector)
    {
        // forward
        if (!empty($sPropertySelector)) { $this->forwardAddEntityProperty($property, $sPropertySelector, $value); return; }
        
        
        // validate input
        if (!MimotoDataUtils::isEntity($value) && !MimotoDataUtils::isValidEntityId($value)) { throw new MimotoEntityException("( '-' ) - Sorry, the value you are trying to add at to collection '$property->config->name' is not a MimotoEntity"); }
        
        
        if (MimotoDataUtils::isEntity($value))
        {
            $sEntityType = $value->getEntityType();
        }
        else
        {
            if ($sEntityType === null)
            {
                // validate
                if (count($property->config->settings->allowedEntityTypes) != 1) { throw new MimotoEntityException("( '-' ) - Please provide an entity type if you only pass an id when adding an item to the collection '$property->config->name' which allows the types ".json_encode($property->config->allowedEntityTypes)); }
                
                // auto define
                $sEntityType = $property->config->settings->allowedEntityTypes[0]->name;
            }
        }
        
        // init
        $subproperty = (object) array();
        
        $subproperty->parentId = $this->getId();
        $subproperty->parentPropertyId = $property->config->id;
        
        
        $aAllowedEntityTypes = [];
        for ($i = 0; $i < count($property->config->settings->allowedEntityTypes); $i++)
        {
            $aAllowedEntityTypes[] = $property->config->settings->allowedEntityTypes[$i]->name;
        }
        
        
        // validate
        if (MimotoDataUtils::isEntity($value) && !in_array($sEntityType, $aAllowedEntityTypes)) { throw new MimotoEntityException("( '-' ) - Sorry, the entity you are trying to set at '$property->config->name' has type '".$value->getEntityType()."' instead of on of the types ".json_encode($property->config->settings->allowedEntityTypes)); }

        if (MimotoDataUtils::isEntity($value))
        {
            // store
            $subproperty->childId = $value->getId();
        }
        else
        if (MimotoDataUtils::isValidEntityId($value))
        {
            // store
            $subproperty->childId = $value;
        }

        $subproperty->childEntityType = (object) array(
            'id' => $GLOBALS['Mimoto.Config']->getEntityIdByName($sEntityType),
            'name' => $sEntityType
        );
        $subproperty->sortIndex = count($property->data->currentCollection);
        
        // store
        if (!$this->_bTrackChanges) { $property->data->persistentCollection[$i] = clone $subproperty; }
        $property->data->currentCollection[] = $subproperty;
        
        
        // validate
        //throw new MimotoEntityException("( '-' ) - Sorry, the entity or entity id you are trying to set at '$property->config->name' doesn't seem to be valid");
    }
    
    
    
    // ----------------------------------------------------------------------------------
    // ----------------------------------------------------------------------------------
    // ----------------------------------------------------------------------------------
    // ----------------------------------------------------------------------------------
    // ----------------------------------------------------------------------------------
    
    
    /**
     * Get property
     * @param type $sPropertySelector
     * @return single property or collection of properties
     * @throws MimotoEntityException
     */
    private function getProperty($sPropertySelector)
    {
        // validate
        if (!MimotoDataUtils::validatePropertySelector($sPropertySelector)) { throw new MimotoEntityException("( '-' ) - The property selector '$sPropertySelector' seems to be malformed"); }        
        
        // init
        $aMatchingProperties = [];
        
        // check all properties
        foreach ($this->_aProperties as $sPropertyName => $property)
        {
            // search
            if ($sPropertyName === $sPropertySelector || (strlen($sPropertySelector) > strlen($sPropertyName) && $sPropertyName.'.' === substr($sPropertySelector, 0, strlen($sPropertyName) + 1)))
            {
                // register
                $aMatchingProperties[] = $property;
                break;
            }
        }
        
        // verify
        if (count($aMatchingProperties) === 0) { throw new MimotoEntityException("( '-' ) - The property '$sPropertySelector' you are looking for doesn't seem to be here"); }        
        
        // send
        return $aMatchingProperties[0];
    }
    
    /**
     * Get subproperty selector
     * @param string $sPropertySelector
     * @param object $property
     * @return string
     */
    private function getSubpropertySelector($sPropertySelector, $property)
    {
        // strip
        $sSubpropertySelector = substr($sPropertySelector, strlen($property->config->name));
        
        // strip more
        if (substr($sSubpropertySelector, 0, 1) === '.') { $sSubpropertySelector = substr($sSubpropertySelector, 1); }
        
        // send
        return $sSubpropertySelector;
    }



    /** _______________________________________________________________________________________
     * ___                                         ____________________________________________
     * ___|   Private functions - string parsing  |____________________________________________
     * ________________________________________________________________________________________
     */


    /**
     * Split selector and respect expressions
     */
    private function splitSelector($sPropertySelector)
    {
        return explode(self::SELECTOR_KEY_SEPARATOR, $sPropertySelector);
    }

    
    
    public function valueRelatesToEntity($sPropertyName)
    {
        //output('$sPropertyName', $sPropertyName);
        //output('$this->_aProperties[$sPropertyName]', $this->_aProperties[$sPropertyName]);
        
        // verify and send
        return (isset($this->_aProperties[$sPropertyName]) && $this->_aProperties[$sPropertyName]->config->type == MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY);
    }

    
    
    
    /**
     * Add an item to a collection
     * 
     * @param string $sPropertySelector The selector containing the property name and optional subselector
     * @param mixed $value The item (id or entity)
     * @param index $nIndex (Optional) The index on which to add the item
     * @throws MimotoEntityException
     */
    public function add($sPropertySelector, $value, $nIndex = null)
    {
        // prepare
        $sPropertyName = MimotoDataUtils::getPropertyFromPropertySelector($sPropertySelector);
        $sSubselector = MimotoDataUtils::getSubselectorFromPropertySelector($sPropertySelector, $sPropertyName);
        
        // load
        //if ($this->hasProperty($sPropertyName)) { $property = $this->_aProperties[$sPropertyName]; }
        $property = $this->_aProperties[$sPropertyName];
        
        // report
        if ($property instanceof MimotoValueProperty) { throw new MimotoEntityException("( '-' ) - It's not possible to add an item to value"); }
        
        // forward
        $property->add($sSubselector, $value, $nIndex);
    }
    
    /**
     * Remove an item from a collection
     * 
     * @param string $sPropertySelector The selector containing the property name and optional subselector
     * @param mixed $value The item (id or entity)
     * @throws MimotoEntityException
     */
    public function remove($sPropertySelector, $value)
    {
        // prepare
        $sPropertyName = MimotoDataUtils::getPropertyFromPropertySelector($sPropertySelector);
        $sSubselector = MimotoDataUtils::getSubselectorFromPropertySelector($sPropertySelector, $sPropertyName);
        
        // load
        $property = $this->_aProperties[$sPropertyName];
        
        // report
        if ($property instanceof MimotoValueProperty) { throw new MimotoEntityException("( '-' ) - It's not possible to remove an item from value"); }
        
        // forward
        $property->from($sSubselector, $value);
    }
    
    /**
     * Get Changes
     * @return array Collection containing of all changed properties as key/value pairs
     */
    public function getChanges()
    {
        // init
        $aModifiedValues = [];
        
        foreach ($this->_aProperties as $sPropertyName => $property)
        {
            // register
            $property = $this->_aProperties[$sPropertyName];
            
            switch($property->config->type)
            {
                case MimotoEntityPropertyTypes::PROPERTY_TYPE_VALUE:
                
                    if (!isset($property->data->persistentId) || $property->data->persistentValue != $property->data->currentValue)
                    {
                        $aModifiedValues[$sPropertyName] = $property->data->currentValue;
                    }
                    break;

                case MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY:
                    
                    if (isset($property->data->currentId))
                    {
                        if (!isset($property->data->persistentId) || $property->data->persistentId != $property->data->currentId)
                        {
                            $aModifiedValues[$sPropertyName] = $property->data->currentId;
                        }
                    }
                    break;

                case MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION:
                    
                    // init
                    $aAddedItems = [];
                    $aUpdatedItems = [];
                    $aRemovedItems = [];
                    
                    
                    if (!empty($property->data->currentCollection))
                    {
                        for ($c = 0; $c < count($property->data->currentCollection); $c++)
                        {
                            $currentItem = $property->data->currentCollection[$c];
                            
                            // add new items
                            if (!isset($currentItem->id)) { $aAddedItems[] = $currentItem; continue; }
                            
                            // add updated items
                            if (!empty($property->data->persistentCollection))
                            {
                                for ($p = 0; $p < count($property->data->persistentCollection); $p++)
                                {
                                    $persistentItem = $property->data->persistentCollection[$p];
                                    
                                    if ($persistentItem->id == $currentItem->id)
                                    {
                                        if ($persistentItem->sortIndex != $currentItem->sortIndex)
                                        {
                                            $aUpdatedItems[] = $currentItem;
                                            break;
                                        }
                                    }
                                }
                                
                                
                            }
                        }
                    }
                    else
                    {
                        // add removed items
                        if (!empty($property->data->persistentCollection))
                        {
                            for ($k = 0; $k < count($property->data->persistentCollection); $k++)
                            {
                                $aRemovedItems[] = $property->data->persistentCollection[$k];
                            }
                        }
                    }
                    
                    if (count($aAddedItems) > 0 || count($aChangedItems) > 0 || count($aRemovedItems) > 0)
                    {
                        $aModifiedValues[$sPropertyName] = (object) array(
                            'added' => $aAddedItems,
                            'updated' => $aUpdatedItems,
                            'removed' => $aRemovedItems,
                        );
                    }
                    break;
            }
        }
        
        // send
        return $aModifiedValues;
    }
    
    
    /**
     * Start tracking changes
     */
    public function trackChanges()
    {
        // toggle
        $this->_bTrackChanges = true;
    }
    
    /**
     * Check if the value was changed
     * 
     * @return boolean True if value was changed
     */
    public function hasChanges()
    {
        // check and send
        return (count($this->getChanges()) > 0) ? true : false;
    }
    
    /**
     * Accept the changes made to the value
     */
    public function acceptChanges()
    {
        foreach ($this->_aProperties as $sPropertyName => $property)
        {
            // register
            $property = $this->_aProperties[$sPropertyName];
            
            switch($property->config->type)
            {
                case MimotoEntityPropertyTypes::PROPERTY_TYPE_VALUE:
                
                    $property->data->persistentValue = $property->data->currentValue;
                    break;

                case MimotoEntityPropertyTypes::PROPERTY_TYPE_ENTITY:
                    
                    if (!empty($property->data->currentId))
                    {
                        $property->data->persistentId = $property->data->currentId;
                    }
                    else
                    {
                        unset($property->data->persistentId);
                    }
                    break;

                case MimotoEntityPropertyTypes::PROPERTY_TYPE_COLLECTION:
                    
                    // delete
                    $property->data->persistentCollection = [];
                    
                    if (!empty($property->data->currentCollection))
                    {
                        for ($k = 0; $k < count($property->data->currentCollection); $k++)
                        {
                            $property->data->persistentCollection[$k] = clone $property->data->currentCollection[$k];
                        }
                    }
                    break;
            }
        }
    }
    
    /**
     * Check if the data object has a property
     * 
     * @return boolean True if value was changed
     */
    public function hasProperty($sProperty)
    {
        return isset($this->_aProperties[$sProperty]);
    }
    
}
