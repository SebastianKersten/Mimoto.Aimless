<?php

// classpath
namespace Mimoto\Selection;

// Mimoto classes
use Mimoto\Mimoto;
use Mimoto\Core\CoreConfig;
use Mimoto\Data\MimotoEntity;


/**
 * Selection
 *
 * @author Sebastian Kersten (@supertaboo)
 */
class Selection
{
    // properties
    private $_sName = '';

    // instructions
    private $_bAllowDuplicates = false;

    // rules
    private $_aRules = [];
    private $_aliasRule = null;
    private $_data = [];



    // ----------------------------------------------------------------------------
    // --- Constructor ------------------------------------------------------------
    // ----------------------------------------------------------------------------


    /**
     * Constructor
     * @param null $selectionSettings mixed Object or array containing the selection settings
     */
    public function __construct($selectionSettings = null)
    {
        // validate
        if (!empty($selectionSettings))
        {
            if ($selectionSettings instanceof MimotoEntity && $selectionSettings->getEntityTypeName() == CoreConfig::MIMOTO_SELECTION)
            {
                // translate
                $eSelection = $selectionSettings;

                // read
                $aRules = $eSelection->getValue('rules');

                // parse rules
                $nRuleCount = count($aRules);
                for ($nRuleIndex = 0; $nRuleIndex < $nRuleCount; $nRuleIndex++)
                {
                    // register
                    $eRule = $aRules[$nRuleIndex];

                    #todo --- (and move to separate function)
                    // 1. use same code as building items on boot


                    // verify
                    if ($eRule->get('typeAsVar') && !empty($eRule->get('typeVarName'))) // #todo - validate varname
                    {
                        $this->setTypeAsVar($eRule->get('typeVarName'));
                    }
                    else
                    {
                        // read
                        $eType = $eRule->get('type');

                        // validate
                        if (!empty($eType))
                        {
                            $this->setType($eType->get('name'));
                        }
                    }

                    // verify
                    if ($eRule->get('idAsVar') && !empty($eRule->get('idVarName'))) // #todo - validate varname
                    {
                        $this->setIdAsVar($eRule->get('idVarName'));
                    }
                    else
                    {
                        // read
                        $eInstance = $eRule->get('instance');

                        // validate
                        if (!empty($eInstance))
                        {
                            $this->setId($eInstance->getid());
                        }
                    }

                    // verify
                    if ($eRule->get('propertyAsVar') && !empty($eRule->get('propertyVarName'))) // #todo - validate varname
                    {
                        $this->setPropertyAsVar($eRule->get('propertyVarName'));
                    }
                    else
                    {
                        // read
                        $eProperty = $eRule->get('property');

                        // validate
                        if (!empty($eProperty))
                        {
                            $this->setProperty($eProperty->getid());
                        }
                    }

                    //Mimoto::output('$selectionSettings', $selectionSettings);
                    // ......
                    //Mimoto::error($this);

                    $aRuleValues = $eRule->get('values');
                    if (!empty($aRuleValues))
                    {
                        // add values
                        $nRuleValueCount = count($aRuleValues);
                        for ($nRuleValueIndex = 0; $nRuleValueIndex < $nRuleValueCount; $nRuleValueIndex++)
                        {
                            // register
                            $eSelectionRuleValue = $aRuleValues[$nRuleValueIndex];

                            // add
                            $this->setValue($eSelectionRuleValue->get('propertyName'), $eSelectionRuleValue->get('value'));
                        }
                    }

                }
            }
            else
            {
                // convert
                foreach ($selectionSettings as $sKey => $value)
                {
                    switch($sKey)
                    {
                        case 'type':

                            $this->setType(is_array($selectionSettings) ? $selectionSettings['type'] : $selectionSettings->type);
                            break;

                        case 'id':

                            $this->setId(is_array($selectionSettings) ? $selectionSettings['id'] : $selectionSettings->id);
                            break;

                        case 'property':

                            $this->setProperty(is_array($selectionSettings) ? $selectionSettings['property'] : $selectionSettings->property);
                            break;

                        case 'values':

                            // register
                            $aValues = (is_array($selectionSettings) ? $selectionSettings[$sKey] : $selectionSettings->$sKey);

                            // store
                            foreach ($aValues as $sPropertyName => $valuetoCompare) $this->setValue($sPropertyName, $valuetoCompare);
                            break;
                    }

                }
            }
        }
    }



    // ----------------------------------------------------------------------------
    // --- Properties -------------------------------------------------------------
    // ----------------------------------------------------------------------------


    /**
     * Get the selection name
     * @return string The name of the selection
     */
    public function getName()
    {
        // load and send
        return $this->_sName;
    }

    /**
     * Set the selection name
     * @param $sValue string The name of the selection
     */
    public function setName($sValue)
    {
        // store
        $this->_sName = $sValue;
    }


    /**
     * Check if the selection allows duplicate entries
     * @return boolean If false, duplicates are filtered from the result
     */
    public function getAllowDuplicates()
    {
        // load and send
        return $this->_bAllowDuplicates;
    }

    /**
     * Define if the selection accept duplicate entries
     * @param $bValue boolean The name of the selection
     */
    public function setAllowDuplicates($bValue = false)
    {
        // store
        $this->_bAllowDuplicates = $bValue;
    }

    /**
     * Check if the selection is configured to return a single result
     * @return boolean The state of returning a single result
     */
    public function willReturnSingleResult()
    {
        // 1. init
        $bWillReturnSingleResult = true;

        // 2. investigate
        $nRuleCount =  count($this->_aRules);
        for ($nRuleIndex = 0; $nRuleIndex < $nRuleCount; $nRuleIndex++)
        {
            // a. register
            $rule = $this->_aRules[$nRuleIndex];

            // b. verify
            if (!$rule->willReturnSingleResult())
            {
                // I. toggle
                $bWillReturnSingleResult = false;
                break;
            }
        }

        // 3. respond
        return $bWillReturnSingleResult;
    }


    
    // ----------------------------------------------------------------------------
    // --- Public methods ---------------------------------------------------------
    // ----------------------------------------------------------------------------


    /**
     * Add a rule to the selection
     * @return SelectionRule
     */
    public function addRule()
    {
        // init
        $rule = new SelectionRule();

        // store
        $this->_aRules[] = $rule;

        // send
        return $rule;
    }

    /**
     * Get all the rules within this selection
     * @return array
     */
    public function getRules()
    {
        // send
        return $this->_aRules;
    }

    /**
     * Apply a variable
     */
    public function applyVar($sVarName, $value)
    {
        // forward to all rules
        $nRuleCount = count($this->_aRules);
        for ($nRuleIndex = 0; $nRuleIndex < $nRuleCount; $nRuleIndex++)
        {
            // register
            $rule = $this->_aRules[$nRuleIndex];

            // forward
            $rule->applyVar($sVarName, $value);
        }
    }



    // ----------------------------------------------------------------------------
    // --- Public aliasses --------------------------------------------------------
    // ----------------------------------------------------------------------------


    /**
     * Set the entity type
     * @param $xType mixed Reference to the entity type
     */
    public function setType($xType)
    {
        // forward
        $this->getAliasRule()->setType($xType);
    }

    /**
     * Set the entity type as variable
     * @param $sVarName string The name of the variable
     */
    public function setTypeAsVar($sVarName)
    {
        // forward
        $this->getAliasRule()->setTypeAsVar($sVarName);
    }

    /**
     * Set the instance id
     * @param $xId mixed The id
     */
    public function setId($xId)
    {
        // forward
        $this->getAliasRule()->setId($xId);
    }

    /**
     * Set the instance id as variable
     * @param $sVarName string The name of the variable
     */
    public function setIdAsVar($sVarName)
    {
        // forward
        $this->getAliasRule()->setIdAsVar($sVarName);
    }

    /**
     * Set the property containing the entities
     * @param $xParent mixed Reference to the parent property
     */
    public function setProperty($xParent)
    {
        // forward
        $this->getAliasRule()->setProperty($xParent);
    }

    /**
     * Set the property containing the entities as variable
     * @param $sVarName string The name of the variable
     */
    public function setPropertyAsVar($sVarName)
    {
        // forward
        $this->getAliasRule()->setPropertyAsVar($sVarName);
    }

    /**
     * Set a rule value (multiple values possible)
     * @param $xProperty mixed Reference to the property
     * @param $value mixed The value to compare
     */
    public function setValue($xProperty, $value)
    {
        // forward
        $this->getAliasRule()->setValue($xProperty, $value);
    }

    /**
     * Set a rule value (multiple values possible) as variable
     * @param $xProperty mixed Reference to the property
     * @param $sVarName string The name of the variable
     */
    public function setValueAsVar($xProperty, $sVarName)
    {
        // forward
        $this->getAliasRule()->setValueAsVar($xProperty, $sVarName);
    }

    /**
     * Set the child types that are part of the result (multiple types possible)
     * @param $xTypes string|array The preferred child types (either name or id)
     */
    public function setChildTypes($xTypes)
    {
        // forward
        $this->getAliasRule()->setChildTypes($xTypes);
    }

    /**
     * Set the child types that are part of the result (multiple types possible) as variable
     * @param $sVarName string The name of the variable
     */
    public function setChildTypesAsVar($sVarName)
    {
        // forward
        $this->getAliasRule()->setChildTypesAsVar($sVarName);
    }

    /**
     * Set a child's value (multiple values possible)
     * @param $xProperty mixed Reference to the property
     * @param $value mixed The child value to compare
     */
    public function setChildValue($xProperty, $value)
    {
        // forward
        $this->getAliasRule()->setChildValue($xProperty, $value);
    }

    /**
     * Set a child's value (multiple values possible) as variable
     * @param $xProperty mixed Reference to the property
     * @param $sVarName string The name of the variable
     */
    public function setChildValueAsVar($xProperty, $sVarName)
    {
        // forward
        $this->getAliasRule()->setChildValueAsVar($xProperty, $sVarName);
    }

    /**
     * Get the instances that were added via the core
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Add instances (currently only the core code adds these
     * @param array $aInstances
     */
    public function setData($aInstances)
    {
        $this->_data = $aInstances;
    }



    // ----------------------------------------------------------------------------
    // --- Private methods --------------------------------------------------------
    // ----------------------------------------------------------------------------


    /**
     * Get alias rule
     * @return SelectionRule
     */
    private function getAliasRule()
    {
        // verify
        if (empty($this->_aliasRule))
        {
            // init and register
            $this->_aliasRule = $this->addRule();
        }

        // send
        return $this->_aliasRule;
    }

}
