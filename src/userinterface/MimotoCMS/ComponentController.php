<?php

// classpath
namespace Mimoto\UserInterface\MimotoCMS;

// Silex classes
use Mimoto\Core\CoreConfig;

// Symfony classes
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

// Silex classes
use Silex\Application;



/**
 * ComponentController
 *
 * @author Sebastian Kersten (@supertaboo)
 */
class ComponentController
{

    public function viewComponentOverview(Application $app)
    {
        // load
        $aComponents = $app['Mimoto.Data']->find(['type' => CoreConfig::MIMOTO_COMPONENT]);

        // create
        $page = $app['Mimoto.Aimless']->createComponent('Mimoto.CMS_components_ComponentOverview');

        // setup
        $page->addSelection('components', 'Mimoto.CMS_components_ComponentListItem', $aComponents);

        // output
        return $page->render();
    }


    public function entityNew(Application $app)
    {

        //$form->setVar('aPropertyNameValidation', json_encode(['regex' => '^[a-zA-Z][a-zA-Z0-9-_]*?$', 'maxchars' => 25, 'api' => 'Mimoto/form/validate']));

        // 'aPropertyNameValidation' => json_encode(['regex' => '^[a-zA-Z][a-zA-Z0-9-_]*?$', 'maxchars' => 10, 'api' => 'Mimoto/form/validate']),
        // 'aPropertyGroupValidation' => json_encode(['regex' => '^[a-zA-Z]*?[a-zA-Z0-9-_]*?(\.[a-zA-Z][a-zA-Z0-9-_]*?)*$'])


        // create
        $component = $app['Mimoto.Aimless']->createComponent('Mimoto.CMS_forms_Form');

        // prepare
        $aValues = [
            'name' => '',
            //'group' => $entity->getValue('group')
        ];

        // setup
        $component->addForm('form_entity_create', $aValues);

        // render and send
        return $component->render();
    }

    public function entityCreate(Application $app, Request $request)
    {
        // register
        $sEntityName = $request->get('name');

        // create entity
        $app['Mimoto.Config']->entityCreate($sEntityName);

        // send
        return new JsonResponse((object) array('result' => 'Entity created! '.date("Y.m.d H:i:s")), 200);
    }

    public function entityView(Application $app, $nEntityId)
    {
        // 1. load requested entity
        $entity = $app['Mimoto.Data']->get(CoreConfig::MIMOTO_ENTITY, $nEntityId);

        // 2. check if entity exists
        if ($entity === false) return $app->redirect("/mimoto.cms/entities");

        // 3. create component
        $page = $app['Mimoto.Aimless']->createComponent('Mimoto.CMS_entities_EntityDetail', $entity);

        // 4. setup component
        $page->setPropertyComponent('properties', 'Mimoto.CMS_entities_PropertyListItem');

        // 5. output
        return $page->render();
    }

    public function entityEdit(Application $app, $nEntityId)
    {

        // load
        $entity = $app['Mimoto.Data']->get(CoreConfig::MIMOTO_ENTITY, $nEntityId);

        // validate
        if ($entity === false) return $app->redirect("/mimoto.cms/entities");

        // create
        $component = $app['Mimoto.Aimless']->createComponent('Mimoto.CMS_forms_Form');

        // prepare
        $aValues = [
            'name' => $entity->getValue('name'),
            //'group' => $entity->getValue('group')
        ];

        // setup
        $component->addForm('form_entity_edit', $aValues);

        // render and send
        return $component->render();
    }

    public function entityUpdate(Application $app, Request $request, $nEntityId)
    {
        // register
        $sNewEntityName = $request->get('name');

        // change
        $app['Mimoto.Config']->entityUpdate($nEntityId, $sNewEntityName);

        // send
        return new JsonResponse((object) array('result' => 'Entity updated! '.date("Y.m.d H:i:s")), 200);
    }

    public function entityDelete(Application $app, Request $request, $nEntityId)
    {
        // delete
        $app['Mimoto.Config']->entityDelete($nEntityId);

        // send
        return new JsonResponse((object) array('result' => 'Entity deleted! '.date("Y.m.d H:i:s")), 200);
    }



    // --- EntityProperty ---


    public function entityPropertyNew(Application $app, $nEntityId)
    {
        // create
        $form = $app['Mimoto.Aimless']->createComponent('Mimoto.CMS_entities_FormEntityProperty');

        // setup
        $form->setVar('nEntityId', $nEntityId);

        // output
        return $form->render();
    }

    public function entityPropertyCreate(Application $app, Request $request, $nEntityId)
    {
        // register
        $sEntityPropertyName = $request->get('name');
        $sEntityPropertyType = 'value'; //$request->get('type');

        // create entity
        $app['Mimoto.Config']->entityPropertyCreate($nEntityId, $sEntityPropertyName, $sEntityPropertyType);

        // send
        return new JsonResponse((object) array('result' => 'EntityProperty created! '.date("Y.m.d H:i:s")), 200);
    }


    public function entityPropertyChange(Application $app)
    {

    }
}
