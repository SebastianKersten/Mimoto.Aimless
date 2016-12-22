<?php

// classpath
namespace Mimoto\Core\entities;

// Mimoto classes
use Mimoto\Core\CoreConfig;
use Mimoto\EntityConfig\MimotoEntityPropertyValueTypes;


/**
 * LayoutGroupEnd
 *
 * @author Sebastian Kersten (@supertaboo)
 */
class LayoutGroupEnd
{

    public static function getStructure()
    {
        return (object) array(
            'id' => CoreConfig::MIMOTO_FORM_LAYOUT_GROUPEND,
            'created' => CoreConfig::EPOCH,
            // ---
            'name' => CoreConfig::MIMOTO_FORM_LAYOUT_GROUPEND,
            'visualName' => 'Group end',
            'extends' => null,
            'properties' => []
        );
    }

    public static function getData()
    {
        // hierin komen de velden die nodig zijn voor entity-management etc
    }

}
